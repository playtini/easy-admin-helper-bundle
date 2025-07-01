<?php

namespace Playtini\EasyAdminHelperBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait ShortCommentEntityTrait
{
    #[ORM\Column(length: 1024, options: ['default' => ''])]
    private string $comment = '';

    public function getComment(): string
    {
        return $this->comment ?? '';
    }

    public function setComment(?string $comment): static
    {
        $this->comment = mb_substr($comment ?? '', 0, 1024);

        return $this;
    }
}
