<?php
declare(strict_types=1);

require '../vendor/autoload.php';

$client = new \Attlaz\Client('6as&01LW!iVe!wO7Guv%5#MlfZ2SJgSG', '#zqtn*4IKcx7iNM4bNvc$XU@H27prch8');
//$client->setEndPoint('https://api2.attlaz.com');
$client->setEndPoint('https://3604-82-30-226-152.ngrok.io');
//$client = new \Attlaz\Client('http://10.0.75.1:8080/', '6as&01LW!iVe!wO7Guv%5#MlfZ2SJgSG', '#zqtn*4IKcx7iNM4bNvc$XU@H27prch8');
$client->enableDebug();

$logEntry = new \Attlaz\Model\LogEntry(new \Attlaz\Model\LogStreamId('test:php-client'), 'TEST API 3 ' . generateRandomString(500), 'info', new DateTime('now'));

try {
    $result = $client->getLogEndpoint()->saveLog($logEntry);
    var_dump($result);
} catch (Exception $ex) {
    echo 'Whoops: ' . $ex->getMessage();
}

function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
}
