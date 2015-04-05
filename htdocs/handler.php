<?php
if (php_sapi_name() == 'cli-server') {
    if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|ico)$/', $_SERVER["REQUEST_URI"])) {
        return false;    // serve the requested resource as-is.
    }

    $filename = __DIR__.$_SERVER["SCRIPT_NAME"];

    if (preg_match('/\.(?:woff)(?:\?.+)$/', $_SERVER["REQUEST_URI"])) {
        header('Content-type: application/font-woff');
        $file = file_get_contents($filename);
        header('Content-Length: '.strlen($file));
        echo $file;
        die();
    }

    if (preg_match('/\.(?:eot)(?:\?.+)$/', $_SERVER["REQUEST_URI"])) {
        header('Content-type: application/vnd.ms-fontobject');
        readfile($filename);
        die();
    }

    if (preg_match('/\.(?:ttf)(?:\?.+)$/', $_SERVER["REQUEST_URI"])) {
        header('Content-type: application/octet-stream');
        readfile($filename);
        die();
    }
}

require realpath(__DIR__.'/..').'/vendor/autoload.php';

$app = new \Freefeed\Website\Application();
$app->setupRouting();
$app->run();
