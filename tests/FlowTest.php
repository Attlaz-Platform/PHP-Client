<?php
declare(strict_types=1);

namespace Attlaz;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class FlowTest extends TestCase
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

    public function testRealTime()
    {

        $client = new Client();
        $client->authWithClient($_ENV['api_client_id'], $_ENV['api_client_secret']);
        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);


            $randomMessage = 'Hello - ' . random_int(0, 100);
            $arguments = ['message' => $randomMessage];
            $result = $client->getFlowEndpoint()->requestRunFlow('BEXG88ATH', $arguments);

            $this->assertTrue($result->isSuccess());
            $this->assertEquals($randomMessage, $result->result['message']);
        }
//        $client = new Client($_ENV['api_client_id'], $_ENV['api_client_secret']);

//$client->enableDebug();


//$result = $client->scheduleProjectTask('vijgeblad', 'get-realtime-stock', []);
//var_dump($result);
    }

    public function testRunFlow()
    {

        $client = new Client();
        $client->authWithClient($_ENV['api_client_id'], $_ENV['api_client_secret']);

        foreach ($this->endpoints as $endpoint) {

            $client->setEndPoint($endpoint);
            //    $client->enableDebug();
            //
            $input = 'Rand-' . random_int(9999999, 9999999999);

            $result = $client->getFlowEndpoint()->requestRunFlow('123JKhnLyN5nS4eY9GD2JhEbEbm', ['message' => $input], '12mmUlGH16cBCs5Xuswirp7UYAX');


            $this->assertTrue($result->isSuccess());
            $this->assertEquals($input, $result->result['message']);
        }

    }
}
