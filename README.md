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

##Configuration

``` yml
# app/config/config.yml
skuola_sitemap:
    scheme: http
    host: www.skuola.net
    db_driver: orm # orm|mongodb
    # If you want to specify a custom base url for sitemap_index    
    # base_url: http://www.skuola.net/univerista
    base_url: ~ # http://www.skuola.net
    routes:
        category_show:
            parameters:
                slug:
                    repository:
                        object: SkuolaTestBundle:Category
                        property: slug
                        method: findPublic
                type:
                    defaults: ["free", "open-source", "premium"]
            changefreq: weekly
            priority: 0.5
        tag_show:
            parameters:
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
