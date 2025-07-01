<?php

namespace Playtini\EasyAdminHelperBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class EasyAdminHelperBundleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $builder): void
    {
        $bundleTemplatesOverrideDir = $builder->getParameter('kernel.project_dir') . '/templates/bundles/EasyAdminHelperBundle/';
        $builder->prependExtensionConfig('twig', [
            'paths' => is_dir($bundleTemplatesOverrideDir)
                ? [
                    'templates/bundles/EasyAdminHelperBundle/' => 'EasyAdminHelper',
                    \dirname(__DIR__) . '/../templates/' => 'EasyAdminHelper',
                ]
                : [
                    \dirname(__DIR__) . '/../templates/' => 'EasyAdminHelper',
                ],
        ]);
    }
}
