<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\RegistrationType;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        RoleRepository $roleRepository,
        MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM)%')] string $defaultSender,
        SessionInterface $session,
        Security $security,
        LoggerInterface $logger
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Rôle par défaut en base (id 1) si aucun rôle n'a été fourni
            if (!$user->getIdRole()) {
                if ($defaultRole = $roleRepository->find(1)) {
                    $user->setIdRole($defaultRole);
                }
            }

            if ($user->getPassword()) {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $user->getPassword())
                );
            }

            // Génère et stocke un code de confirmation simple (6 chiffres)
            $code = (string) random_int(100000, 999999);
            $user->setConfirmationCode($code);
            $user->setIsVerified(false);
            $user->setConfirmationExpiresAt(new DateTimeImmutable('+15 minutes'));

            $entityManager->persist($user);
            $entityManager->flush();

            // Connecte automatiquement l'utilisateur pour qu'il puisse vérifier son email
            $security->login($user);

            // Envoi du mail de confirmation
            $email = (new Email())
                ->from($defaultSender)
                ->to($user->getEmail())
                ->subject('Confirmez votre inscription')
                ->text(sprintf(
                    "Bonjour %s,\n\nVoici votre code de confirmation : %s\n\nSaisissez-le sur la page de vérification pour activer votre compte.",
                    $user->getPrenom() ?? $user->getNom() ?? 'utilisateur',
                    $code
                ));
            $mailer->send($email);
            $logger->info('Mail de confirmation envoyé', [
                'to' => $user->getEmail(),
                'code' => $code,
                'expires_at' => $user->getConfirmationExpiresAt()?->format(DATE_ATOM),
            ]);

            $this->addFlash('info', 'Un code de vérification vous a été envoyé. Merci de confirmer votre email.');
            return $this->redirectToRoute('app_verify');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
