# SitemapBundle
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/e8e5c5e8-8536-4632-8528-796d99ed54fd/mini.png)](https://insight.sensiolabs.com/projects/e8e5c5e8-8536-4632-8528-796d99ed54fd)
[![Build Status](https://travis-ci.org/skuola/SitemapBundle.svg?branch=master)](https://travis-ci.org/skuola/SitemapBundle)

##Installation

Install the bundle:

    composer require skuola/sitemap-bundle

Register the bundle in `app/AppKernel.php`:

``` php
<?php
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new Skuola\SitemapBundle\SkuolaSitemapBundle()
    );
}
```

##Basic Configuration

``` yml
# app/config/config.yml
skuola_sitemap:
    scheme: http
    host: www.example.com
    db_driver: orm # orm|mongodb
    sitemaps:
        FirstSitemap:    
            index:
                # If you want to specify a custom base url for sitemap_index
                base_url: ~ # Or your custom base url: http://%domain%/sitemaps/home
                path: ~ # %kernel.root_dir%/../web/sitemap_index.xml
            path: ~ # %kernel.root_dir%/../web/sitemap.xml
            routes:
                category_show:
                    options:
                        slug:
                            repository:
                                object: SkuolaTestBundle:Category
                                property: slug
                                method: findPublic
                        type:
                            defaults: ["free", "open-source", "premium"]
                    changefreq: weekly
                    priority: 0.5
                open_source_post:
                    options:
                        slug:
                            repository:
                                object: SkuolaTestBundle:Category
                                property: slug
                                method: findBySlug
                                #Call findWithSlug($slug) method with custom arguments
                                arguments: ["open-source"]
                    changefreq: weekly
                    priority: 0.3
                tag_show:
                    options:
                        slug:
                            repository:
                                object: SkuolaTestBundle:Tag
                                property: slug
                        type:
                            repository:
                                object: SkuolaTestBundle:Type
                                property: id
                                method: findEnabled
                             #merge repository results with defaults options   
                            defaults: [0]
                    changefreq: weekly
                    priority: 0.8
```

##Multi Sitemaps

``` yml
skuola_sitemap:
    scheme: http
    host: www.example.com
    db_driver: orm
    sitemaps:
        Blog:
            index:
                base_url: http://www.example.com/sitemaps/home
                path: %kernel.root_dir%/../web/shared/sitemaps/home/sitemap_index.xml
            path: %kernel.root_dir%/../web/shared/sitemaps/home/sitemap.xml
            routes:
                ...
        Store:
            index:
                base_url: http://www.example.com/sitemaps/store
                path: %kernel.root_dir%/../web/shared/sitemaps/store/sitemap_index.xml
            path: %kernel.root_dir%/../web/shared/sitemaps/store/sitemap.xml
            routes:
                ...
```

##Configuration with custom service:

###Example
Routing

``` yml
# app/config/test_routing.yml
page_show: 
    path: /{category_slug}/{page_slug}
```

Configuration
``` yml
# app/config/config.yml
skuola_sitemap:
    scheme: http
    host: www.example.com
    db_driver: orm
    sitemaps:
        FirstSitemap:
            routes:
                page_show:
                    provider: skuola_testbundle.sitemap.page_provider
                    changefreq: weekly
                    priority: 0.5
```

Create your generator service, implements `Skuola\SitemapBundle\Service\ParametersCollectionInterface`

``` yml
# src/TestBundle/Resources/config/services.yml
services:
  skuola_testbundle.sitemap.page_provider:
      class: Skuola\TestBundle\Service\Sitemap\PageProvider
      arguments: [@doctrine.orm.entity_manager]
```

Create `PageProvider` class

``` php
use Skuola\SitemapBundle\Service\ParametersCollectionInterface;
class PageProvider implements ParametersCollectionInterface {
    protected $entityManager;
    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }
    //Implement getParametersCollection()
    public function getParametersCollection() {
        $collection = [];
        $pages = $this->entityManager->getRepository('Page')->findAll();
        foreach($pages as $page) {
            $collection[] = [
               'category_slug' => $page->getCategory()->getSlug(),
               'page_slug'     => $page->getSlug()
            ]
        }
        return $collection;
    }
}
```

Run

`app/console sitemap:generator`

Run single Sitemap

`app/console sitemap:generator --name "FirstSitemap"`

![SitemapGeneratorCommand](https://cloud.githubusercontent.com/assets/5167596/11930198/ad15746c-a7e1-11e5-869d-6328c26bc7e4.png)
