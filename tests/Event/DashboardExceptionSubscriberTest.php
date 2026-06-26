<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\Event;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Registry\AdminControllerRegistryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Router\AdminRouteGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Event\DashboardExceptionSubscriber;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DashboardExceptionSubscriberTest extends TestCase
{
    public function testSubscribesToExceptionEvent(): void
    {
        self::assertSame(
            [KernelEvents::EXCEPTION => ['onKernelException']],
            DashboardExceptionSubscriber::getSubscribedEvents(),
        );
    }

    public function testSendFlashWrapsTitleInBold(): void
    {
        [$subscriber, $session] = $this->createSubscriber();

        $subscriber->sendFlashSuccess('Saved', 'All good');

        self::assertSame(
            ['<b>Saved</b><br/>All good'],
            $session->getFlashBag()->get('success'),
        );
    }

    public function testSendFlashWithMessageOnlyOmitsBoldTitle(): void
    {
        [$subscriber, $session] = $this->createSubscriber();

        $subscriber->sendFlashInfo('', 'just a message');

        self::assertSame(['just a message'], $session->getFlashBag()->get('info'));
    }

    public function testSendFlashWithEmptyTitleAndMessageAddsNothing(): void
    {
        [$subscriber, $session] = $this->createSubscriber();

        $subscriber->sendFlashWarning('', '');

        self::assertSame([], $session->getFlashBag()->get('warning'));
    }

    public function testSendFlashDangerFromExceptionEventExtractsException(): void
    {
        [$subscriber, $session] = $this->createSubscriber();

        $exception = new RuntimeException('Boom happened');
        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );

        $subscriber->sendFlashDanger($event);

        $flashes = $session->getFlashBag()->get('danger');
        self::assertCount(1, $flashes);
        self::assertStringContainsString(RuntimeException::class, $flashes[0]);
        self::assertStringContainsString('Boom happened', $flashes[0]);
    }

    /**
     * @return array{0: DashboardExceptionSubscriber, 1: Session}
     */
    private function createSubscriber(): array
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        // AdminContextProvider and AdminUrlGenerator are final; build real instances
        // from mocked interface dependencies (they are unused by the flash methods).
        $adminUrlGenerator = new AdminUrlGenerator(
            $this->createStub(AdminContextProviderInterface::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(AdminControllerRegistryInterface::class),
            $this->createStub(AdminRouteGeneratorInterface::class),
            $this->createStub(CacheItemPoolInterface::class),
        );

        $subscriber = new DashboardExceptionSubscriber(
            new AdminContextProvider($requestStack),
            $adminUrlGenerator,
            $requestStack,
            false,
        );

        return [$subscriber, $session];
    }
}
