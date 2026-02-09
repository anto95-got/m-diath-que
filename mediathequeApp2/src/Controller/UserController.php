<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\RoleRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'utilisateurs' => $utilisateurRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        RoleRepository $roleRepository
    ): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur, [
            'can_edit_role' => true,
            'can_edit_password' => true,
            'password_help' => 'Laissez vide uniquement si vous voulez définir le mot de passe plus tard.',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Assigne le rôle par défaut (id 1) si aucun rôle n'a été choisi dans le formulaire
            if (!$utilisateur->getIdRole()) {
                if ($defaultRole = $roleRepository->find(1)) {
                    $utilisateur->setIdRole($defaultRole);
                }
            }

            if ($utilisateur->getPassword()) {
                $hashed = $passwordHasher->hashPassword($utilisateur, $utilisateur->getPassword());
                $utilisateur->setPassword($hashed);
            }
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function profile(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/show.html.twig', [
            'utilisateur' => $user,
            'can_manage_role' => false,
        ]);
    }

    #[Route('/{idUtilisateur}', name: 'app_user_show', methods: ['GET'])]
    public function show(#[MapEntity(id: 'idUtilisateur')] Utilisateur $utilisateur): Response
    {
        $current = $this->getUser();
        if (!$current instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        if ((int) $current->getIdUtilisateur() !== (int) $utilisateur->getIdUtilisateur() && !$current->isAdmin()) {
            $this->addFlash('danger', 'Accès refusé.');
            return $this->redirectToRoute('app_access_denied');
        }

        return $this->render('user/show.html.twig', [
            'utilisateur' => $utilisateur,
            'can_manage_role' => $current->isAdmin(),
        ]);
    }

    #[Route('/{idUtilisateur}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(id: 'idUtilisateur')] Utilisateur $utilisateur,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $current = $this->getUser();
        if (!$current instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        // Allow only the owner or a super-admin to edit
        $currentId = $current->getIdUtilisateur();
        if ($currentId !== $utilisateur->getIdUtilisateur() && !$current->isSuperAdmin()) {
            $this->addFlash('danger', 'Accès refusé : vous ne pouvez pas modifier ce profil.');
            return $this->redirectToRoute('app_access_denied');
        }

        $isOwnProfile = $currentId === $utilisateur->getIdUtilisateur();
        $oldPassword = $utilisateur->getPassword();
        $form = $this->createForm(UtilisateurType::class, $utilisateur, [
            'can_edit_role' => $current->isSuperAdmin(),
            'can_edit_password' => $isOwnProfile,
            'password_help' => $isOwnProfile ? 'Laissez vide pour conserver votre mot de passe actuel.' : null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isOwnProfile) {
                $newPassword = trim((string) $utilisateur->getPassword());
                if ($newPassword !== '' && $newPassword !== $oldPassword) {
                    $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $newPassword));
                } else {
                    $utilisateur->setPassword((string) $oldPassword);
                }
            } else {
                $utilisateur->setPassword((string) $oldPassword);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
            'is_own_profile' => $isOwnProfile,
        ]);
    }

    #[Route('/{idUtilisateur}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, #[MapEntity(id: 'idUtilisateur')] Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $current = $this->getUser();
        if (!$current instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }
        $currentId = $current->getIdUtilisateur();
        if ($currentId !== $utilisateur->getIdUtilisateur() && !$current->isSuperAdmin()) {
            $this->addFlash('danger', 'Accès refusé : vous ne pouvez pas supprimer ce profil.');
            return $this->redirectToRoute('app_access_denied');
        }

        if ($this->isCsrfTokenValid('delete'.$utilisateur->getIdUtilisateur(), $request->request->get('_token'))) {
            $entityManager->remove($utilisateur);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{idUtilisateur}/promote-admin', name: 'app_user_promote_admin', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function promoteAdmin(
        #[MapEntity(id: 'idUtilisateur')] Utilisateur $utilisateur,
        Request $request,
        RoleRepository $roleRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $current = $this->getUser();
        if (!$current instanceof Utilisateur || !$this->isCsrfTokenValid('promote_admin'.$utilisateur->getIdUtilisateur(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_user_index');
        }

        $adminRole = $roleRepository->findOneBy(['nomRole' => 'admin']) ?? $roleRepository->findOneBy(['nomRole' => 'ROLE_ADMIN']);
        if (!$adminRole) {
            $this->addFlash('danger', 'Rôle admin introuvable.');
            return $this->redirectToRoute('app_user_index');
        }

        if (!$utilisateur->isSuperAdmin()) {
            $utilisateur->setIdRole($adminRole);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur promu admin.');
        }

        return $this->redirectToRoute('app_user_index');
    }
}
