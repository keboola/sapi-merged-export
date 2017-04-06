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
use Symfony\Component\Process\Process;

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
        }, iterator_to_array($this->finder->files()->in($inputTablesFolderPath)->name('*.csv')));
    }

    private function processFile(SplFileInfo $file, $outputFilesFolderPath)
    {
        print sprintf("Processing file: %s\n", $file->getBasename());
        $outputPath = $outputFilesFolderPath . '/' . $file->getBasename();

        $cmd = sprintf(
            "gzip --fast < %s > %s",
            escapeshellarg($file->getRealPath()),
            escapeshellarg($outputPath . '.gz')
        );
        $process = new Process($cmd);
        $process->mustRun();

        $this->fileSystem->dumpFile($outputPath . '.gz.manifest', json_encode([
            'is_encrypted' => true,
            'tags' => [
                'storage-merged-export',
            ],
        ]));
    }
}
