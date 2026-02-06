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
        $overdues      = $empruntRepository->countOverdue(new \DateTimeImmutable('today'));

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
    public function documentsNew(Request $request, EntityManagerInterface $em): Response
    {
        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($document);
            $em->flush();
            $this->addFlash('success', 'Document créé.');
            return $this->redirectToRoute('admin_documents');
        }

        return $this->render('admin/document_form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/emprunts', name: 'admin_emprunts', methods: ['GET'])]
    public function emprunts(EmpruntRepository $empruntRepository): Response
    {
        $emprunts = $empruntRepository->findBy([], ['idEmprunt' => 'DESC']);
        return $this->render('admin/emprunts.html.twig', [
            'emprunts' => $emprunts,
        ]);
    }

    #[Route('/emprunts/new', name: 'admin_emprunts_new', methods: ['GET', 'POST'])]
    public function empruntsNew(Request $request, EntityManagerInterface $em): Response
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
            } elseif (!$document->getDisponible()) {
                $this->addFlash('error', 'Document déjà emprunté.');
            } else {
                $emprunt->setIdDoc($document);
                $emprunt->setDateEmprunt(new \DateTimeImmutable());
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
    public function empruntsReturn(Emprunt $emprunt, EntityManagerInterface $em): Response
    {
        $emprunt->setDateRetourReelle(new \DateTimeImmutable());
        if ($emprunt->getIdDoc()) {
            $emprunt->getIdDoc()->setDisponible(true);
        }
        $em->flush();
        $this->addFlash('success', 'Emprunt clôturé.');
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
    public function demandes(DemandeDocumentRepository $demandeDocumentRepository): Response
    {
        return $this->render('admin/demandes.html.twig', [
            'demandes' => $demandeDocumentRepository->findPending(),
        ]);
    }

    #[Route('/demandes/{id}/approve', name: 'admin_demandes_approve', methods: ['POST'])]
    public function approveDemande(int $id, DemandeDocumentRepository $repo, EntityManagerInterface $em, DocumentRepository $documentRepo, MailerInterface $mailer): Response
    {
        $demande = $repo->find($id);
        if ($demande) {
            $demande->setStatutDemande('Réservé');

            // If possible, find the matching document by title and mark as not available
            try {
                $titre = $demande->getTitreDemande();
                $document = $documentRepo->findOneBy(['titre' => $titre]);
                if ($document && $document->getDisponible()) {
                    $document->setDisponible(false);
                    $em->persist($document);
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

    #[Route('/demandes/{id}/refuse', name: 'admin_demandes_refuse', methods: ['POST'])]
    public function refuseDemande(int $id, DemandeDocumentRepository $repo, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $demande = $repo->find($id);
        if ($demande) {
            $demande->setStatutDemande('Refusé');
            $em->flush();

            // notify requester
            try {
                $user = $demande->getIdUtilisateur();
                if ($user && $user->getEmail()) {
                    $email = (new Email())
                        ->from('noreply@localhost')
                        ->to($user->getEmail())
                        ->subject('Votre demande a été refusée')
                        ->text(sprintf('Bonjour %s, votre demande "%s" a été refusée par un administrateur.', $user->getPrenom() ?? $user->getEmail(), $demande->getTitreDemande()));
                    $mailer->send($email);
                }
            } catch (\Throwable $e) {
            }

            $this->addFlash('info', 'Demande refusée.');
        }
        return $this->redirectToRoute('admin_demandes');
    }
}
