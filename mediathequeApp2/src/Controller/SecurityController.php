<?php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/access-denied', name: 'app_access_denied')]
    public function accessDenied(): Response
    {
        return $this->render('security/access_denied.html.twig');
    }

    #[Route(path: '/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        SessionInterface $session,
        LoggerInterface $logger,
        #[Autowire('%env(MAILER_FROM)%')] string $defaultSender,
        #[Autowire('%kernel.environment%')] string $appEnv
    ): Response {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            $user = $email !== '' ? $utilisateurRepository->findByEmail($email) : null;

            if ($user) {
                $code = (string) random_int(100000, 999999);
                $user->setPasswordResetCode($code);
                $user->setPasswordResetExpiresAt((new \DateTime('now'))->modify('+15 minutes'));
                $entityManager->flush();

                try {
                    $from = trim($defaultSender) !== '' ? $defaultSender : 'noreply@localhost';
                    $mail = (new Email())
                        ->from($from)
                        ->to((string) $user->getEmail())
                        ->subject('Code de réinitialisation de mot de passe')
                        ->text(sprintf(
                            "Bonjour %s,\n\nVotre code de réinitialisation est : %s\nCe code expire dans 15 minutes.",
                            $user->getPrenom() ?: $user->getEmail(),
                            $code
                        ));
                    $mailer->send($mail);
                } catch (\Throwable $e) {
                    $logger->error('Echec envoi mail reset password', [
                        'email' => $user->getEmail(),
                        'error' => $e->getMessage(),
                    ]);
                    if ($appEnv === 'dev') {
                        $this->addFlash('danger', 'Le mail ne part pas: '.$e->getMessage());
                    }
                    return $this->redirectToRoute('app_forgot_password');
                }

                $session->set('password_reset_email', (string) $user->getEmail());
                $session->remove('password_reset_verified');
                $this->addFlash('info', 'Code envoyé par email.');
                return $this->redirectToRoute('app_reset_password_code');
            }

            $this->addFlash('info', 'Si un compte existe avec cet email, un code a été envoyé.');
            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route(path: '/reset-password/code', name: 'app_reset_password_code', methods: ['GET', 'POST'])]
    public function resetPasswordCode(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        SessionInterface $session
    ): Response {
        $email = (string) $session->get('password_reset_email', '');
        if ($email === '') {
            $this->addFlash('warning', 'Commence par la demande de code par email.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $code = trim((string) $request->request->get('code'));

            $user = $utilisateurRepository->findByEmail($email);
            $isValidCode = $user
                && $user->getPasswordResetCode() === $code
                && $user->getPasswordResetExpiresAt() instanceof \DateTimeInterface
                && $user->getPasswordResetExpiresAt() >= new \DateTime('now');

            if (!$isValidCode) {
                $this->addFlash('danger', 'Code invalide ou expiré.');
                return $this->redirectToRoute('app_reset_password_code');
            }

            $session->set('password_reset_verified', true);
            return $this->redirectToRoute('app_reset_password_new');
        }

        return $this->render('security/reset_password_code.html.twig', [
            'masked_email' => preg_replace('/(^.).*(@.*$)/', '$1***$2', $email) ?: $email,
        ]);
    }

    #[Route(path: '/reset-password/new', name: 'app_reset_password_new', methods: ['GET', 'POST'])]
    public function resetPasswordNew(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ): Response {
        $email = (string) $session->get('password_reset_email', '');
        $verified = (bool) $session->get('password_reset_verified', false);
        if ($email === '' || !$verified) {
            $this->addFlash('warning', 'Valide d\'abord le code reçu.');
            return $this->redirectToRoute('app_reset_password_code');
        }

        if ($request->isMethod('POST')) {
            $password = (string) $request->request->get('password');
            $passwordConfirm = (string) $request->request->get('password_confirm');

            if ($password === '' || $password !== $passwordConfirm) {
                $this->addFlash('danger', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_reset_password_new');
            }

            $user = $utilisateurRepository->findByEmail($email);
            if (!$user) {
                $this->addFlash('danger', 'Utilisateur introuvable.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setPasswordResetCode(null);
            $user->setPasswordResetExpiresAt(null);
            $entityManager->flush();
            $session->remove('password_reset_email');
            $session->remove('password_reset_verified');

            $this->addFlash('success', 'Mot de passe modifié. Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password_new.html.twig');
    }
}
