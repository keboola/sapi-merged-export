<?php

namespace Keboola\SapiMergedExport;

use Keboola\Component\JsonHelper;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
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
        $datadir = sys_get_temp_dir();
        $fs->remove($datadir);
        putenv('KBC_DATADIR=' . $datadir);
        $inputTablesDir = $datadir . '/in/tables';
        $outputFilesDir = $datadir . '/out/files';
        JsonHelper::writeFile($datadir . '/config.json', []);
        $fs->mkdir([$inputTablesDir, $outputFilesDir]);

        // create test files
        $fs->dumpFile($inputTablesDir . '/in.c-main.test.csv', <<< EOF
id,text,some_other_column
1,"Short text","Whatever"
2,"Long text Long text Long text","Something else"
EOF
        );
        $initialFileContent = file_get_contents($inputTablesDir . '/in.c-main.test.csv');
        $fs->dumpFile($inputTablesDir . '/in.c-main.test.manifest', 'something');

        $app = new \Keboola\SapiMergedExport\App(new TestLogger());
        $app->execute();

        $foundFiles = $finder->files()->in($outputFilesDir);
        $this->assertCount(2, $foundFiles);

        $gzFiles = $foundFiles->name('*.gz');
        $filesIterator = $gzFiles->getIterator();
        $filesIterator->rewind();
        $this->assertEquals('in.c-main.test.csv.gz', $filesIterator->current()->getBasename());

        // un-gzip and check content
        $process = Process::fromShellCommandline(
            sprintf("gzip -d %s", escapeshellarg($filesIterator->current()->getRealPath()))
        );
        $process->mustRun();
        $this->assertEquals($initialFileContent, file_get_contents($outputFilesDir . '/in.c-main.test.csv'));

        // manifest
        $manifestFiles = $foundFiles->name('*.manifest');
        $filesIterator = $manifestFiles->getIterator();
        $filesIterator->rewind();
        $this->assertEquals('in.c-main.test.csv.gz.manifest', $filesIterator->current()->getBasename());
        $this->assertNotEmpty(json_decode(file_get_contents($filesIterator->current()->getRealPath()))->tags);
    }
}
