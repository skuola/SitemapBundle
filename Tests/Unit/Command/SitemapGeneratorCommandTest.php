<?php

namespace Skuola\SitemapBundle\Tests\Unit\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Mockery as m;
use samdark\sitemap\Sitemap;
use Skuola\SitemapBundle\Command\SitemapGeneratorCommand;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

class SitemapGeneratorCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var $router
     */
    protected $router;

    /**
     * @var $objectManager
     */
    protected $objectManager;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var SitemapGeneratorCommand
     */
    protected $service;

    public function setUp()
    {
        $this->router = m::mock(RouterInterface::class);
        $this->objectManager = m::mock(ObjectManager::class);
        $this->output = $this->getOutputMock();

        $this->service = new SitemapGeneratorCommand(
            $this->router,
            $this->objectManager,
            []
        );

        $reflectionService = new \ReflectionClass($this->service);

        $reflectionOutput = $reflectionService->getProperty('output');
        $reflectionOutput->setAccessible(true);
        $reflectionOutput->setValue($this->service, $this->output);

        $this->service->setHelperSet(new HelperSet(
            [new FormatterHelper()]
        ));
    }

    protected function getOutputMock()
    {
        $output = m::mock(Output::class);

        $output->shouldReceive('writeln')
            ->andReturn("\n");

        $output->shouldReceive('isDecorated')
            ->andReturn(false);

        $output->shouldReceive('getVerbosity')
            ->andReturn(Output::VERBOSITY_QUIET);

        return $output;
    }

    public function tearDown()
    {
        m::close();
    }

    public function testGenerateSitemapFromRoutesWithObjectRoute()
    {
        $service = m::mock(SitemapGeneratorCommand::class.'[getValuesAttributes,generateCombinations]', [$this->router, $this->objectManager, []]);
        $service->shouldAllowMockingProtectedMethods();

        $reflectionService = new \ReflectionClass($service);

        $reflectionOutput = $reflectionService->getProperty('output');
        $reflectionOutput->setAccessible(true);
        $reflectionOutput->setValue($service, $this->output);

        $service->setHelperSet(new HelperSet(
            [new FormatterHelper()]
        ));

        $sitemap = m::mock(Sitemap::class);
        $routes = [
            'route_name' => [
                'options' => [
                    'param1' => [
                        'defaults' => [0],
                        'repository' => [
                            'object'   => 'TestObject',
                            'property' => 'id'
                        ]
                    ],
                    'param2' => [
                        'repository' => [
                            'object'   => 'Test1Object',
                            'property' => 'id'
                        ]
                    ],
                ],
                'lastmod'  => (new \DateTime('now'))->getTimestamp(),
                'changefreq' => Sitemap::WEEKLY,
                'priority' => '0.8'
            ]
        ];

        $service->shouldReceive('getValuesAttributes')
                ->once()
                ->andReturn([['0','1', '2'], ['a', 'b']]);

        $service->shouldReceive('generateCombinations')
                ->once()
                ->andReturn([['1', 'a'], ['1', 'b'], ['2', 'a'], ['2', 'b']]);

        $this->router->shouldReceive('generate')
            ->times(4)
            ->andReturn('a', 'b', 'c', 'd');

        $sitemap->shouldReceive('addItem')
                ->times(4)->with(m::anyOf('a', 'b', 'c', 'd'), (new \DateTime('now'))->getTimestamp(), Sitemap::WEEKLY, '0.8');

        $sitemap->shouldReceive('setMaxUrls')->andReturn();

        $reflectionMethod = new \ReflectionMethod($service, 'generateSitemapFromRoutes');
        $reflectionMethod->setAccessible(true);

        $this->assertInstanceOf(
            Sitemap::class,
            $reflectionMethod->invoke($service, $sitemap, $routes)
        );
    }

    public function testGenerateSitemapFromRoutesWithStaticRoute()
    {
        $sitemap = m::mock(Sitemap::class);
        $routes = ['route_name' => ['options' => [], 'lastmod' => (new \DateTime('now'))->getTimestamp(), 'changefreq' => Sitemap::WEEKLY, 'priority' => '0.8']];

        $this->router->shouldReceive('generate')
            ->once()->with('route_name', [], true)
            ->andReturn('http://valid.route');

        $sitemap->shouldReceive('addItem')->once()->with('http://valid.route', (new \DateTime('now'))->getTimestamp(), $routes['route_name']['changefreq'], $routes['route_name']['priority']);
        $sitemap->shouldReceive('setMaxUrls')->andReturn();

        $reflectionMethod = new \ReflectionMethod($this->service, 'generateSitemapFromRoutes');
        $reflectionMethod->setAccessible(true);

        $this->assertInstanceOf(
            Sitemap::class,
            $reflectionMethod->invoke($this->service, $sitemap, $routes)
        );
    }

    /**
     * @dataProvider arraysProviders
     * @param array $firstArray
     * @param array $secondArray
     * @param array $expected
     */
    public function testGenerateCombinations($firstArray, $secondArray, $expected)
    {
        $reflectionMethod = new \ReflectionMethod($this->service, 'generateCombinations');
        $reflectionMethod->setAccessible(true);

        $this->assertEquals($expected, $reflectionMethod->invoke($this->service, [$firstArray, $secondArray]));
    }

    /**
     * @return array
     */
    public function arraysProviders()
    {
        return [
            [['a', 'b', 'c'], ['a', 'b'], [['a', 'a'], ['a', 'b'], ['b', 'a'], ['b', 'b'], ['c', 'a'], ['c', 'b']]],
            [['matematica', 'informatica'], ['torino', 'roma', 'firenze'], [['matematica', 'torino'], ['matematica', 'roma'], ['matematica', 'firenze'], ['informatica', 'torino'], ['informatica', 'roma'], ['informatica', 'firenze']]],
            [[], [], []],
            [['matematica'], [], []],
            [['matematica'], ['torino'], [['matematica', 'torino']]]
        ];
    }
}
