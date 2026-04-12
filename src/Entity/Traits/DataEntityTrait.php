<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Yaml\Yaml;

trait DataEntityTrait
{
    #[ORM\Column(type: Types::JSON, options: ['default' => '{}'])]
    private array $data = [];

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data ?? [];

        return $this;
    }

    public function getDataString(int $maxLength = 200): string
    {
        $s = Yaml::dump($this->data);
        if (mb_strlen($s) > $maxLength) {
            $s = mb_substr($s, 0, $maxLength) . '...';
        }

        return nl2br(htmlspecialchars($s));
    }
}
