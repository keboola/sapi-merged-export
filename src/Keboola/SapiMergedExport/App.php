<?php
/**
 * Created by PhpStorm.
 * User: martinhalamicek
 * Date: 05/04/17
 * Time: 08:23
 */

namespace Keboola\SapiMergedExport;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class App
{
    private $finder;
    private $fileSystem;

    public function __construct()
    {
        $this->finder = new Finder();
        $this->fileSystem = new Filesystem();
    }

    public function run($inputTablesFolderPath, $outputFilesFolderPath)
    {
        array_map(function (SplFileInfo $file) use ($outputFilesFolderPath) {
            $this->processFile($file, $outputFilesFolderPath);
        }, iterator_to_array($this->finder->files()->in($inputTablesFolderPath)));
    }

    private function processFile(SplFileInfo $file, $outputFilesFolderPath)
    {
        $this->fileSystem->copy($file->getRealPath(), $outputFilesFolderPath . '/' . $file->getBasename());
    }
}
