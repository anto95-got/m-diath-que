<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\RoleRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
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

    #[Route('/{matricule}', name: 'app_user_show', methods: ['GET'])]
    public function show(Utilisateur $utilisateur): Response
    {
        return $this->render('user/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/{matricule}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $current = $this->getUser();
        if (!$current) {
            return $this->redirectToRoute('app_login');
        }

        // Allow only the owner or a super-admin to edit
        $currentId = method_exists($current, 'getIdUtilisateur') ? $current->getIdUtilisateur() : null;
        $isSuperAdmin = $current->getIdRole() && $current->getIdRole()->getNomRole() === 'ROLE_SUPER_ADMIN';
        if ($currentId !== $utilisateur->getIdUtilisateur() && !$isSuperAdmin) {
            $this->addFlash('danger', 'Accès refusé : vous ne pouvez pas modifier ce profil.');
            return $this->redirectToRoute('home');
        }
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    #[Route('/{matricule}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $current = $this->getUser();
        if (!$current) {
            return $this->redirectToRoute('app_login');
        }
        $currentId = method_exists($current, 'getIdUtilisateur') ? $current->getIdUtilisateur() : null;
        $isSuperAdmin = $current->getIdRole() && $current->getIdRole()->getNomRole() === 'ROLE_SUPER_ADMIN';
        if ($currentId !== $utilisateur->getIdUtilisateur() && !$isSuperAdmin) {
            $this->addFlash('danger', 'Accès refusé : vous ne pouvez pas supprimer ce profil.');
            return $this->redirectToRoute('home');
        }

        if ($this->isCsrfTokenValid('delete'.$utilisateur->getMatricule(), $request->request->get('_token'))) {
            $entityManager->remove($utilisateur);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
