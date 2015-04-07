#!/usr/bin/env php
<?php
require realpath(__DIR__.'/..').'/vendor/autoload.php';

$app = new \Freefeed\Website\Application();
$app->boot();

$api = new \Freefeed\Clio\Api($app->getSettings()['clio_api']);

$model = new \Freefeed\Website\Models\User($app);
foreach ($model->listUnvalidatedAccountsWithEmails() as $row) {
    $login = $row['friendfeed_username'];
    $email = $row['email'];

    echo "{$login} + {$email} = ";

    if ($api->userEmailMatches($login, $email)) {
        $model->validateAccount($row['id']);
        echo "OK\n";
    } else {
        echo "--\n";
    }
}
