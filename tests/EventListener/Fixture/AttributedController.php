<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\EventListener\Fixture;

use Playtini\EasyAdminHelperBundle\Attribute\ReleaseSessionEarly;

#[ReleaseSessionEarly]
class AttributedController
{
    public function __invoke(): void
    {
    }

    public function someAction(): void
    {
    }
}
