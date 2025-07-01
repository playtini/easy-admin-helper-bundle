<?php

namespace Playtini\EasyAdminHelperBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gupalo\UidGenerator\UidGenerator;

trait UidEntityTrait
{
    use IdEntityTrait;

    #[ORM\Column(length: 32, unique: true, options: ['default' => ''])]
    private string $uid = '';

    public function getUid(): string
    {
        return $this->uid ?? '';
    }

    public function setUid(?string $uid): static
    {
        $this->uid = mb_substr($uid ?? UidGenerator::generate(), 0, 32);

        return $this;
    }
}
