<?php
declare(strict_types=1);

namespace Attlaz;


use Attlaz\Model\StorageItem;
use PHPUnit\Framework\TestCase;

class FlowTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $dotenv = new \Dotenv();
        $dotenv->load(\dirname(__DIR__));
    }

    public function testRealTime()
    {
        $client = new \Attlaz\Client(\getenv('api_client_id'), \getenv('api_client_secret'));

//$client->enableDebug();

        $result = $client->scheduleTask('E823A9A83');
        var_dump($result);

//$result = $client->scheduleProjectTask('vijgeblad', 'get-realtime-stock', []);
//var_dump($result);
    }

    public function testRunFlow()
    {
        $endpoints = [
            'https://api.attlaz.com',
            'https://api2.attlaz.com',
        ];
        foreach ($endpoints as $endpoint) {
            //$client = new \Attlaz\Client('https://api2.attlaz.com', 'democlient', 'democlientsecret', true);
            $client = new \Attlaz\Client(\getenv('api_client_id'), \getenv('api_client_secret'), true);
            $client->setEndPoint($endpoint);
            //    $client->enableDebug();
            //
            $result = $client->requestTaskExecution('C55B0E4CB', [], '55');
            var_dump($result);
        }

    }
}
