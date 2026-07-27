<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\EventListener\Fixture;

use Playtini\EasyAdminHelperBundle\Attribute\ReleaseSessionEarly;

class MethodAttributedController
{
    #[ReleaseSessionEarly]
    public function attributedAction(): void
    {
    }

    public function plainAction(): void
    {
    }
}
