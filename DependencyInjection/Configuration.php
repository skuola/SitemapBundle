<?php

namespace Skuola\SitemapBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use samdark\sitemap\Sitemap;

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
                ->scalarNode('scheme')
                    ->defaultValue('http')
                ->end()
                ->scalarNode('host')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('db_driver')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('sitemaps')
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('index')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('base_url')
                                        ->defaultNull()
                                    ->end()
                                    ->scalarNode('path')
                                        ->defaultValue('%kernel.root_dir%/../web/sitemap_index.xml')
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('path')
                                ->defaultValue('%kernel.root_dir%/../web/sitemap.xml')
                            ->end()
                            ->arrayNode('routes')
                                ->normalizeKeys(false)
                                ->useAttributeAsKey('key')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('provider')
                                        ->end()
                                    ->end()
                                    ->children()
                                    ->scalarNode('lastmod')
                                        ->defaultNull()
                                    ->end()
                                    ->scalarNode('changefreq')
                                        ->defaultValue(Sitemap::MONTHLY)
                                    ->end()
                                    ->scalarNode('priority')
                                        ->defaultValue('0.5')
                                    ->end()
                                ->end()
                                ->children()
                                    ->arrayNode('options')
                                        ->defaultValue([])
                                        ->normalizeKeys(false)
                                        ->useAttributeAsKey('key')
                                        ->prototype('array')
                                            ->children()
                                                ->arrayNode('defaults')
                                                    ->prototype('scalar')
                                                ->end()
                                            ->end()
                                            ->arrayNode('repository')
                                                ->children()
                                                    ->scalarNode('object')
                                                    ->end()
                                                    ->scalarNode('property')
                                                    ->end()
                                                    ->scalarNode('method')
                                                        ->defaultValue('findAll')
                                                    ->end()
                                                    ->arrayNode('arguments')
                                                        ->prototype('scalar')
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                         ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
