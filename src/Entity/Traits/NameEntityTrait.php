<?php

namespace Playtini\EasyAdminHelperBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait NameEntityTrait
{
    use IdEntityTrait;

    #[ORM\Column(length: 255, options: ['default' => ''])]
    private string $name = '';

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function setName(?string $name): static
    {
        $this->name = mb_substr($name ?? '', 0, 255);

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
