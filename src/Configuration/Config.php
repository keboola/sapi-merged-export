<?php

declare(strict_types=1);

namespace Keboola\SapiMergedExport\Configuration;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function oneCompressFile(): bool
    {
        return $this->getValue(['parameters', 'oneCompressFile'], false);
    }

    public function doCompression(): bool
    {
        return $this->getValue(['parameters', 'doCompression'], true);
    }
}
