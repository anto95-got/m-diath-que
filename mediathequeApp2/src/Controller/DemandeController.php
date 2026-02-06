<?php

namespace App\Controller;

use App\Entity\DemandeDocument;
use App\Form\DemandeDocumentType;
use App\Repository\DemandeDocumentRepository;
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
    #[Route('/demande', name: 'app_demande_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, DemandeDocumentRepository $demandeRepo, UtilisateurRepository $utilisateurRepo, MailerInterface $mailer): Response
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
            // Prevent duplicate active demandes by same user for same title/type
            $existing = $demandeRepo->findActiveDuplicate($user, $demande->getTitreDemande(), $demande->getTypeDemande());
            if ($existing) {
                $this->addFlash('warning', 'Vous avez déjà une demande en cours pour ce document.');
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
                        ->subject('Nouvelle demande — Médiathèque')
                        ->text(sprintf("Nouvelle demande de %s %s (%s): %s — %s", $user->getPrenom(), $user->getNom(), $user->getEmail(), $demande->getTitreDemande(), $demande->getTypeDemande()));
                    $mailer->send($email);
                }
            } catch (\Throwable $e) {
                // Do not block user if mail fails; log and continue
                // optionally: $this->get('logger')->error(...)
            }

            $this->addFlash('success', 'Demande envoyée. Un administrateur en a été informé.');
            return $this->redirectToRoute('home');
        }

        return $this->render('demande/new.html.twig', [
            'form' => $form,
        ]);
    }
}
