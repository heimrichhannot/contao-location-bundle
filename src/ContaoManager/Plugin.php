<?php

namespace HeimrichHannot\LocationBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use HeimrichHannot\LocationBundle\HeimrichHannotLocationBundle;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        $loadAfter = [
            ContaoCoreBundle::class
        ];

        return [
            BundleConfig::create(HeimrichHannotLocationBundle::class)->setLoadAfter($loadAfter)
        ];
    }
}
