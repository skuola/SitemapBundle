<?php

namespace Skuola\SitemapBundle\Command;

use Symfony\Component\Routing\RouterInterface;
use samdark\sitemap\Index;
use samdark\sitemap\Sitemap;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\Common\Persistence\ObjectManager;

class SitemapGeneratorCommand extends Command
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
        $start = time();

        $this->validateRoutes($this->config['routes']);

        $formatter = $this->getHelper('formatter');

        $this->router->getContext()->setScheme($this->config['scheme']);
        $this->router->getContext()->setHost($this->config['host']);

        $sitemapWriter = new Sitemap($this->config['path']);

        $sitemapWriter = $this->generateSitemapFromRoutes($this->config['routes'], $sitemapWriter);

        $output->writeln($formatter->formatBlock(['[Info]', count($sitemapWriter->getSitemapUrls($this->getBaseUrl())) . ' sitemap will be generated'], 'info', true));

        $sitemapWriter->write();

        $output->writeln($formatter->formatBlock(['[Info]', 'Generating Sitemap index'], 'info', true));

        $this->generateSitemapsIndex($sitemapWriter);

        $output->writeln($formatter->formatBlock(['[Info]', 'Mission Accomplished in '. (time() - $start) . ' s'], 'info', true));
    }

    public function generateSitemapFromRoutes(array $routes, Sitemap $sitemapWriter)
    {
        foreach ($routes as $name => $config) {
            $routeParams = array_keys($config['options']);

            $priority = $config['priority'];
            $changefreq = $config['changefreq'];

            if (empty($routeParams)) {
                $sitemapWriter->addItem($this->router->generate($name, [], true), null, $changefreq, $priority);
            } else {
                $values = $this->getValuesAttributes($config['options']);
                $combinations = $this->generateCombinations($values);

                foreach ($combinations as $combination) {
                    if (!is_array($combination)) {
                        $combination = [$combination];
                    }

                    $params = array_combine($routeParams, $combination);

                    $sitemapWriter->addItem($this->router->generate($name, $params, true), null, $changefreq, $priority);
                }
            }
        }

        return $sitemapWriter;
    }

    public function getValuesAttributes($routeParams)
    {
        $values = [];

        foreach ($routeParams as $params) {
            if (empty($params['repository'])) {
                $values[] = $params['defaults'];

                continue;
            }

            $repositoryOptions = $params['repository'];

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
                        $this->objectManager->getRepository($repositoryOptions['object'])->$repositoryOptions['method']()
                    ),
                    $params['defaults']
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

    protected function generateSitemapsIndex(Sitemap $sitemapWriter)
    {
        $sitemapIndex = new Index($this->config['index_path']);

        $sitemapFileUrls = $sitemapWriter->getSitemapUrls($this->getBaseUrl());

        foreach ($sitemapFileUrls as $sitemapUrl) {
            $sitemapIndex->addSitemap($sitemapUrl);
        }

        return $sitemapIndex->write();
    }

    protected function validateRoutes($definedRoutes)
    {
        foreach($definedRoutes as $name => $info) {
            if (!$this->router->getRouteCollection()->get($name)) {
                throw new InvalidConfigurationException(sprintf('The route "%s" does not exist.', $name));
            }
        }

        return true;
    }

    protected function getBaseUrl()
    {
        $context = $this->router->getContext();

        return sprintf('%s://%s%s', $context->getScheme(), $context->getHost(), $context->getPathInfo());
    }
}
