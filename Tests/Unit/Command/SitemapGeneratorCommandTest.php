<?php

namespace Skuola\SitemapBundle\Tests\Unit\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Mockery as m;
use samdark\sitemap\Sitemap;
use Skuola\SitemapBundle\Command\SitemapGeneratorCommand;
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
     * @var SitemapGeneratorCommand
     */
    protected $service;

    public function setUp()
    {
        $this->router = m::mock(RouterInterface::class);
        $this->objectManager = m::mock(ObjectManager::class);

        $this->service = new SitemapGeneratorCommand(
            $this->router,
            $this->objectManager,
            []
        );
    }

    public function tearDown()
    {
        m::close();
    }

    public function testGenerateSitemapFromRoutesWithEntityRoute()
    {
        $service = m::mock(SitemapGeneratorCommand::class.'[getEntitiesAttributes,generateCombinations]', [$this->router, $this->objectManager, []]);

        $sitemap = m::mock(Sitemap::class);
        $routes = [
            'route_name' => [
                'route_params' => [
                    'entity1' => ['entity' => 'EntityName1', 'prop' => 'property1'],
                    'entity2' => ['entity' => 'EntityName2', 'prop' => 'property2']
                ],
                'changefreq' => Sitemap::WEEKLY,
                'priority' => '0.8'
            ]
        ];

        $service->shouldReceive('getEntitiesAttributes')
                ->once()
                ->andReturn([['1', '2'], ['a', 'b']]);

        $service->shouldReceive('generateCombinations')
                ->once()
                ->andReturn([['1', 'a'], ['1', 'b'], ['2', 'a'], ['2', 'b']]);

        $this->router->shouldReceive('generate')
            ->times(4)
            ->andReturn('a', 'b', 'c', 'd');

        $sitemap->shouldReceive('addItem')
                ->times(4)->with(m::anyOf('a', 'b', 'c', 'd'), null, Sitemap::WEEKLY, '0.8');

        $this->assertInstanceOf(Sitemap::class, $service->generateSitemapFromRoutes($routes, $sitemap));
    }

    public function testGenerateSitemapFromRoutesWithStaticRoute()
    {
        $sitemap = m::mock(Sitemap::class);
        $routes = ['route_name' => ['route_params' => [], 'changefreq' => Sitemap::WEEKLY, 'priority' => '0.8']];

        $this->router->shouldReceive('generate')
            ->once()->with('route_name', [], true)
            ->andReturn('http://valid.route');

        $sitemap->shouldReceive('addItem')->once()->with('http://valid.route', null, $routes['route_name']['changefreq'], $routes['route_name']['priority']);

        $this->assertInstanceOf(Sitemap::class, $this->service->generateSitemapFromRoutes($routes, $sitemap));
    }

    /**
     * @dataProvider arraysProviders
     * @param array $firstArray
     * @param array $secondArray
     * @param array $expected
     */
    public function testGenerateCombinations($firstArray, $secondArray, $expected)
    {
        $this->assertEquals($expected, $this->service->generateCombinations([$firstArray, $secondArray]));
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
