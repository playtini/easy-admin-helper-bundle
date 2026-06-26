<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class DashboardExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AdminContextProvider $adminContextProvider,
        private AdminUrlGenerator $adminUrlGenerator,
        private RequestStack $requestStack,
        #[Autowire('%kernel.debug%')]
        private bool $debug = false,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException']];
    }

    public function sendFlashPrimary(string $title = '', string $message = ''): void
    {
        $this->sendFlash('primary', $title, $message);
    }

    public function sendFlashSecondary(string $title = '', string $message = ''): void
    {
        $this->sendFlash('secondary', $title, $message);
    }

    public function sendFlashDark(string $title = '', string $message = ''): void
    {
        $this->sendFlash('dark', $title, $message);
    }

    public function sendFlashLight(string $title = '', string $message = ''): void
    {
        $this->sendFlash('light', $title, $message);
    }

    public function sendFlashSuccess(string $title = '', string $message = ''): void
    {
        $this->sendFlash('success', $title, $message);
    }

    public function sendFlashInfo(string $title = '', string $message = ''): void
    {
        $this->sendFlash('info', $title, $message);
    }

    public function sendFlashNotice(string $title = '', string $message = ''): void
    {
        $this->sendFlash('notice', $title, $message);
    }

    public function sendFlashWarning(string $title = '', string $message = ''): void
    {
        $this->sendFlash('warning', $title, $message);
    }

    public function sendFlashDanger(string|ExceptionEvent $title = '', string $message = ''): void
    {
        $this->sendFlash('danger', $title, $message);
    }

    public function sendFlash(string $type, string|ExceptionEvent $title = '', string $message = ''): void
    {
        if ($title instanceof ExceptionEvent) {
            $exception = $title->getThrowable();

            $title = $exception::class . '<br/>';
            $title .= '(' . $exception->getFile() . ':' . $exception->getLine() . ')';

            $message = $exception->getMessage();
        }

        if ($title !== '') {
            $title = '<b>' . $title . '</b><br/>';
        }

        $session = $this->requestStack->getSession();
        if (($title . $message) !== '' && $session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $title . $message);
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // Check if exception happened in EasyAdmin (avoid warning outside EA)
        if (!$this->adminContextProvider->getContext()) {
            return;
        }

        // Get back exception & send flash message
        $this->sendFlashDanger($event);

        // Get back crud information
        $crud = $this->adminContextProvider->getContext()->getCrud();
        if (!$crud) {
            return;
        }

        $controller = $crud->getControllerFqcn();
        $action = $crud->getCurrentPage();

        // Avoid infinite redirection
        // - If exception happened in 'index', redirect to dashboard
        // - If exception happened in another section, redirect to index page first
        // - If exception happened after submitting a form, just redirect to the initial page
        $url = $this->adminUrlGenerator->unsetAll();
        if ($action !== 'index') {
            $url = $url->setController($controller);
            if ($event->getRequest()->request->count()) {
                $url = $url->setAction((string)$action);
                $url = $url->set('exception', '1');
            }
        }

        // Avoid infinite redirection loop: render the error instead of redirecting again
        if ($event->getRequest()->query->get('exception') === '1') {
            $exception = $event->getThrowable();
            $html = '<h1>' . htmlspecialchars($exception->getMessage()) . '</h1>';
            if ($this->debug) {
                $html .= '<pre>' . nl2br(htmlspecialchars($exception->getTraceAsString())) . '</pre>';
            }
            $event->setResponse(new Response($html, Response::HTTP_INTERNAL_SERVER_ERROR));

            return;
        }

        $event->setResponse(new RedirectResponse($url->generateUrl()));
    }
}
