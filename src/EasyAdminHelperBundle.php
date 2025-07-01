<?php

namespace Playtini\EasyAdminHelperBundle;

use Playtini\EasyAdminHelperBundle\DependencyInjection\EasyAdminHelperBundleExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EasyAdminHelperBundle extends Bundle
{
    protected string $name = 'EasyAdminHelperBundle';

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new EasyAdminHelperBundleExtension();
        }

        return $this->extension;
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
