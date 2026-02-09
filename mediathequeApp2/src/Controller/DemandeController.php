<?php

namespace App\Controller;

use App\Entity\DemandeDocument;
use App\Form\DemandeDocumentType;
use App\Repository\DemandeDocumentRepository;
use App\Repository\DocumentRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class DemandeController extends AbstractController
{
    #[Route('/demande', name: 'app_demande_new', methods: ['GET'])]
    public function redirectLegacy(): Response
    {
        return $this->redirectToRoute('app_demande_achat');
    }

    #[Route('/demande/achat', name: 'app_demande_achat', methods: ['GET', 'POST'])]
    public function achat(Request $request, EntityManagerInterface $em, DemandeDocumentRepository $demandeRepo, DocumentRepository $documentRepo, UtilisateurRepository $utilisateurRepo, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $demande = new DemandeDocument();
        $demande->setStatutDemande('En attente');
        $demande->setIdUtilisateur($user);

        $form = $this->createForm(DemandeDocumentType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $titre = trim((string) $demande->getTitreDemande());
            $auteur = trim((string) $demande->getAuteurDemande());
            $demande->setTitreDemande($titre);
            $demande->setAuteurDemande($auteur);
            $demande->setTypeDemande('proposition');
            $demande->setQuantiteDemandee(max(1, (int) $demande->getQuantiteDemandee()));

            if ($documentRepo->hasAnyCopyForTitle($titre)) {
                $this->addFlash('warning', 'Ce livre existe déjà dans le catalogue. Utilisez la demande de réservation depuis le catalogue.');
                return $this->redirectToRoute('home');
            }

            if ($demandeRepo->findActivePropositionDuplicate($user, $titre) !== null) {
                $this->addFlash('warning', 'Vous avez déjà proposé ce document. Une seule proposition est autorisée par utilisateur.');
                return $this->redirectToRoute('home');
            }

            $em->persist($demande);
            $em->flush();

            // Notify admins by email
            try {
                $admins = $utilisateurRepo->findByRoleName('ROLE_ADMIN');
                foreach ($admins as $admin) {
                    if (!$admin->getEmail()) {
                        continue;
                    }
                    $email = (new Email())
                        ->from('noreply@localhost')
                        ->to($admin->getEmail())
                        ->subject('Nouvelle proposition d\'achat — Médiathèque')
                        ->text(sprintf(
                            "Proposition d'achat de %s %s (%s)\nTitre: %s\nAuteur: %s\nQuantité souhaitée: %d",
                            $user->getPrenom(),
                            $user->getNom(),
                            $user->getEmail(),
                            $demande->getTitreDemande(),
                            $demande->getAuteurDemande(),
                            $demande->getQuantiteDemandee()
                        ));
                    $mailer->send($email);
                }
            } catch (\Throwable $e) {
                // Do not block user if mail fails; log and continue
                // optionally: $this->get('logger')->error(...)
            }

            $this->addFlash('success', 'Proposition d\'achat envoyée. Un administrateur en a été informé.');
            return $this->redirectToRoute('home');
        }

        return $this->render('demande/new.html.twig', [
            'form' => $form,
        ]);
    }
}
