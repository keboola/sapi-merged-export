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
        $fs->dumpFile($inputTablesDir . '/in.c-main.test.csv', <<< EOF
id,text,some_other_column
1,"Short text","Whatever"
2,"Long text Long text Long text","Something else"
EOF
        );

        $process = new Process(
            sprintf("php /code/src/run.php --data=%s", escapeshellarg($dataDir))
        );
        $process->mustRun();
        $this->assertEquals(0, $process->getExitCode());

        $foundFiles = $finder->files()->in($outputFilesDir);
        $this->assertCount(1, $foundFiles);

        $filesIterator = $foundFiles->getIterator();
        $filesIterator->rewind();
        $this->assertEquals('in.c-main.test.csv.gz', $filesIterator->current()->getBasename());

        // un-gzip and check content
        $process = new Process(sprintf("gzip -d %s", escapeshellarg($filesIterator->current()->getRealPath())));
        $process->mustRun();
        $this->assertEquals(file_get_contents($inputTablesDir . '/in.c-main.test.csv'), file_get_contents($outputFilesDir . '/in.c-main.test.csv'));
    }
}
