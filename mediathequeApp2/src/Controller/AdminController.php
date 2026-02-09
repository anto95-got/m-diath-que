<?php

namespace App\Controller;

use App\Repository\DocumentRepository;
use App\Repository\EmpruntRepository;
use App\Repository\DemandeDocumentRepository;
use App\Entity\Document;
use App\Form\DocumentType;
use App\Entity\Emprunt;
use App\Form\EmpruntType;
use App\Entity\Categorie;
use App\Entity\SousCategorie;
use App\Entity\Etat;
use App\Entity\Auteur;
use App\Form\CategorieType;
use App\Form\SousCategorieType;
use App\Form\EtatType;
use App\Form\AuteurType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Form\FormError;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(DocumentRepository $documentRepository, EmpruntRepository $empruntRepository): Response
    {
        $totalDocs     = $documentRepository->countAll();
        $availableDocs = $documentRepository->countAvailable();
        $activeLoans   = $empruntRepository->countActive();
        $overdues      = $empruntRepository->countOverdue(new \DateTime('today'));

        return $this->render('admin/dashboard.html.twig', [
            'total_docs'      => $totalDocs,
            'available_docs'  => $availableDocs,
            'active_loans'    => $activeLoans,
            'overdues'        => $overdues,
            'latest_docs'     => $documentRepository->findLatest(5),
            'recent_loans'    => $empruntRepository->findRecent(5),
        ]);
    }

    #[Route('/documents', name: 'admin_documents', methods: ['GET'])]
    public function documents(DocumentRepository $documentRepository, Request $request): Response
    {
        $q = $request->query->get('q', '');

        $qb = $documentRepository->createQueryBuilder('d')
            ->leftJoin('d.idEtat', 'e')->addSelect('e')
            ->leftJoin('d.idSousCategorie', 'sc')->addSelect('sc');

        if ($q) {
            $qb->andWhere('d.titre LIKE :q OR d.codeBarres LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        $docs = $qb->orderBy('d.titre', 'ASC')->getQuery()->getResult();

        return $this->render('admin/documents.html.twig', [
            'documents' => $docs,
            'query'     => $q,
        ]);
    }

    #[Route('/documents/new', name: 'admin_documents_new', methods: ['GET', 'POST'])]
    public function documentsNew(Request $request, EntityManagerInterface $em, DocumentRepository $documentRepository): Response
    {
        $copyOfId = (int) $request->query->get('copy_of', 0);
        $initialCopies = max(1, min(100, (int) $request->query->get('copies', 1)));
        $sourceDocument = $copyOfId > 0 ? $documentRepository->find($copyOfId) : null;

        $document = new Document();
        if ($sourceDocument) {
            $document->setTitre((string) $sourceDocument->getTitre());
            $document->setIdEtat($sourceDocument->getIdEtat());
            $document->setIdSousCategorie($sourceDocument->getIdSousCategorie());
            $document->setDisponible(true);
            foreach ($sourceDocument->getAuteurs() as $auteur) {
                $document->addAuteur($auteur);
            }
        } elseif ($copyOfId > 0) {
            $this->addFlash('warning', 'Document source introuvable pour créer un exemplaire.');
        }
        $document->setBientotDisponible(false);
        $document->setCodeBarres($this->generateUniqueCodeBarres($documentRepository));

        $form = $this->createForm(DocumentType::class, $document, [
            'selected_categorie' => $document->getIdSousCategorie()?->getIdCategorie(),
            'initial_copies' => $initialCopies,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedCategorie = $form->get('categorie')->getData();
            $selectedSousCategorie = $document->getIdSousCategorie();

            if (
                $selectedCategorie !== null
                && $selectedSousCategorie !== null
                && $selectedSousCategorie->getIdCategorie()?->getIdCategorie() !== $selectedCategorie->getIdCategorie()
            ) {
                $form->get('idSousCategorie')->addError(new FormError('La sous-catégorie doit appartenir à la catégorie choisie.'));
            }

            $newAuteurName = trim((string) $form->get('newAuteur')->getData());
            if ($newAuteurName !== '') {
                $existingAuteur = $em->getRepository(Auteur::class)->createQueryBuilder('a')
                    ->andWhere('LOWER(a.nomPrenom) = :nom')
                    ->setParameter('nom', mb_strtolower($newAuteurName))
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                if (!$existingAuteur) {
                    $existingAuteur = new Auteur();
                    $existingAuteur->setNomPrenom($newAuteurName);
                    $em->persist($existingAuteur);
                }

                $document->addAuteur($existingAuteur);
            }

            if (!$form->isValid()) {
                return $this->render('admin/document_form.html.twig', [
                    'form' => $form,
                    'source_document' => $sourceDocument,
                ]);
            }

            $copiesCount = max(1, min(100, (int) $form->get('nombreExemplaires')->getData()));
            $createdCount = 0;

            for ($i = 0; $i < $copiesCount; $i++) {
                $copy = new Document();
                $copy->setTitre((string) $document->getTitre());
                $copy->setDisponible((bool) $document->getDisponible());
                $copy->setBientotDisponible(false);
                $copy->setIdEtat($document->getIdEtat());
                $copy->setIdSousCategorie($document->getIdSousCategorie());
                $copy->setCodeBarres($this->generateUniqueCodeBarres($documentRepository));
                foreach ($document->getAuteurs() as $auteur) {
                    $copy->addAuteur($auteur);
                }
                $em->persist($copy);
                $createdCount++;
            }

            $em->flush();
            $this->addFlash(
                'success',
                $sourceDocument
                    ? sprintf('%d nouvel(s) exemplaire(s) créé(s).', $createdCount)
                    : sprintf('%d document(s) créé(s).', $createdCount)
            );
            return $this->redirectToRoute('admin_documents');
        }

        return $this->render('admin/document_form.html.twig', [
            'form' => $form,
            'source_document' => $sourceDocument,
        ]);
    }

    #[Route('/emprunts', name: 'admin_emprunts', methods: ['GET'])]
    public function emprunts(EmpruntRepository $empruntRepository): Response
    {
        $openLoans = $empruntRepository->findOpenForAdmin();
        $historyLoans = $empruntRepository->findHistoryForAdmin();
        return $this->render('admin/emprunts.html.twig', [
            'open_loans' => $openLoans,
            'history_loans' => $historyLoans,
        ]);
    }

    #[Route('/emprunts/new', name: 'admin_emprunts_new', methods: ['GET', 'POST'])]
    public function empruntsNew(Request $request, EntityManagerInterface $em, EmpruntRepository $empruntRepository): Response
    {
        $emprunt = new Emprunt();
        $form = $this->createForm(EmpruntType::class, $emprunt);
        $form->handleRequest($request);

        $docTitle = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $code = trim((string) $form->get('codeBarres')->getData());
            $document = $em->getRepository(\App\Entity\Document::class)->findOneByCodeBarres($code);
            if (!$document) {
                $this->addFlash('error', 'Aucun document avec ce code-barres.');
            } elseif (!$document->getDisponible() || $empruntRepository->hasActiveLoanForDocument((int) $document->getIdDoc())) {
                $this->addFlash('error', 'Document déjà emprunté.');
            } else {
                $emprunt->setIdDoc($document);
                $emprunt->setDateEmprunt(new \DateTime());
                $emprunt->setDateRetourPrevue((new \DateTime('today'))->modify('+30 days'));
                $emprunt->setStatut('en_cours');
                $document->setDisponible(false);
                $em->persist($emprunt);
                $em->flush();
                $this->addFlash('success', 'Emprunt créé.');
                return $this->redirectToRoute('admin_emprunts');
            }
            $docTitle = $document?->getTitre();
        }

        return $this->render('admin/emprunt_form.html.twig', [
            'form' => $form,
            'doc_title' => $docTitle,
        ]);
    }

    #[Route('/documents/lookup', name: 'admin_documents_lookup', methods: ['GET'])]
    public function documentLookup(Request $request, DocumentRepository $repo): JsonResponse
    {
        $code = (string) $request->query->get('code', '');
        if (!$code) {
            return new JsonResponse(['found' => false]);
        }
        $doc = $repo->findOneByCodeBarres($code);
        if (!$doc) {
            return new JsonResponse(['found' => false]);
        }
        return new JsonResponse([
            'found' => true,
            'titre' => $doc->getTitre(),
            'disponible' => (bool) $doc->getDisponible(),
        ]);
    }

    #[Route('/emprunts/{id}/return', name: 'admin_emprunts_return', methods: ['POST'])]
    public function empruntsReturn(Emprunt $emprunt, Request $request, EntityManagerInterface $em): Response
    {
        if ($emprunt->getDateRetourEffectif() !== null || $emprunt->getStatut() !== 'en_cours') {
            $this->addFlash('warning', 'Ce retour ne peut pas être confirmé.');
            return $this->redirectToRoute('admin_emprunts');
        }

        $etatRetour = trim((string) $request->request->get('etat_retour', ''));
        $commentaireRetour = trim((string) $request->request->get('commentaire_retour', ''));
        if (!in_array($etatRetour, ['bon', 'abime'], true)) {
            $this->addFlash('error', 'État de retour invalide.');
            return $this->redirectToRoute('admin_emprunts');
        }

        $emprunt->setDateRetourEffectif(new \DateTime());
        $emprunt->setStatut('retourne');
        $emprunt->setEtatRetour($etatRetour);
        $emprunt->setCommentaireRetour($commentaireRetour !== '' ? $commentaireRetour : null);
        if ($emprunt->getIdDoc()) {
            $emprunt->getIdDoc()->setDisponible(true);
        }
        $em->flush();
        $this->addFlash('success', 'Emprunt clôturé.');
        return $this->redirectToRoute('admin_emprunts');
    }

    #[Route('/emprunts/{id}/checkout', name: 'admin_emprunts_checkout', methods: ['POST'])]
    public function empruntsCheckout(
        Emprunt $emprunt,
        EmpruntRepository $empruntRepository,
        EntityManagerInterface $em
    ): Response {
        if ($emprunt->getStatut() !== 'reserve' || $emprunt->getDateRetourEffectif() !== null) {
            $this->addFlash('warning', 'Ce retrait ne peut pas être validé.');
            return $this->redirectToRoute('admin_emprunts');
        }

        $documentId = $emprunt->getIdDoc()?->getIdDoc();
        if (!$documentId) {
            $this->addFlash('error', 'Document introuvable pour cet emprunt.');
            return $this->redirectToRoute('admin_emprunts');
        }

        if ($empruntRepository->hasActiveLoanForDocument((int) $documentId, (int) $emprunt->getIdEmprunt())) {
            $this->addFlash('error', 'Retrait impossible: ce document a déjà un emprunt actif.');
            return $this->redirectToRoute('admin_emprunts');
        }

        $emprunt->setDateEmprunt(new \DateTime());
        $emprunt->setDateRetourPrevue((new \DateTime('today'))->modify('+30 days'));
        $emprunt->setStatut('en_cours');
        if ($emprunt->getIdDoc()) {
            $emprunt->getIdDoc()->setDisponible(false);
        }

        $em->flush();
        $this->addFlash('success', 'Retrait validé.');
        return $this->redirectToRoute('admin_emprunts');
    }

    #[Route('/referentiel', name: 'admin_referentiel', methods: ['GET', 'POST'])]
    public function referentiel(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $forms = [];

        $categorie = new Categorie();
        $formCat = $this->createForm(CategorieType::class, $categorie);
        $formCat->handleRequest($request);
        if ($formCat->isSubmitted() && $formCat->isValid()) {
            $em->persist($categorie);
            $em->flush();
            $this->addFlash('success', 'Catégorie ajoutée.');
            return $this->redirectToRoute('admin_referentiel');
        }
        $forms['categorie'] = $formCat->createView();

        $sousCategorie = new SousCategorie();
        $formSousCat = $this->createForm(SousCategorieType::class, $sousCategorie);
        $formSousCat->handleRequest($request);
        if ($formSousCat->isSubmitted() && $formSousCat->isValid()) {
            $em->persist($sousCategorie);
            $em->flush();
            $this->addFlash('success', 'Sous-catégorie ajoutée.');
            return $this->redirectToRoute('admin_referentiel');
        }
        $forms['sousCategorie'] = $formSousCat->createView();

        $etat = new Etat();
        $formEtat = $this->createForm(EtatType::class, $etat);
        $formEtat->handleRequest($request);
        if ($formEtat->isSubmitted() && $formEtat->isValid()) {
            $em->persist($etat);
            $em->flush();
            $this->addFlash('success', 'État ajouté.');
            return $this->redirectToRoute('admin_referentiel');
        }
        $forms['etat'] = $formEtat->createView();

        $auteur = new Auteur();
        $formAuteur = $this->createForm(AuteurType::class, $auteur);
        $formAuteur->handleRequest($request);
        if ($formAuteur->isSubmitted() && $formAuteur->isValid()) {
            $em->persist($auteur);
            $em->flush();
            $this->addFlash('success', 'Auteur ajouté.');
            return $this->redirectToRoute('admin_referentiel');
        }
        $forms['auteur'] = $formAuteur->createView();

        return $this->render('admin/referentiel.html.twig', [
            'forms' => $forms,
        ]);
    }

    #[Route('/demandes', name: 'admin_demandes', methods: ['GET'])]
    public function demandes(
        DemandeDocumentRepository $demandeDocumentRepository,
        \App\Repository\CategorieRepository $categorieRepository,
        \App\Repository\SousCategorieRepository $sousCategorieRepository,
        \App\Repository\AuthorRepository $authorRepository,
        \App\Repository\EtatRepository $etatRepository
    ): Response
    {
        return $this->render('admin/demandes.html.twig', [
            'reservation_demandes' => $demandeDocumentRepository->findReservationQueue(),
            'purchase_demandes' => $demandeDocumentRepository->findPurchaseQueue(),
            'categories' => $categorieRepository->findBy([], ['nomCategorie' => 'ASC']),
            'sous_categories' => $sousCategorieRepository->findBy([], ['nomSousCategorie' => 'ASC']),
            'authors' => $authorRepository->findBy([], ['nomPrenom' => 'ASC']),
            'etats' => $etatRepository->findBy([], ['libelleEtat' => 'ASC']),
        ]);
    }

    #[Route('/demandes/{id}/order-purchase', name: 'admin_demandes_order_purchase', methods: ['POST'])]
    public function orderPurchaseDemande(
        int $id,
        Request $request,
        DemandeDocumentRepository $repo,
        DocumentRepository $documentRepository,
        EntityManagerInterface $em
    ): Response {
        $demande = $repo->find($id);
        if (!$demande || $demande->getTypeDemande() !== 'proposition' || $demande->getStatutDemande() !== 'En attente') {
            $this->addFlash('warning', 'Cette proposition ne peut pas être commandée.');
            return $this->redirectToRoute('admin_demandes');
        }

        $titre = trim((string) $request->request->get('titre_demande', $demande->getTitreDemande()));
        $auteurTexte = trim((string) $request->request->get('auteur_demande', $demande->getAuteurDemande()));
        $quantity = max(1, min(100, (int) $request->request->get('quantite', $demande->getQuantiteDemandee() ?? 1)));
        $etatId = (int) $request->request->get('etat_id', 0);
        $sousCategorieId = (int) $request->request->get('sous_categorie_id', 0);

        $etat = $em->getRepository(Etat::class)->find($etatId);
        $sousCategorie = $em->getRepository(SousCategorie::class)->find($sousCategorieId);
        if (!$etat || !$sousCategorie || $titre === '') {
            $this->addFlash('error', 'Informations de commande incomplètes (titre, état, sous-catégorie).');
            return $this->redirectToRoute('admin_demandes');
        }

        $authors = $this->resolveAuthorsFromRawText($auteurTexte, $em);

        for ($i = 0; $i < $quantity; $i++) {
            $document = new Document();
            $document->setTitre($titre);
            $document->setCodeBarres($this->generateUniqueCodeBarres($documentRepository));
            $document->setDisponible(false);
            $document->setBientotDisponible(true);
            $document->setIdEtat($etat);
            $document->setIdSousCategorie($sousCategorie);
            foreach ($authors as $author) {
                $document->addAuteur($author);
            }
            $em->persist($document);
        }

        $demande->setTitreDemande($titre);
        $demande->setAuteurDemande($auteurTexte !== '' ? $auteurTexte : 'Inconnu');
        $demande->setQuantiteDemandee($quantity);
        $demande->setStatutDemande('Commandé');
        $demande->setMotifRefus(null);

        $em->flush();
        $this->addFlash('success', sprintf('Commande créée (%d exemplaire(s)) et marquée "Bientôt disponible".', $quantity));
        return $this->redirectToRoute('admin_demandes');
    }

    #[Route('/demandes/{id}/receive-purchase', name: 'admin_demandes_receive_purchase', methods: ['POST'])]
    public function receivePurchaseDemande(
        int $id,
        DemandeDocumentRepository $repo,
        DocumentRepository $documentRepository,
        EntityManagerInterface $em
    ): Response {
        $demande = $repo->find($id);
        if (!$demande || $demande->getTypeDemande() !== 'proposition' || $demande->getStatutDemande() !== 'Commandé') {
            $this->addFlash('warning', 'Cette proposition ne peut pas être marquée comme arrivée.');
            return $this->redirectToRoute('admin_demandes');
        }

        $quantity = max(1, (int) ($demande->getQuantiteDemandee() ?? 1));
        $soonDocs = $documentRepository->findSoonByTitle((string) $demande->getTitreDemande(), $quantity);
        if ($soonDocs === []) {
            $this->addFlash('warning', 'Aucun exemplaire "bientôt disponible" trouvé pour cette demande.');
            return $this->redirectToRoute('admin_demandes');
        }

        foreach ($soonDocs as $doc) {
            $doc->setBientotDisponible(false);
            $doc->setDisponible(true);
            $em->persist($doc);
        }

        $demande->setStatutDemande('Ajouté');
        $demande->setMotifRefus(null);
        $em->flush();

        $this->addFlash('success', sprintf(
            '%d exemplaire(s) marqué(s) comme arrivés et disponibles dans le catalogue.',
            count($soonDocs)
        ));

        return $this->redirectToRoute('admin_demandes');
    }

    #[Route('/demandes/{id}/approve', name: 'admin_demandes_approve', methods: ['POST'])]
    public function approveDemande(
        int $id,
        DemandeDocumentRepository $repo,
        EntityManagerInterface $em,
        DocumentRepository $documentRepo,
        EmpruntRepository $empruntRepository,
        MailerInterface $mailer
    ): Response
    {
        $demande = $repo->find($id);
        if ($demande && $demande->getTypeDemande() === 'reservation' && $demande->getStatutDemande() === 'En attente') {
            $document = $demande->getIdDocDemande();
            if (!$document) {
                // Fallback for older demandes without explicit document link
                $document = $documentRepo->findFirstAvailableByTitle((string) $demande->getTitreDemande());
            }

            if (!$document || !$document->getDisponible()) {
                $demande->setStatutDemande('Refusé');
                $demande->setMotifRefus('Document donné à quelqu\'un d\'autre.');
                $em->flush();
                $this->addFlash('warning', 'Demande refusée automatiquement: document déjà attribué.');
                return $this->redirectToRoute('admin_demandes');
            }

            $demande->setStatutDemande('Réservé');
            $demande->setMotifRefus(null);

            // If possible, find the matching document by title and mark as not available
            try {
                if ($document && $document->getDisponible()) {
                    $document->setDisponible(false);
                    $em->persist($document);
                }

                if ($document && !$empruntRepository->hasActiveLoanForDocument((int) $document->getIdDoc())) {
                    $emprunt = new Emprunt();
                    $emprunt->setIdUtilisateur($demande->getIdUtilisateur());
                    $emprunt->setIdDoc($document);
                    $emprunt->setDateEmprunt(new \DateTime());
                    $emprunt->setDateRetourPrevue((new \DateTime('today'))->modify('+30 days'));
                    $emprunt->setStatut('reserve');
                    $em->persist($emprunt);
                }
            } catch (\Throwable $e) {
                // continue even if we cannot mark the document
            }

            $em->flush();

            // notify the user who made the demande
            try {
                $user = $demande->getIdUtilisateur();
                if ($user && $user->getEmail()) {
                    $email = (new Email())
                        ->from('noreply@localhost')
                        ->to($user->getEmail())
                        ->subject('Votre demande a été approuvée')
                        ->text(sprintf('Bonjour %s, votre demande "%s" a été approuvée par un administrateur.', $user->getPrenom() ?? $user->getEmail(), $demande->getTitreDemande()));
                    $mailer->send($email);
                }
            } catch (\Throwable $e) {
            }

            $this->addFlash('success', 'Demande validée (statut Réservé).');
        }
        return $this->redirectToRoute('admin_demandes');
    }

    #[Route('/demandes/{id}/checkout', name: 'admin_demandes_checkout', methods: ['POST'])]
    public function checkoutDemande(
        int $id,
        DemandeDocumentRepository $repo,
        DocumentRepository $documentRepo,
        EmpruntRepository $empruntRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $demande = $repo->find($id);
        if (!$demande || $demande->getTypeDemande() !== 'reservation' || $demande->getStatutDemande() !== 'Réservé') {
            $this->addFlash('warning', 'Cette demande ne peut pas être convertie en emprunt.');
            return $this->redirectToRoute('admin_demandes');
        }

        $userId = $demande->getIdUtilisateur()?->getIdUtilisateur();
        $requestedDocument = $demande->getIdDocDemande();
        $reservedLoan = $userId
            ? $empruntRepository->findReservedLoanByTitleForUser((string) $demande->getTitreDemande(), (int) $userId)
            : null;

        if ($reservedLoan) {
            $document = $reservedLoan->getIdDoc();

            if (!$document) {
                $this->addFlash('error', 'Document introuvable pour cette réservation.');
                return $this->redirectToRoute('admin_demandes');
            }

            if ($empruntRepository->hasActiveLoanForDocument((int) $document->getIdDoc(), (int) $reservedLoan->getIdEmprunt())) {
                $this->addFlash('error', 'Retrait impossible: ce document a déjà un emprunt actif.');
                return $this->redirectToRoute('admin_demandes');
            }

            $reservedLoan->setDateEmprunt(new \DateTime());
            $reservedLoan->setDateRetourPrevue((new \DateTime('today'))->modify('+30 days'));
            $reservedLoan->setStatut('en_cours');
            $emprunt = $reservedLoan;
        } else {
            $document = $requestedDocument;
            if (!$document || !$document->getDisponible()) {
                $document = $documentRepo->findFirstAvailableByTitle((string) $demande->getTitreDemande());
            }
            if (!$document) {
                $this->addFlash('error', 'Document indisponible pour cette demande.');
                return $this->redirectToRoute('admin_demandes');
            }
            $emprunt = new Emprunt();
            $emprunt->setIdUtilisateur($demande->getIdUtilisateur());
            $emprunt->setIdDoc($document);
            $emprunt->setDateEmprunt(new \DateTime());
            $emprunt->setDateRetourPrevue((new \DateTime('today'))->modify('+30 days'));
            $emprunt->setStatut('en_cours');
            $em->persist($emprunt);
        }

        $document->setDisponible(false);
        $demande->setStatutDemande('Emprunté');
        if ($document->getIdDoc()) {
            $this->autoRefuseConflictingReservationDemandes(
                (int) $document->getIdDoc(),
                (int) $demande->getIdDemande(),
                $repo,
                $em
            );
        }
        $em->flush();

        try {
            $user = $demande->getIdUtilisateur();
            if ($user && $user->getEmail()) {
                $email = (new Email())
                    ->from('noreply@localhost')
                    ->to($user->getEmail())
                    ->subject('Emprunt confirmé')
                    ->text(sprintf(
                        "Bonjour %s,\n\nLe document \"%s\" vous a été remis. La date de retour prévue est le %s.\n\nMerci.",
                        $user->getPrenom() ?? $user->getEmail(),
                        $document->getTitre(),
                        $emprunt->getDateRetourPrevue()?->format('d/m/Y') ?? ''
                    ));
                $mailer->send($email);
            }
        } catch (\Throwable $e) {
        }

        $this->addFlash('success', 'Emprunt validé: le document a été remis à l\'utilisateur.');
        return $this->redirectToRoute('admin_emprunts');
    }

    #[Route('/demandes/{id}/refuse', name: 'admin_demandes_refuse', methods: ['POST'])]
    public function refuseDemande(
        int $id,
        Request $request,
        DemandeDocumentRepository $repo,
        EmpruntRepository $empruntRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response
    {
        $demande = $repo->find($id);
        if ($demande) {
            $motifRefus = trim((string) $request->request->get('motif_refus', ''));
            if ($motifRefus === '') {
                $this->addFlash('error', 'Le motif du refus est obligatoire.');
                return $this->redirectToRoute('admin_demandes');
            }

            $wasReserved = $demande->getStatutDemande() === 'Réservé';
            $demande->setStatutDemande('Refusé');
            $demande->setMotifRefus($motifRefus);

            if ($wasReserved) {
                $userId = $demande->getIdUtilisateur()?->getIdUtilisateur();
                $reservedLoan = $userId
                    ? $empruntRepository->findReservedLoanByTitleForUser((string) $demande->getTitreDemande(), (int) $userId)
                    : null;
                $document = $reservedLoan?->getIdDoc();
                if ($document) {
                    $document->setDisponible(true);
                    $em->persist($document);
                }
                if ($reservedLoan) {
                    $reservedLoan->setDateRetourEffectif(new \DateTime('today'));
                    $reservedLoan->setStatut('retourne');
                    $em->persist($reservedLoan);
                }
            }

            $em->flush();

            // notify requester
            try {
                $user = $demande->getIdUtilisateur();
                if ($user && $user->getEmail()) {
                    $email = (new Email())
                        ->from('noreply@localhost')
                        ->to($user->getEmail())
                        ->subject('Votre demande a été refusée')
                        ->text(sprintf(
                            "Bonjour %s,\n\nVotre demande \"%s\" a été refusée par un administrateur.\nMotif: %s\n",
                            $user->getPrenom() ?? $user->getEmail(),
                            $demande->getTitreDemande(),
                            $motifRefus
                        ));
                    $mailer->send($email);
                }
            } catch (\Throwable $e) {
            }

            $this->addFlash('info', 'Demande refusée.');
        }
        return $this->redirectToRoute('admin_demandes');
    }

    /**
     * @return array<int, Auteur>
     */
    private function resolveAuthorsFromRawText(string $authorsText, EntityManagerInterface $em): array
    {
        $parts = preg_split('/[,;]+/', $authorsText) ?: [];
        $normalizedNames = [];
        foreach ($parts as $part) {
            $name = trim($part);
            if ($name !== '') {
                $normalizedNames[] = $name;
            }
        }

        if ($normalizedNames === []) {
            return [];
        }

        $authors = [];
        foreach ($normalizedNames as $name) {
            $existing = $em->getRepository(Auteur::class)->createQueryBuilder('a')
                ->andWhere('LOWER(a.nomPrenom) = :nom')
                ->setParameter('nom', mb_strtolower($name))
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$existing) {
                $existing = new Auteur();
                $existing->setNomPrenom($name);
                $em->persist($existing);
            }

            $authors[] = $existing;
        }

        return $authors;
    }

    private function autoRefuseConflictingReservationDemandes(
        int $documentId,
        int $keptDemandeId,
        DemandeDocumentRepository $demandeRepo,
        EntityManagerInterface $em
    ): void {
        $conflicts = $demandeRepo->findConflictingReservationDemandesForDocument($documentId, $keptDemandeId);
        foreach ($conflicts as $conflict) {
            $conflict->setStatutDemande('Refusé');
            $conflict->setMotifRefus('Document donné à quelqu\'un d\'autre.');
            $em->persist($conflict);
        }
    }

    private function generateUniqueCodeBarres(DocumentRepository $documentRepository): string
    {
        for ($i = 0; $i < 20; $i++) {
            $candidate = 'DOC' . date('ymdHis') . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            if (!$documentRepository->findOneByCodeBarres($candidate)) {
                return $candidate;
            }
        }

        return 'DOC' . date('U') . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    }
}
