<?php

namespace Playtini\EasyAdminHelperBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait IdEntityTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
