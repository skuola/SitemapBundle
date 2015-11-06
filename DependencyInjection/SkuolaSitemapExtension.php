<?php

namespace Skuola\SitemapBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class SkuolaSitemapExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('parameters.yml');

        $databaseDriver = $config['db_driver'];

        if ('mongodb' !== $databaseDriver && 'orm' !== $databaseDriver) {
            throw new \InvalidArgumentException(sprintf('Invalid db_driver "%s"', $databaseDriver));
        }

        $loader->load(sprintf('%s.yml', $databaseDriver));

        $container->setParameter('skuola_sitemap', $config);
    }
}
