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
        'https://gateway.api.attlaz.com',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $dotenv = Dotenv::createImmutable(\dirname(__DIR__));
        $dotenv->load();
    }

    public function testWriteItem()
    {

        $client = new Client();
        $client->authWithClient($_ENV['api_client_id'], $_ENV['api_client_secret']);
        $client->setDebug(1);


        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);


            // The shared test log stream (Zone.EU_ZONE.TEST_LOG_STREAM in the core library). The old
            // `test:php-client` form was retired in May 2025 when log stream ids stopped carrying a
            // type prefix, and the API no longer accepts it.
            $logEntry = new LogEntry(new LogStreamId('lst_0nAQnvxYSscFpzE04eVD6VfVfJY'), 'TEST API 3 ' . $this->generateRandomString(500), 'info', new \DateTime('now'));

            //    try {
            $result = $client->getLogEndpoint()->saveLog($logEntry);

            $this->assertNotEmpty($result->id);

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
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

}

