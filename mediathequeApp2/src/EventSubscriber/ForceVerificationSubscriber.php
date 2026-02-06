<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ForceVerificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private RouterInterface $router
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user || !method_exists($user, 'isVerified') || $user->isVerified()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        $path = $request->getPathInfo();

        $whitelistRoutes = [
            'app_verify',
            'app_verify_resend',
            'app_logout',
            'app_login',
            'app_register',
        ];

        $whitelistPrefixes = [
            '/_wdt',
            '/_profiler',
            '/assets',
            '/build',
        ];

        if (in_array($route, $whitelistRoutes, true)) {
            return;
        }
        foreach ($whitelistPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        $event->setResponse(new RedirectResponse($this->router->generate('app_verify')));
    }
}
