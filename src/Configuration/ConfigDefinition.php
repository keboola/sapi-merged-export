<?php

declare(strict_types=1);

namespace Keboola\SapiMergedExport\Configuration;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->booleanNode('oneCompressFile')->defaultFalse()->end()
            ->end()
        ;

        // @formatter:on
        return $parametersNode;
    }
}
