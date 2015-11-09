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
    db_driver: orm
    routes:
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
