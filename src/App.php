<?php

namespace Keboola\SapiMergedExport;

use Keboola\Component\BaseComponent;
use Keboola\SapiMergedExport\Configuration\Config;
use Keboola\SapiMergedExport\Configuration\ConfigDefinition;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;

class App extends BaseComponent
{
    protected function run(): void
    {
        $inputTablesFolderPath = $this->getDataDir() . '/in/tables';
        $outputFilesFolderPath = $this->getDataDir() . '/out/files';

        if ($this->getConfig()->oneCompressFile()) {
            $this->processFiles($inputTablesFolderPath, $outputFilesFolderPath);
        } else {
            $finder = new Finder();
            $tables = $finder->files()->in($inputTablesFolderPath)->name('*.csv');
            foreach ($tables as $table) {
                $this->processFile($table, $outputFilesFolderPath);
            }
        }
    }

    private function processFiles(string $inputTablesFolderPath, string $outputFilesFolderPath): void
    {
        $outputFileName = 'output.tar.gz';
        $cmd = sprintf(
            'cd %s; tar -czf %s/%s *.csv',
            $inputTablesFolderPath,
            $outputFilesFolderPath,
            $outputFileName
        );

        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(null);
        $process->mustRun();

        $manifestFileName = sprintf(
            '%s/%s.manifest',
            $outputFilesFolderPath,
            $outputFileName
        );
        $fileSystem = new Filesystem();
        $fileSystem->dumpFile($manifestFileName, json_encode([
            'is_encrypted' => true,
            'tags' => [
                'storage-merged-export',
            ],
        ]));
    }

    private function processFile(SplFileInfo $file, $outputFilesFolderPath): void
    {
        print sprintf("Processing file: %s\n", $file->getBasename());
        $outputPath = $outputFilesFolderPath . '/' . $file->getBasename();
        $compressedPathBase = $file->getRealPath();

        $cmd = sprintf(
            "gzip --fast %s",
            escapeshellarg($file->getRealPath())
        );
        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(null);
        $process->mustRun();

        $fileSystem = new Filesystem();
        $fileSystem->rename($compressedPathBase . '.gz', $outputPath . '.gz');
        $fileSystem->dumpFile($outputPath . '.gz.manifest', json_encode([
            'is_encrypted' => true,
            'tags' => [
                'storage-merged-export',
            ],
        ]));
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    public function getConfig(): Config
    {
        /** @var Config $config */
        $config = parent::getConfig();
        return $config;
    }
}
