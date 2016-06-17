<?php

/*
 * Skuola\SitemapBundle\Command\SitemapGeneratorCommand.php
 *
 * (c) Skuola.net <dev@skuola.net>
 */

namespace Skuola\SitemapBundle\Command;

use Skuola\SitemapBundle\Service\ParametersCollectionInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Routing\RouterInterface;
use samdark\sitemap\Index;
use samdark\sitemap\Sitemap;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SitemapGeneratorCommand extends ContainerAwareCommand
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var PropertyAccess
     */
    protected $accessor;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * SitemapGeneratorCommand constructor.
     *
     * @param RouterInterface $router
     * @param ObjectManager   $objectManager
     * @param array           $config
     */
    public function __construct(RouterInterface $router, ObjectManager $objectManager, array $config)
    {
        $this->objectManager = $objectManager;
        $this->router = $router;
        $this->config = $config;
        $this->accessor = PropertyAccess::createPropertyAccessor();

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sitemap:generator')
            ->setDescription('Generate sitemap based on sitemap_generator configuration')
            ->addOption('name', 'sn', InputOption::VALUE_OPTIONAL, 'Sitemap Name')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        gc_disable();

        $this->output = $output;

        $this->router->getContext()->setScheme($this->config['scheme']);
        $this->router->getContext()->setHost($this->config['host']);

        $start = time();

        $name = $input->getOption('name');

        if ($name && !$this->runSingleSitemap($name)) {
            return 1;
        } else {
            $this->runMultiSitemaps();
        }

        $this->output->writeln(
            $this->getHelper('formatter')->formatBlock(
                ['[Info]', sprintf('Mission Accomplished in %d s', (time() - $start))], ConsoleLogger::INFO
            )
        );

        return 0;
    }

    /**
     * @param $name
     *
     * @return bool|void
     */
    protected function runSingleSitemap($name)
    {
        if (!array_key_exists($name, $this->config['sitemaps'])) {
            $this->output->writeln(
                $this->getHelper('formatter')->formatBlock(
                    ['[Error]', sprintf('Invalid sitemap name %s', $name)], ConsoleLogger::ERROR
                )
            );

            return false;
        }

        $this->generateSitemap($name, $this->config['sitemaps'][$name]);
    }

    protected function runMultiSitemaps()
    {
        foreach ($this->config['sitemaps'] as $name => $options) {
            $this->generateSitemap($name, $options);
        }
    }

    /**
     * @param $name
     * @param array $options
     */
    protected function generateSitemap($name, array $options)
    {
        $this->output->writeln(
            $this->getHelper('formatter')->formatBlock(['[Info]', sprintf("Generating %s Sitemap \n", $name)], ConsoleLogger::INFO)
        );

        $sitemap = $this->generateSitemapFromRoutes(
            new Sitemap($options['path']),
            $options['routes']
        );

        $this->output->writeln(
            $this->getHelper('formatter')->formatBlock(['[Info]', sprintf("Writing %s Sitemap... \n", $name)], ConsoleLogger::INFO)
        );

        $sitemap->write();

        $this->output->writeln(
            $this->getHelper('formatter')->formatBlock(['[Info]', 'Yea look here!'], ConsoleLogger::INFO)
        );

        $this->printSitemapsPath(
            $sitemap->getSitemapUrls($this->getBaseUrl($options)),
            realpath(dirname($options['path']))
        );

        $sitemapIndex = $this->generateSitemapsIndex($sitemap, $options);
        $sitemapIndex->write();

        $this->output->writeln(
            $this->getHelper('formatter')->formatBlock(['[Info]', sprintf("%s Sitemap Index: %s\n\n", $name, realpath($options['index']['path']))], ConsoleLogger::INFO)
        );
    }

    /**
     * @param Sitemap $sitemap
     * @param array   $routes
     *
     * @return Sitemap
     */
    protected function generateSitemapFromRoutes(Sitemap $sitemap, array $routes)
    {
        foreach ($routes as $routeName => $routeConfigurations) {
            $routeParameters = $this->getRouteParamaters($routeConfigurations);

            $this->output->writeln(
                $this->getHelper('formatter')->formatBlock(['[Route]', $routeName], 'comment')
            );

            if (empty($routeParameters)) {
                $sitemap->addItem(
                    $this->router->generate($routeName, [], true),
                    $routeConfigurations['lastmod'],
                    $routeConfigurations['changefreq'],
                    $routeConfigurations['priority']
                );

                $this->output->writeln('');
                continue;
            }

            $this->addItems(
                $sitemap,
                $routeName,
                $routeParameters,
                $routeConfigurations
            );

            $this->output->writeln('');
        }

        return $sitemap;
    }

    /**
     * @param $urls
     * @param $basePath
     */
    protected function printSitemapsPath($urls, $basePath)
    {
        $paths = array_map(
            function ($url) use ($basePath) {
                return sprintf('%s/%s', $basePath, basename($url));
            }, $urls
        );

        $table = new Table($this->output);
        $table->setHeaders(['N°', 'Sitemap Path']);

        $sitemapCounter = 0;
        foreach ($paths as $path) {
            $table->addRow([
                    ++$sitemapCounter,
                    $path,
                ]
            );
        }

        $table->render();
        $this->output->writeln('');
    }

    /**
     * @param Sitemap $sitemap
     * @param $route
     * @param array $parametersCollection
     * @param array $routeConfigurations
     */
    protected function addItems(Sitemap $sitemap, $route, array $parametersCollection, array $routeConfigurations)
    {
        $progress = new ProgressBar($this->output, count($parametersCollection));
        $progress->start();

        foreach ($parametersCollection as $parameters) {
            $sitemap->addItem(
                $this->router->generate($route, $this->accessor->getValue($parameters, '[route_params]') ?: $parameters, true),
                $this->accessor->getValue($parameters, '[sitemap_optional_tags][lastmod]')    ?: $routeConfigurations['lastmod'],
                $this->accessor->getValue($parameters, '[sitemap_optional_tags][changefreq]') ?: $routeConfigurations['changefreq'],
                $this->accessor->getValue($parameters, '[sitemap_optional_tags][priority]')   ?: $routeConfigurations['priority']
            );

            $progress->advance();
        }

        $progress->finish();
        $this->output->writeln('');
    }

    /**
     * @param array $keys
     * @param array $options
     *
     * @return array
     */
    protected function getCombinationsWithRouteParameters(array $keys, array $options)
    {
        $combinations = $this->generateCombinations(
            $this->getValuesAttributes($options)
        );

        $values = [];

        foreach ($combinations as $combination) {
            if (!is_array($combination)) {
                $combination = [$combination];
            }

            $values[] = array_combine($keys, $combination);
        }

        return $values;
    }

    /**
     * @param $routeOptions
     *
     * @return array
     */
    protected function getValuesAttributes($routeOptions)
    {
        $values = [];

        foreach ($routeOptions as $option) {
            if (empty($option['repository'])) {
                $values[] = $option['defaults'];

                continue;
            }

            $repositoryOptions = $option['repository'];

            $values[] = array_unique(
                array_merge(
                    array_map(
                        function ($value) use ($repositoryOptions) {
                            return call_user_func(
                                [
                                    $value,
                                    Container::camelize("get{$repositoryOptions['property']}"),
                                ]
                            );
                        },
                        call_user_func_array([
                            $this->objectManager->getRepository($repositoryOptions['object']),
                            $repositoryOptions['method'],
                        ], $repositoryOptions['arguments'])
                    ),
                    $option['defaults']
                )
            );
        }

        return $values;
    }

    /**
     * @param array $arrays
     * @param int   $i
     *
     * @return array
     */
    protected function generateCombinations(array $arrays, $i = 0)
    {
        if (!isset($arrays[$i])) {
            return [];
        }

        if ($i == count($arrays) - 1) {
            return $arrays[$i];
        }

        $tmp = $this->generateCombinations($arrays, $i + 1);

        $result = [];

        foreach ($arrays[$i] as $v) {
            foreach ($tmp as $t) {
                $result[] = is_array($t) ? array_merge([$v], $t) : [$v, $t];
            }
        }

        return $result;
    }

    /**
     * @param Sitemap $sitemap
     * @param array   $options
     *
     * @return Index
     */
    protected function generateSitemapsIndex(Sitemap $sitemap, array $options)
    {
        $sitemapIndex = new Index($options['index']['path']);

        $sitemapFileUrls = $sitemap->getSitemapUrls(
            $this->getBaseUrl($options)
        );

        foreach ($sitemapFileUrls as $sitemapUrl) {
            $sitemapIndex->addSitemap($sitemapUrl);
        }

        return $sitemapIndex;
    }

    /**
     * @param array $definedRoutes
     *
     * @return bool
     */
    protected function validateRoutes(array $definedRoutes)
    {
        foreach ($definedRoutes as $name => $info) {
            if (!$this->router->getRouteCollection()->get($name)) {
                throw new \InvalidArgumentException(sprintf('The route "%s" does not exist.', $name));
            }
        }

        return true;
    }

    /**
     * @param array $options
     *
     * @return string
     */
    protected function getBaseUrl(array $options)
    {
        if (!empty($options['index']['base_url'])) {
            if ('/' == substr($options['index']['base_url'], -1)) {
                return $options['index']['base_url'];
            }

            return sprintf('%s/', $options['index']['base_url']);
        }

        $context = $this->router->getContext();

        return sprintf('%s://%s%s', $context->getScheme(), $context->getHost(), $context->getPathInfo());
    }

    /**
     * @param array $routeConfigurations
     *
     * @return array
     */
    protected function getRouteParamaters(array $routeConfigurations)
    {
        if (!empty($routeConfigurations['provider'])) {
            $service = $this->getContainer()->get(
                $routeConfigurations['provider']
            );

            if (!$service instanceof ParametersCollectionInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid %s class, please implement %s',
                        $routeConfigurations['service'],
                        ParametersCollectionInterface::class
                    )
                );
            }

            return $service->getParametersCollection();
        }

        return $this->getCombinationsWithRouteParameters(
            array_keys($routeConfigurations['options']),
            $routeConfigurations['options']
        );
    }
}
