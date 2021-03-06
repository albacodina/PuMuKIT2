<?php

namespace Pumukit\WebTVBundle\Twig;

/**
 * MongoDate::toDateTime is only available in PECL mongo >= 1.6.0.
 *
 * PuMuKIT 2.3 must be compatible with PECL mongo 1.4.5 (Ubuntu 14.04).
 */
class MongoDateExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('convertMongoDate', array($this, 'convertMongoDateFilter')),
        );
    }

    public function convertMongoDateFilter(\MongoDate $mongoDate)
    {
        return new \DateTime('@'.$mongoDate->sec);
    }
}
