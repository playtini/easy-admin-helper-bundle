<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\EventListener\ReleaseSessionEarlyListener;
use Playtini\EasyAdminHelperBundle\Tests\EventListener\Fixture\AttributedController;
use Playtini\EasyAdminHelperBundle\Tests\EventListener\Fixture\InvokableMethodAttributedController;
use Playtini\EasyAdminHelperBundle\Tests\EventListener\Fixture\MethodAttributedController;
use Playtini\EasyAdminHelperBundle\Tests\EventListener\Fixture\PlainController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ReleaseSessionEarlyListenerTest extends TestCase
{
    public function testSavesStartedSessionForClassLevelAttribute(): void
    {
        $session = $this->startedSessionExpectingSave(1);
        $event = $this->event(new AttributedController(), $session, HttpKernelInterface::MAIN_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testSavesStartedSessionForMethodLevelAttribute(): void
    {
        $session = $this->startedSessionExpectingSave(1);
        $controller = [new MethodAttributedController(), 'attributedAction'];
        $event = $this->event($controller, $session, HttpKernelInterface::MAIN_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testSavesStartedSessionForArrayControllerInheritingTheClassAttribute(): void
    {
        $session = $this->startedSessionExpectingSave(1);
        $controller = [new AttributedController(), 'someAction'];
        $event = $this->event($controller, $session, HttpKernelInterface::MAIN_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testSavesStartedSessionForInvokableControllerWithAttributeOnInvokeMethod(): void
    {
        $session = $this->startedSessionExpectingSave(1);
        $event = $this->event(new InvokableMethodAttributedController(), $session, HttpKernelInterface::MAIN_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testIgnoresUnattributedMethodOnAnUnattributedClass(): void
    {
        $session = $this->startedSessionExpectingSave(0);
        $controller = [new MethodAttributedController(), 'plainAction'];
        $event = $this->event($controller, $session, HttpKernelInterface::MAIN_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testIgnoresControllerWithoutAttribute(): void
    {
        $session = $this->startedSessionExpectingSave(0);
        $event = $this->event(new PlainController(), $session, HttpKernelInterface::MAIN_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testIgnoresSubRequest(): void
    {
        $session = $this->startedSessionExpectingSave(0);
        $event = $this->event(new AttributedController(), $session, HttpKernelInterface::SUB_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testIgnoresUnstartedSession(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('isStarted')->willReturn(false);
        $session->expects($this->never())->method('save');

        $event = $this->event(new AttributedController(), $session, HttpKernelInterface::MAIN_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testIgnoresRequestWithoutSession(): void
    {
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            new AttributedController(),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
        );

        new ReleaseSessionEarlyListener()($event);

        $this->assertFalse($event->getRequest()->hasSession());
    }

    private function startedSessionExpectingSave(int $times): SessionInterface
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('isStarted')->willReturn(true);
        $session->expects($this->exactly($times))->method('save');

        return $session;
    }

    private function event(mixed $controller, SessionInterface $session, int $requestType): ControllerEvent
    {
        $request = new Request();
        $request->setSession($session);

        return new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            $request,
            $requestType,
        );
    }
}
