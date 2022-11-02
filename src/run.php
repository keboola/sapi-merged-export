<?php

declare(strict_types=1);

use Keboola\SapiMergedExport\App;

require_once(dirname(__FILE__) . "/../vendor/autoload.php");

$logger = new Keboola\Component\Logger();

try {
    print "Preparing merged file for upload";
    $app = new App($logger);
    $app->execute();
    print "Preparation done";
} catch (Exception $e) {
    print $e->getMessage();
    exit(2);
}

exit(0);
