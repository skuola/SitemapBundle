# SitemapBundle

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
