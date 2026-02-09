<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\RoleRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/super-admin')]
#[IsGranted('ROLE_SUPERADMIN')]
class SuperAdminController extends AbstractController
{
    #[Route('/users', name: 'super_admin_users', methods: ['GET'])]
    public function users(UtilisateurRepository $utilisateurRepository): Response
    {
        return $this->render('super_admin/users.html.twig', [
            'users' => $utilisateurRepository->findBy([], ['idUtilisateur' => 'ASC']),
        ]);
    }

    #[Route('/users/{idUtilisateur}', name: 'super_admin_user_show', methods: ['GET'])]
    public function show(#[MapEntity(id: 'idUtilisateur')] Utilisateur $utilisateur): Response
    {
        return $this->render('super_admin/user_show.html.twig', [
            'user' => $utilisateur,
        ]);
    }

    #[Route('/users/{idUtilisateur}/revoke-admin', name: 'super_admin_revoke_admin', methods: ['POST'])]
    public function revokeAdmin(
        #[MapEntity(id: 'idUtilisateur')] Utilisateur $utilisateur,
        Request $request,
        RoleRepository $roleRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('revoke_admin'.$utilisateur->getIdUtilisateur(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('super_admin_users');
        }

        if ($utilisateur->isSuperAdmin()) {
            $this->addFlash('warning', 'Impossible de révoquer un super admin.');
            return $this->redirectToRoute('super_admin_users');
        }

        $userRole = $roleRepository->findOneBy(['nomRole' => 'user']) ?? $roleRepository->findOneBy(['nomRole' => 'ROLE_USER']);
        if (!$userRole) {
            $this->addFlash('danger', 'Rôle user introuvable.');
            return $this->redirectToRoute('super_admin_users');
        }

        $utilisateur->setIdRole($userRole);
        $entityManager->flush();
        $this->addFlash('success', 'Droits admin révoqués.');

        return $this->redirectToRoute('super_admin_users');
    }
}
