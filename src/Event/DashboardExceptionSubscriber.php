<?php

namespace Playtini\EasyAdminHelperBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class DashboardExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AdminContextProvider $adminContextProvider,
        private AdminUrlGenerator $adminUrlGenerator,
        private RequestStack $requestStack,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException']];
    }

    public function sendFlashPrimary($title = '', $message = ''): void
    {
        $this->sendFlash('primary', $title, $message);
    }

    public function sendFlashSecondary($title = '', $message = ''): void
    {
        $this->sendFlash('secondary', $title, $message);
    }

    public function sendFlashDark($title = '', $message = ''): void
    {
        $this->sendFlash('dark', $title, $message);
    }

    public function sendFlashLight($title = '', $message = ''): void
    {
        $this->sendFlash('light', $title, $message);
    }

    public function sendFlashSuccess($title = '', $message = ''): void
    {
        $this->sendFlash('success', $title, $message);
    }

    public function sendFlashInfo($title = '', $message = ''): void
    {
        $this->sendFlash('info', $title, $message);
    }

    public function sendFlashNotice($title = '', $message = ''): void
    {
        $this->sendFlash('notice', $title, $message);
    }

    public function sendFlashWarning($title = '', $message = ''): void
    {
        $this->sendFlash('warning', $title, $message);
    }

    public function sendFlashDanger($title = '', $message = ''): void
    {
        $this->sendFlash('danger', $title, $message);
    }

    public function sendFlash($type, $title = '', $message = ''): void
    {
        if ($title instanceof ExceptionEvent) {

            $event = $title;
            $exception = $event->getThrowable();

            $title = get_class($exception) . '<br/>';
            $title .= '(' . $exception->getFile() . ':' . $exception->getLine() . ')';

            $message = $exception->getMessage();
        }

        if (!empty($title)) $title = '<b>' . $title . '</b><br/>';
        if (!empty($title . $message)) {
            $this->requestStack->getSession()->getFlashBag()->add($type, $title . $message);
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // Check if exception happened in EasyAdmin (avoid warning outside EA)
        if (!$this->adminContextProvider) return;
        if (!$this->adminContextProvider->getContext()) return;

        // Get back exception & send flash message
        $this->sendFlashDanger($event);

        // Get back crud information
        $crud = $this->adminContextProvider->getContext()->getCrud();
        if (!$crud) return;

        $controller = $crud->getControllerFqcn();
        $action = $crud->getCurrentPage();

        // Avoid infinite redirection
        // - If exception happened in 'index', redirect to dashboard
        // - If exception happened in another section, redirect to index page first
        // - If exception happened after submitting a form, just redirect to the initial page
        $url = $this->adminUrlGenerator->unsetAll();
        switch ($action) {
            case 'index':
                break;
            default:
                $url = $url->setController($controller);
                if ($this->requestStack->getCurrentRequest()->request->count()) {
                    $url = $url->setAction($action);
                    $url = $url->set('exception', '1');
                }
        }

        if ($event->getRequest()->query->get('exception') === '1') { // avoid infinite redirection loop
            $e = $event->getThrowable();
            echo '<h1>' . htmlspecialchars($e->getMessage()) . '</h1>';
            echo '<pre>' . nl2br(htmlspecialchars($e->getTraceAsString())) . '</pre>';
            exit;
        }

        $event->setResponse(new RedirectResponse($url));
    }
}
