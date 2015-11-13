<?php

namespace Skuola\SitemapBundle\Command;

use Skuola\SitemapBundle\Service\ParametersCollectionInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Routing\RouterInterface;
use samdark\sitemap\Index;
use samdark\sitemap\Sitemap;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\Common\Persistence\ObjectManager;

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
     * @var array
     */
    protected $config;

    /**
     * @var Sitemap
     */
    protected $sitemap;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * SitemapGeneratorCommand constructor.
     * @param RouterInterface $router
     * @param ObjectManager $objectManager
     * @param array $config
     */
    public function __construct(RouterInterface $router, ObjectManager $objectManager, array $config)
    {
        $this->objectManager = $objectManager;
        $this->router        = $router;
        $this->config        = $config;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('sitemap:generator')
            ->setDescription('Generate sitemap based on sitemap_generator configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $start = time();

        $this->validateRoutes($this->config['routes']);

        $formatter = $this->getHelper('formatter');

        $this->router->getContext()->setScheme($this->config['scheme']);
        $this->router->getContext()->setHost($this->config['host']);

        $this->sitemap = new Sitemap($this->config['path']);

        $this->generateSitemapFromRoutes($this->config['routes']);

        $this->output->writeln($formatter->formatBlock(['[Info]', count($this->sitemap->getSitemapUrls($this->getBaseUrl())) . ' sitemap will be generated'], 'info', true));

        $this->sitemap->write();

        $this->output->writeln($formatter->formatBlock(['[Info]', 'Generating Sitemap index'], 'info', true));

        $this->generateSitemapsIndex();

        $this->output->writeln($formatter->formatBlock(['[Info]', 'Mission Accomplished in '. (time() - $start) . ' s'], 'info', true));
    }

    /**
     * @param array $routes
     * @return Sitemap
     */
    public function generateSitemapFromRoutes(array $routes)
    {
        foreach ($routes as $routeName => $routeConfigurations) {
            $priority   = $routeConfigurations['priority'];
            $changefreq = $routeConfigurations['changefreq'];

            $this->output->writeln(
                $this->getHelper('formatter')->formatBlock(['[Route]', $routeName], 'comment')
            );

            if (!empty($routeConfigurations['provider'])) {
                $service = $this->getContainer()->get(
                    $routeConfigurations['provider']
                );

                if (!$service instanceof ParametersCollectionInterface) {
                    throw new \InvalidArgumentException(sprintf('Invalid %s class, please implement %s', $routeConfigurations['service'], ParametersCollectionInterface::class));
                }

                $this->addItems(
                    $routeName,
                    $service->getParametersCollection(),
                    $changefreq,
                    $priority
                );

                $this->output->writeln('');
                continue;
            }

            $routeOptions = $routeConfigurations['options'];
            $routeKeys = array_keys($routeOptions);

            if (empty($routeOptions)) {
                $this->sitemap->addItem($this->router->generate($routeName, [], true), null, $changefreq, $priority);

                $this->output->writeln('');
                continue;
            }

            $this->addItems(
                $routeName,
                $this->getCombinationsWithRouteParameters($routeKeys, $routeOptions),
                $changefreq,
                $priority
            );

            $this->output->writeln('');
        }

        return $this->sitemap;
    }

    protected function addItems($route, array $parametersCollection, $changefreq, $priority)
    {
        $progress = new ProgressBar($this->output, count($parametersCollection));
        $progress->start();

        foreach($parametersCollection as $parameters) {
            $this->sitemap->addItem($this->router->generate($route, $parameters, true), null, $changefreq, $priority);
            $progress->advance();
        }

        $progress->finish();
        $this->output->writeln('');
    }

    public function getCombinationsWithRouteParameters($keys, $options)
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

    public function getValuesAttributes($routeOptions)
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
                        function($value) use ($repositoryOptions) {
                            return call_user_func(
                                [
                                    $value,
                                    Container::camelize("get{$repositoryOptions['property']}")
                                ]
                            );
                        },
                        call_user_func_array([
                            $this->objectManager->getRepository($repositoryOptions['object']),
                            $repositoryOptions['method']
                        ], $repositoryOptions['arguments'])
                    ),
                    $option['defaults']
                )
            );
        }

        return $values;
    }

    public function generateCombinations(array $arrays, $i = 0)
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

    protected function generateSitemapsIndex()
    {
        $sitemapIndex = new Index($this->config['index_path']);

        $sitemapFileUrls = $this->sitemap->getSitemapUrls($this->getBaseUrl());

        foreach ($sitemapFileUrls as $sitemapUrl) {
            $sitemapIndex->addSitemap($sitemapUrl);
        }

        return $sitemapIndex->write();
    }

    protected function validateRoutes($definedRoutes)
    {
        foreach($definedRoutes as $name => $info) {
            if (!$this->router->getRouteCollection()->get($name)) {
                throw new \InvalidArgumentException(sprintf('The route "%s" does not exist.', $name));
            }
        }

        return true;
    }

    protected function getBaseUrl()
    {
        if (!empty($this->config['base_url'])) {
            return $this->config['base_url'];
        }

        $context = $this->router->getContext();

        return sprintf('%s://%s%s', $context->getScheme(), $context->getHost(), $context->getPathInfo());
    }
}
