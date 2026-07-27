<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\EventListener\Fixture;

use Playtini\EasyAdminHelperBundle\Attribute\ReleaseSessionEarly;

class InvokableMethodAttributedController
{
    #[ReleaseSessionEarly]
    public function __invoke(): void
    {
    }
}
