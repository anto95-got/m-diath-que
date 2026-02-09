<?php

namespace App\Controller;

use App\Entity\DemandeDocument;
use App\Entity\Document;
use App\Repository\AuthorRepository;
use App\Repository\CategorieRepository;
use App\Repository\DocumentRepository;
use App\Repository\DemandeDocumentRepository;
use App\Repository\EmpruntRepository;
use App\Repository\SousCategorieRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class CatalogueController extends AbstractController
{
    #[Route('/catalogue', name: 'catalogue_index', methods: ['GET'])]
    public function index(
        DocumentRepository $documentRepository,
        DemandeDocumentRepository $demandeRepo,
        CategorieRepository $categorieRepository,
        SousCategorieRepository $sousCategorieRepository,
        AuthorRepository $authorRepository,
        Request $request
    ): Response
    {
        $q = $request->query->get('q', '');
        $categorieId = (int) $request->query->get('categorie', 0);
        $sousCategorieId = (int) $request->query->get('sous_categorie', 0);
        $auteurId = (int) $request->query->get('auteur', 0);
        $dispo = (string) $request->query->get('dispo', '');

        $qb = $documentRepository->createQueryBuilder('d')
            ->distinct()
            ->leftJoin('d.idSousCategorie', 'sc')->addSelect('sc')
            ->leftJoin('sc.idCategorie', 'c')->addSelect('c')
            ->leftJoin('d.auteurs', 'a')->addSelect('a')
            ->leftJoin('d.idEtat', 'e')->addSelect('e')
            ->orderBy('d.titre', 'ASC');

        if ($q) {
            $qb->andWhere('d.titre LIKE :q OR d.codeBarres LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }
        if ($categorieId > 0) {
            $qb->andWhere('c.idCategorie = :categorieId')
                ->setParameter('categorieId', $categorieId);
        }
        if ($sousCategorieId > 0) {
            $qb->andWhere('sc.idSousCategorie = :sousCategorieId')
                ->setParameter('sousCategorieId', $sousCategorieId);
        }
        if ($auteurId > 0) {
            $qb->andWhere('a.idAuteur = :auteurId')
                ->setParameter('auteurId', $auteurId);
        }
        if ($dispo === '1') {
            $qb->andWhere('d.disponible = 1');
        } elseif ($dispo === '0') {
            $qb->andWhere('d.disponible = 0');
        } elseif ($dispo === 'soon') {
            $qb->andWhere('d.bientotDisponible = 1');
        }

        $docs = $qb->getQuery()->getResult();
        $availableCopiesByTitle = $documentRepository->countAvailableByTitle();

        $requestedDocumentIds = [];
        $requestedTitles = [];
        $pendingReservationIdsByDocument = [];
        $pendingReservationIdsByTitle = [];
        $user = $this->getUser();
        if ($user) {
            $requestedDocumentIds = $demandeRepo->findReservationDocumentIdsRequestedByUser($user);
            $requestedTitles = $demandeRepo->findReservationTitlesRequestedByUser($user);
            $pendingReservationIdsByDocument = $demandeRepo->findPendingReservationIdsByDocumentForUser($user);
            $pendingReservationIdsByTitle = $demandeRepo->findPendingReservationIdsByTitleForUser($user);
        }

        return $this->render('catalogue/index.html.twig', [
            'documents' => $docs,
            'query' => $q,
            'selectedCategorie' => $categorieId,
            'selectedSousCategorie' => $sousCategorieId,
            'selectedAuteur' => $auteurId,
            'selectedDispo' => $dispo,
            'categories' => $categorieRepository->findBy([], ['nomCategorie' => 'ASC']),
            'sousCategories' => $sousCategorieRepository->findBy([], ['nomSousCategorie' => 'ASC']),
            'auteurs' => $authorRepository->findBy([], ['nomPrenom' => 'ASC']),
            'availableCopiesByTitle' => $availableCopiesByTitle,
            'requestedDocumentIds' => $requestedDocumentIds,
            'requestedTitles' => $requestedTitles,
            'pendingReservationIdsByDocument' => $pendingReservationIdsByDocument,
            'pendingReservationIdsByTitle' => $pendingReservationIdsByTitle,
        ]);
    }

    #[Route('/catalogue/{id}/demande', name: 'catalogue_demande', methods: ['POST'])]
    public function demande(
        Document $document,
        EntityManagerInterface $em,
        DocumentRepository $documentRepo,
        DemandeDocumentRepository $demandeRepo,
        EmpruntRepository $empruntRepository,
        UtilisateurRepository $utilisateurRepo,
        MailerInterface $mailer
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $demande = new DemandeDocument();
        $demande->setIdUtilisateur($user);
        $demande->setTitreDemande($document->getTitre());

        // concat auteurs en chaîne simple
        $auteurs = [];
        foreach ($document->getAuteurs() as $a) {
            if (method_exists($a, 'getNomPrenom')) {
                $auteurs[] = $a->getNomPrenom();
            }
        }
        $demande->setAuteurDemande($auteurs ? implode(', ', $auteurs) : 'Inconnu');
        $demande->setTypeDemande('reservation');
        $demande->setStatutDemande('En attente');

        $titre = trim((string) $demande->getTitreDemande());
        $demande->setTitreDemande($titre);

        $userId = (int) ($user->getIdUtilisateur() ?? 0);
        if ($userId > 0 && $empruntRepository->hasActiveLoanOrReservationForTitleByUser($userId, $titre)) {
            $this->addFlash('warning', 'Vous avez déjà un exemplaire réservé/emprunté pour ce document.');
            return $this->redirectToRoute('catalogue_index');
        }

        if ($demandeRepo->userHasActiveReservationForTitle($user, $titre)) {
            $this->addFlash('warning', 'Vous avez déjà une demande en cours pour ce document.');
            return $this->redirectToRoute('catalogue_index');
        }

        if ($document->getBientotDisponible()) {
            $this->addFlash('warning', 'Ce document est en cours de commande et sera bientôt disponible.');
            return $this->redirectToRoute('catalogue_index');
        }

        $documentId = (int) $document->getIdDoc();
        if ($documentId > 0 && $demandeRepo->userAlreadyRequestedReservationForDocument($user, $documentId)) {
            $this->addFlash('warning', 'Vous avez déjà demandé ce document. Une seule demande est autorisée par utilisateur.');
            return $this->redirectToRoute('catalogue_index');
        }

        if (
            !$document->getDisponible()
            && !$documentRepo->hasAvailableCopyForTitle($titre, (int) $document->getIdDoc())
        ) {
            $this->addFlash('warning', 'Aucun exemplaire disponible pour ce document.');
            return $this->redirectToRoute('catalogue_index');
        }

        $demande->setIdDocDemande($document);

        $em->persist($demande);
        $em->flush();

        // notify admins
        try {
            $admins = $utilisateurRepo->findByRoleName('ROLE_ADMIN');
            foreach ($admins as $admin) {
                if (!$admin->getEmail()) continue;
                $email = (new Email())
                    ->from('noreply@localhost')
                    ->to($admin->getEmail())
                    ->subject('Nouvelle demande de réservation')
                    ->text(sprintf('Nouvelle réservation: %s demandée par %s %s (%s)', $demande->getTitreDemande(), $user->getPrenom(), $user->getNom(), $user->getEmail()));
                $mailer->send($email);
            }
        } catch (\Throwable $e) {
        }

        $this->addFlash('success', 'Demande de réservation envoyée pour "' . $document->getTitre() . '".');
        return $this->redirectToRoute('catalogue_index');
    }

    #[Route('/catalogue/demande/{id}/cancel', name: 'catalogue_demande_cancel', methods: ['POST'])]
    public function cancelDemande(
        int $id,
        Request $request,
        DemandeDocumentRepository $demandeRepo,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $demande = $demandeRepo->find($id);
        if (!$demande || $demande->getTypeDemande() !== 'reservation') {
            $this->addFlash('warning', 'Demande introuvable.');
            return $this->redirectToRoute('catalogue_index');
        }

        if ((int) $demande->getIdUtilisateur()?->getIdUtilisateur() !== (int) $user->getIdUtilisateur()) {
            $this->addFlash('danger', 'Action non autorisée.');
            return $this->redirectToRoute('catalogue_index');
        }

        if (!$this->isCsrfTokenValid('cancel_demande'.$demande->getIdDemande(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton invalide.');
            return $this->redirectToRoute('catalogue_index');
        }

        if ($demande->getStatutDemande() !== 'En attente') {
            $this->addFlash('warning', 'Cette demande ne peut plus être retirée.');
            return $this->redirectToRoute('catalogue_index');
        }

        $demande->setStatutDemande('Annulée');
        $demande->setMotifRefus('Annulée par l\'utilisateur.');
        $em->flush();

        $this->addFlash('success', 'Votre demande a bien été retirée.');
        return $this->redirectToRoute('catalogue_index');
    }
}
