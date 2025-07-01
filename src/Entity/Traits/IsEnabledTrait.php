<?php

namespace Playtini\EasyAdminHelperBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Playtini\EasyAdminHelperBundle\Entity\Interfaces\ArchivableInterface;

trait IsEnabledTrait
{
    #[ORM\Column(options: ['default' => true])]
    private bool $isEnabled = true;

    public function isEnabled(): bool
    {
        $result = $this->isEnabled ?? true;
        if ($result && $this instanceof ArchivableInterface) {
            $result = !$this->isArchived();
        }

        return $result;
    }

    public function setIsEnabled(?bool $isEnabled): static
    {
        $this->isEnabled = $isEnabled ?? true;

        return $this;
    }
}
