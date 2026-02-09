<?php

namespace App\Twig;

use App\Repository\DemandeDocumentRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminDemandeExtension extends AbstractExtension
{
    public function __construct(
        private readonly DemandeDocumentRepository $demandeRepository,
        private readonly Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_pending_demandes_count', [$this, 'getAdminPendingDemandesCount']),
        ];
    }

    public function getAdminPendingDemandesCount(): int
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return 0;
        }

        return $this->demandeRepository->countForAdminQueue();
    }
}
