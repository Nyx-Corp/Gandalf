<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Symfony\Bundle\DependencyInjection;

use Gandalf\Bridge\Symfony\Security\CorsSubscriber;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class GandalfExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $globals = [];

        if ($config['email']['enabled']) {
            $globals['gandalf_email_layout'] = $config['email']['layout'];
        }

        if ($config['admin']['enabled']) {
            $globals['gandalf_admin_layout'] = $config['admin']['layout'];
        }

        if ($globals) {
            $container->prependExtensionConfig('twig', ['globals' => $globals]);
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yaml');

        if ($config['cors']['enabled']) {
            $container->register(CorsSubscriber::class, CorsSubscriber::class)
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setArgument('$apiHost', $config['cors']['api_host']);
        }

        if ($config['admin']['enabled']) {
            $container->setParameter('gandalf.admin.roles', $config['admin']['roles']);
        }

        // Credentials encryption key
        $container->setParameter(
            'gandalf.credentials.encryption_key',
            $config['credentials']['encryption_key'],
        );
    }
}
