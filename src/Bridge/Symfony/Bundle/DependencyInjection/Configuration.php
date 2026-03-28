<?php

declare(strict_types=1);

namespace Gandalf\Bridge\Symfony\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('gandalf');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('cors')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('api_host')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('admin')
                    ->canBeDisabled()
                    ->children()
                        ->scalarNode('layout')
                            ->defaultValue('@_gandalf/admin/layout.html.twig')
                            ->info('Twig template used as parent layout for admin pages')
                        ->end()
                        ->arrayNode('roles')
                            ->useAttributeAsKey('role')
                            ->scalarPrototype()->end()
                            ->defaultValue(['ROLE_USER' => 'Utilisateur', 'ROLE_ADMIN' => 'Administrateur'])
                            ->info('Available ACL roles for the account edit form')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
