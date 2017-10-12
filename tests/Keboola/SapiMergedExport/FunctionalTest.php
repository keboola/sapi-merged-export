<?php

namespace Keboola\SapiMergedExport;

use Keboola\SapiMergedExport\App;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class FunctionalTest extends TestCase
{

    public function testApp()
    {
        // create data dirs
        $fs = new Filesystem();
        $finder = new Finder();
        $dataDir = sys_get_temp_dir() . '/test-data';
        $fs->mkdir($dataDir);

        $fs->mkdir($dataDir . '/in');
        $fs->mkdir($dataDir . '/out');
        $inputTablesDir = $dataDir. '/in/tables';
        $outputFilesDir = $dataDir . '/out/files';
        $fs->mkdir([$inputTablesDir, $outputFilesDir]);

        // create test files
        $numberOfRows = 10000000;
        $this->generateLargeFile($fs, $inputTablesDir . '/in.c-main.test.csv', $numberOfRows);

        $process = new Process(
            sprintf("php /code/src/run.php --data=%s", escapeshellarg($dataDir))
        );
        $process->mustRun();
        $this->assertEquals(0, $process->getExitCode());

        $foundFiles = $finder->files()->in($outputFilesDir);
        $this->assertCount(2, $foundFiles);

        $gzFiles = $foundFiles->name('*.gz');
        $filesIterator = $gzFiles->getIterator();
        $filesIterator->rewind();
        $this->assertEquals('in.c-main.test.csv.gz', $filesIterator->current()->getBasename());

        // un-gzip and check content
        $process = new Process(sprintf("gzip -d %s", escapeshellarg($filesIterator->current()->getRealPath())));
        $process->mustRun();

        // lines count
        $outputLinesCount = (int) (new Process(sprintf("wc -l %s", escapeshellarg($outputFilesDir . '/in.c-main.test.csv'))))
            ->mustRun()
            ->getOutput();

        $this->assertEquals($numberOfRows + 1, $outputLinesCount);
    }

    private function generateLargeFile(Filesystem $fs, $filePath, $numberOfRows)
    {
        $fs->dumpFile($filePath, "id,text,some_other_column\n");

        for ($i = 0; $i < $numberOfRows; $i++) {
            $fs->appendToFile(
                $filePath,
                sprintf('"%s","%s","%s"', rand(), rand(), rand()) . "\n"
            );
        }
    }
}
