<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\EventListener;

use Playtini\EasyAdminHelperBundle\Attribute\ReleaseSessionEarly;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * If a controller carries the {@see ReleaseSessionEarly} attribute, flush the
 * Symfony session (`$session->save()`) before the action runs. The action is
 * then free to do its work without the session row being held — important for
 * concurrent XHRs from the same user.
 *
 * Sessions are never started here: if the controller hasn't started one, no
 * save happens. The listener is inert in a project that uses the attribute
 * nowhere.
 */
#[AsEventListener(event: ControllerEvent::class)]
final readonly class ReleaseSessionEarlyListener
{
    public function __invoke(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->controllerHasAttribute($event->getController())) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        if ($session->isStarted()) {
            $session->save();
        }
    }

    private function controllerHasAttribute(mixed $controller): bool
    {
        if (is_array($controller) && count($controller) === 2 && is_object($controller[0]) && is_string($controller[1])) {
            [$object, $method] = $controller;
            $reflectionMethod = new ReflectionMethod($object, $method);
            if ($reflectionMethod->getAttributes(ReleaseSessionEarly::class) !== []) {
                return true;
            }

            return (new ReflectionClass($object))->getAttributes(ReleaseSessionEarly::class) !== [];
        }

        if (is_object($controller)) {
            return (new ReflectionClass($controller))->getAttributes(ReleaseSessionEarly::class) !== [];
        }

        return false;
    }
}
