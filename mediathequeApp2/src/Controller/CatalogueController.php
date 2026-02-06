<?php

namespace App\Controller;

use App\Entity\DemandeDocument;
use App\Entity\Document;
use App\Repository\DocumentRepository;
use App\Repository\DemandeDocumentRepository;
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
    public function index(DocumentRepository $documentRepository, Request $request): Response
    {
        $q = $request->query->get('q', '');
        $qb = $documentRepository->createQueryBuilder('d')
            ->leftJoin('d.idSousCategorie', 'sc')->addSelect('sc')
            ->leftJoin('d.idEtat', 'e')->addSelect('e')
            ->orderBy('d.titre', 'ASC');

        if ($q) {
            $qb->andWhere('d.titre LIKE :q OR d.codeBarres LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        $docs = $qb->getQuery()->getResult();

        return $this->render('catalogue/index.html.twig', [
            'documents' => $docs,
            'query' => $q,
        ]);
    }

    #[Route('/catalogue/{id}/demande', name: 'catalogue_demande', methods: ['POST'])]
    public function demande(
        Document $document,
        EntityManagerInterface $em,
        DemandeDocumentRepository $demandeRepo,
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

        // avoid duplicate reservation demandes
        $existing = $demandeRepo->findActiveDuplicate($user, $demande->getTitreDemande(), 'reservation');
        if ($existing) {
            $this->addFlash('warning', 'Vous avez déjà une demande en cours pour ce document.');
            return $this->redirectToRoute('catalogue_index');
        }

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
}
