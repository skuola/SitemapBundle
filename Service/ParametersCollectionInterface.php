<?php

namespace Skuola\SitemapBundle\Service;

interface ParametersCollectionInterface
{
    /**
     * $collection = [
     *      [
     *         'slug' => 'test-slug'
     *      ],
     *      [
     *          'slug' => 'test-slug-1'
     *      ]
     * ]
     *
     * @return array
     */
    public function getParametersCollection();
}
