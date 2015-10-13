# SitemapBundle
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/e8e5c5e8-8536-4632-8528-796d99ed54fd/mini.png)](https://insight.sensiolabs.com/projects/e8e5c5e8-8536-4632-8528-796d99ed54fd)

Installation
------------

Install the bundle:

    composer require skuola/sitemap-bundle "dev-master"

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
