<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Controller\Traits;

use Peekabooauth\PeekabooBundle\DTO\UserDTO;

/**
 * @method UserDTO|null getUser()
 */
trait UserCrudControllerTrait
{
    public function getUserEmail(): string
    {
        $user = $this->getUser();

        return $user?->email ?? '';
    }
}
