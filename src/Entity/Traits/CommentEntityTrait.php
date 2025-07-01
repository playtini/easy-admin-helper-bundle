<?php

namespace Playtini\EasyAdminHelperBundle\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait CommentEntityTrait
{
    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    private string $comment = '';

    public function getComment(): string
    {
        return $this->comment ?? '';
    }

    public function setComment(?string $comment): static
    {
        $this->comment = mb_substr($comment ?? '', 0, 1_000_000);

        return $this;
    }
}
