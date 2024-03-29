<?php

namespace Keboola\SapiMergedExport;

use Keboola\Component\JsonHelper;
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
        $inputTablesDir = $dataDir . '/in/tables';
        $outputFilesDir = $dataDir . '/out/files';
        $fs->mkdir([$inputTablesDir, $outputFilesDir]);
        JsonHelper::writeFile($dataDir . '/config.json', []);

        // create test files
        $numberOfRows = 10000000;
        $this->generateLargeFile($fs, $inputTablesDir . '/in.c-main.test.csv', $numberOfRows);

        $process = Process::fromShellCommandline('php /code/src/run.php');
        $process->setEnv([
            'KBC_DATADIR' => $dataDir,
        ]);
        $process->mustRun();
        $this->assertEquals(0, $process->getExitCode());

        $foundFiles = $finder->files()->in($outputFilesDir);
        $this->assertCount(2, $foundFiles);

        $gzFiles = $foundFiles->name('*.gz');
        $filesIterator = $gzFiles->getIterator();
        $filesIterator->rewind();
        $this->assertEquals('in.c-main.test.csv.gz', $filesIterator->current()->getBasename());

        // un-gzip and check content
        $process = Process::fromShellCommandline(sprintf("gzip -d %s", escapeshellarg($filesIterator->current()->getRealPath())));
        $process->mustRun();

        // lines count
        $outputLinesCount = (int)(Process::fromShellCommandline(sprintf("wc -l %s", escapeshellarg($outputFilesDir . '/in.c-main.test.csv'))))
            ->mustRun()
            ->getOutput();

        $this->assertEquals($numberOfRows + 1, $outputLinesCount);
    }

    public function testMultiFilesCompress(): void
    {

        // create data dirs
        $fs = new Filesystem();
        $finder = new Finder();
        $dataDir = sys_get_temp_dir() . '/test-data2';
        $fs->remove($dataDir);
        $fs->mkdir($dataDir);

        $fs->mkdir($dataDir . '/in');
        $fs->mkdir($dataDir . '/out');
        $inputTablesDir = $dataDir . '/in/tables';
        $outputFilesDir = $dataDir . '/out/files';
        $fs->mkdir([$inputTablesDir, $outputFilesDir]);
        JsonHelper::writeFile($dataDir . '/config.json', [
            'parameters' => [
                'oneCompressFile' => true,
            ],
        ]);

        // create test files
        $numberOfRows = 10000;

        for ($i = 0; $i < 5; $i++) {
            $this->generateLargeFile($fs, $inputTablesDir . '/in.c-main.test' . $i . '.csv', $numberOfRows);
        }

        $process = Process::fromShellCommandline('php /code/src/run.php');
        $process->setEnv([
            'KBC_DATADIR' => $dataDir,
        ]);
        $process->mustRun();
        $this->assertEquals(0, $process->getExitCode());

        $foundFiles = $finder->files()->in($outputFilesDir);
        $this->assertCount(2, $foundFiles);

        $gzFiles = $foundFiles->name('*.tar.gz');
        $filesIterator = $gzFiles->getIterator();
        $filesIterator->rewind();
        $this->assertEquals('output.tar.gz', $filesIterator->current()->getBasename());

        // un-gzip and check content
        $process = Process::fromShellCommandline(
            sprintf(
                'cd %s;tar -xf %s',
                $outputFilesDir,
                escapeshellarg($filesIterator->current()->getRealPath())
            )
        );
        $process->mustRun();

        for ($i = 0; $i < 5; $i++) {
            // lines count
            $process = Process::fromShellCommandline(
                sprintf(
                    "wc -l %s",
                    escapeshellarg($outputFilesDir . '/in.c-main.test' . $i . '.csv')
                )
            );
            $outputLinesCount = (int) $process
                ->mustRun()
                ->getOutput();

            $this->assertEquals($numberOfRows + 1, $outputLinesCount);
        }
    }

    public function testNonCompressFile(): void
    {
        // create data dirs
        $fs = new Filesystem();
        $finder = new Finder();
        $dataDir = sys_get_temp_dir() . '/test-data2';
        $fs->remove($dataDir);
        $fs->mkdir($dataDir);

        $fs->mkdir($dataDir . '/in');
        $fs->mkdir($dataDir . '/out');
        $inputTablesDir = $dataDir . '/in/tables';
        $outputFilesDir = $dataDir . '/out/files';
        $fs->mkdir([$inputTablesDir, $outputFilesDir]);
        JsonHelper::writeFile($dataDir . '/config.json', [
            'parameters' => [
                'doCompression' => false,
                'oneCompressFile' => false,
            ],
        ]);

        // create test files
        $numberOfRows = 100;

        for ($i = 0; $i < 5; $i++) {
            $this->generateLargeFile($fs, $inputTablesDir . '/in.c-main.test' . $i . '.csv', $numberOfRows);
        }

        $process = Process::fromShellCommandline('php /code/src/run.php');
        $process->setEnv([
            'KBC_DATADIR' => $dataDir,
        ]);
        $process->mustRun();
        $this->assertEquals(0, $process->getExitCode());

        $foundFiles = $finder->files()->in($outputFilesDir);
        $this->assertCount(10, $foundFiles);

        for ($i = 0; $i < 5; $i++) {
            // lines count
            $process = Process::fromShellCommandline(
                sprintf(
                    "wc -l %s",
                    escapeshellarg($outputFilesDir . '/in.c-main.test' . $i . '.csv')
                )
            );
            $outputLinesCount = (int) $process
                ->mustRun()
                ->getOutput();

            $this->assertEquals($numberOfRows + 1, $outputLinesCount);
        }
    }
    
    private function generateLargeFile(Filesystem $fs, $filePath, $numberOfRows): void
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
