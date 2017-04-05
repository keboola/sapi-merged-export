<?php

namespace Keboola\SapiMergedExport;

use Keboola\SapiMergedExport\App;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class AppTest extends TestCase
{

    public function testProcess()
    {
        // create data dirs
        $fs = new Filesystem();
        $finder = new Finder();
        $inputTablesDir = sys_get_temp_dir() . '/input';
        $outputFilesDir = sys_get_temp_dir() . '/output';
        $fs->mkdir([$inputTablesDir, $outputFilesDir]);

        // create test files
        $fs->dumpFile($inputTablesDir . '/in.c-main.test.csv', <<< EOF
id,text,some_other_column
1,"Short text","Whatever"
2,"Long text Long text Long text","Something else"
EOF
        );
        $fs->dumpFile($inputTablesDir . '/in.c-main.test.manifest', 'something');

        $app = new App();
        $app->run($inputTablesDir, $outputFilesDir);

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
