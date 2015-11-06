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
        legacy_route: ~
        my_route_city:
            route_params:
                city: { object: SkuolaDemoBundle:City, prop: slug }
            changefreq: weekly
            priority: 0.8
        my_route_user_subject:
            route_params:
                user: { object: SkuolaDemoBundle:User, prop: username }
                subject: { object: SkuolaDemoBundle:Subject, prop: slug }
            changefreq: weekly
            priority: 0.5
```
