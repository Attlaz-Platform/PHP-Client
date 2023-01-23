<?php
declare(strict_types=1);

namespace Attlaz;


use Attlaz\Model\Log\LogEntry;
use Attlaz\Model\Log\LogStreamId;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    private array $endpoints = [
//        'https://api.attlaz.com',
//        'https://api.attlaz.com/1.6',
//        'https://api.attlaz.com/1.7',
//        'https://api.attlaz.com/1.8',
'https://api.attlaz.com/1.9',
//        'https://api.attlaz.com/beta'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $dotenv = Dotenv::createImmutable(\dirname(__DIR__));
        $dotenv->load();
    }

    public function testWriteItem()
    {

        $client = new Client($_ENV['api_client_id'], $_ENV['api_client_secret']);
        $client->enableDebug();


        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);


            $logEntry = new LogEntry(new LogStreamId('test:php-client'), 'TEST API 3 ' . $this->generateRandomString(500), 'info', new \DateTime('now'));

            //    try {
            $result = $client->getLogEndpoint()->saveLog($logEntry);

            $this->assertNotEmpty($result->id);
            var_dump($result);
//            } catch (\Exception $ex) {
//                echo 'Whoops: ' . $ex->getMessage();
//            }
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

