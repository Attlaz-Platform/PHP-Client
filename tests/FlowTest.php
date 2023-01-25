<?php
declare(strict_types=1);

namespace Attlaz;

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

        $endpoints = [
//            'https://api.attlaz.com',
//            'https://api.attlaz.com/1.6',
//            'https://api.attlaz.com/1.7',
//            'https://api.attlaz.com/1.8',
//            'https://api.attlaz.com/beta'
'https://55af-188-211-160-246.ngrok.io'
        ];
        foreach ($endpoints as $endpoint) {
            $client = new Client(\getenv('api_client_id'), \getenv('api_client_secret'), true);
            $client->setEndPoint($endpoint);


            $result = $client->requestTaskExecution('123JKhnLyN5nS4eY9GD2JhEbEbm', ['message' => 'blaat']);
            var_dump($result);
        }
//        $client = new Client(\getenv('api_client_id'), \getenv('api_client_secret'));

//$client->enableDebug();


//$result = $client->scheduleProjectTask('vijgeblad', 'get-realtime-stock', []);
//var_dump($result);
    }

    public function testRunFlow()
    {
        $endpoints = [
//            'https://api.attlaz.com',
//            'https://api.attlaz.com/1.6',
//            'https://api.attlaz.com/1.7',
//            'https://api.attlaz.com/1.8',
//'https://api.attlaz.com/beta'
'https://55af-188-211-160-246.ngrok.io'
        ];
        foreach ($endpoints as $endpoint) {
            $client = new Client(\getenv('api_client_id'), \getenv('api_client_secret'), true);
            $client->setEndPoint($endpoint);
            //    $client->enableDebug();
            //
            $result = $client->requestTaskExecution('123JKhnLyN5nS4eY9GD2JhEbEbm', ['message' => 'test'], '0liREOSzIzzqY2tIiSA71ZWKArz');

            $this->assertTrue($result->isSuccess());
//            var_dump($result);
        }

    }
}
