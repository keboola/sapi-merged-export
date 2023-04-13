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
            ->validate()
                ->ifTrue(function ($v) {
                    return $v['oneCompressFile'] && !$v['doCompression'];
                })
                ->thenInvalid('Cannot use "oneCompressFile" without "doCompression".')
            ->end()
            ->children()
                ->booleanNode('oneCompressFile')->defaultFalse()->end()
                ->booleanNode('doCompression')->defaultTrue()->end()
            ->end()
        ;

        // @formatter:on
        return $parametersNode;
    }
}
