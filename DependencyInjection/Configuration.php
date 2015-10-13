<?php

namespace Skuola\SitemapBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use samdark\sitemap\Sitemap;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('skuola_sitemap');

        $rootNode
            ->children()
                ->scalarNode('scheme')->defaultValue('http')->end()
                ->scalarNode('host')->defaultValue('%domain%')->end()
                ->scalarNode('index_path')->defaultValue('%kernel.root_dir%/../web/sitemap_index.xml')->end()
                ->scalarNode('path')->defaultValue('%kernel.root_dir%/../web/sitemap.xml')->end()
                ->arrayNode('routes')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('changefreq')->defaultValue(Sitemap::MONTHLY)->end()
                        ->scalarNode('priority')->defaultValue('0.5')->end()
                    ->end()
                    ->children()
                        ->arrayNode('route_params')->defaultValue([])
                        ->normalizeKeys(false)
                        ->useAttributeAsKey('key')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('entity')->end()
                                ->scalarNode('prop')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
