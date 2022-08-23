<?php
declare(strict_types=1);

namespace Attlaz;


use Attlaz\Model\Log\LogEntry;
use Attlaz\Model\Log\LogStreamId;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $dotenv = new \Dotenv();
        $dotenv->load(\dirname(__DIR__));
    }

    public function testGet()
    {

        $client = new \Attlaz\Client(\getenv('api_client_id'), \getenv('api_client_secret'));
//$client->setEndPoint('https://api2.attlaz.com');
        $client->setEndPoint('https://api.attlaz.com/1.8');
//$client = new \Attlaz\Client('http://10.0.75.1:8080/', '6as&01LW!iVe!wO7Guv%5#MlfZ2SJgSG', '#zqtn*4IKcx7iNM4bNvc$XU@H27prch8');
        $client->enableDebug();

        $project = $client->getProjectById('0DF2CCCDF');
        $this->assertEquals('0DF2CCCDF', $project->id);
        $this->assertEquals('webshop', $project->key);

        $projectEnvironment = $client->getProjectEnvironmentByKey('0DF2CCCDF', 'production');
        $this->assertEquals('0DF2CCCDF', $projectEnvironment->projectId);
        $this->assertEquals('61', $projectEnvironment->id);
        $this->assertEquals('production', $projectEnvironment->key);

        $projectEnvironments = $client->getProjectEnvironments('0DF2CCCDF');
        foreach ($projectEnvironments as $projectEnvironment) {
            $this->assertEquals('0DF2CCCDF', $projectEnvironment->projectId);
        }

//        $logEntry = new LogEntry(new LogStreamId('test:php-client'), 'TEST API 3 ' . $this->generateRandomString(500), 'info', new \DateTime('now'));
//
//        try {
//            $result = $client->getLogEndpoint()->saveLog($logEntry);
//
//            $this->assertNotEmpty($result->id);
//            var_dump($result);
//        } catch (\Exception $ex) {
//            echo 'Whoops: ' . $ex->getMessage();
//        }


    }


}

