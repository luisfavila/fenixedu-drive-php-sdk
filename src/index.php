<?php

if (!class_exists('FenixEdu\Drive\Client')) {
    $loader = require __DIR__ . '/../vendor/autoload.php';
    $loader->addPsr4('FenixEdu\\Drive\\', __DIR__);
}

/*

use FenixEdu\Drive\Client;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

new Client();
$client = Client::getInstance();

//

//$client->download("570023764584050");
//echo

$client->authenticate("ist424801");
print_r($client->info("570028059993888"));
echo "<br><br>";
print_r($client->upload("570028059993888", "C:/xampp/htdocs/drive/ficheiro.png"));
*/