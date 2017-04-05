<?php

namespace Keboola\DockerDemo;

use Keboola\SapiMergedExport\App;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

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

        $app = new App();
        $app->run($inputTablesDir, $outputFilesDir);

        $this->assertCount(1, $finder->files()->in($outputFilesDir));
    }
}
