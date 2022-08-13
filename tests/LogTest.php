<?php
declare(strict_types=1);

namespace Attlaz;


use Attlaz\Model\Log\LogEntry;
use Attlaz\Model\Log\LogStreamId;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $dotenv = new \Dotenv();
        $dotenv->load(\dirname(__DIR__));
    }

    public function testWriteItem()
    {

        $client = new \Attlaz\Client(\getenv('api_client_id'), \getenv('api_client_secret'));
//$client->setEndPoint('https://api2.attlaz.com');
        $client->setEndPoint('https://api.attlaz.com');
//$client = new \Attlaz\Client('http://10.0.75.1:8080/', '6as&01LW!iVe!wO7Guv%5#MlfZ2SJgSG', '#zqtn*4IKcx7iNM4bNvc$XU@H27prch8');
        $client->enableDebug();

        $logEntry = new LogEntry(new LogStreamId('test:php-client'), 'TEST API 3 ' . $this->generateRandomString(500), 'info', new \DateTime('now'));

        try {
            $result = $client->getLogEndpoint()->saveLog($logEntry);

            $this->assertNotEmpty($result->id);
            var_dump($result);
        } catch (\Exception $ex) {
            echo 'Whoops: ' . $ex->getMessage();
        }


    }

    private function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

}

