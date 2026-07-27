<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Attribute;

use Attribute;

/**
 * Mark a controller (class or method) as session-read-only so the
 * {@see \Playtini\EasyAdminHelperBundle\EventListener\ReleaseSessionEarlyListener}
 * flushes the session before the action runs, preventing the session row from
 * being held while concurrent XHRs hit the same user.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class ReleaseSessionEarly
{
}
