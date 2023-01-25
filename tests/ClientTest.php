<?php
declare(strict_types=1);

namespace Attlaz;

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

        $client = new Client(\getenv('api_client_id'), \getenv('api_client_secret'));
//        $client->enableDebug();
        $endpoints = [
//            'https://api.attlaz.com',
//            'https://api.attlaz.com/1.6',
//            'https://api.attlaz.com/1.7',
//            'https://api.attlaz.com/1.8',
//            'https://api.attlaz.com/beta'
'https://55af-188-211-160-246.ngrok.io'
        ];
        foreach ($endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $project = $client->getProjectById('1dHTsCjE5x2SZbSaq6TDDvKQUZC');
            $this->assertEquals('1dHTsCjE5x2SZbSaq6TDDvKQUZC', $project->id);
            $this->assertEquals('echron', $project->key);
            $this->assertEquals('1krQ3RecRdB379qgcjU5XVXczOA', $project->team);

            $projectEnvironment = $client->getProjectEnvironmentByKey('1dHTsCjE5x2SZbSaq6TDDvKQUZC', '0liREOSzIzzqY2tIiSA71ZWKArz');
            $this->assertEquals('1dHTsCjE5x2SZbSaq6TDDvKQUZC', $projectEnvironment->projectId);
            $this->assertEquals('0liREOSzIzzqY2tIiSA71ZWKArz', $projectEnvironment->id);
            $this->assertEquals('production', $projectEnvironment->key);

            $projectEnvironments = $client->getProjectEnvironments('1dHTsCjE5x2SZbSaq6TDDvKQUZC');
            foreach ($projectEnvironments as $projectEnvironment) {
                $this->assertEquals('1dHTsCjE5x2SZbSaq6TDDvKQUZC', $projectEnvironment->projectId);
            }
        }


    }

    public function testGetProjectEnvironments()
    {

        $client = new Client(\getenv('api_client_id'), \getenv('api_client_secret'));
//        $client->enableDebug();
        $endpoints = [
//            'https://api.attlaz.com',
//            'https://api.attlaz.com/1.6',
//            'https://api.attlaz.com/1.7',
//            'https://api.attlaz.com/1.8',
//            'https://api.attlaz.com/beta'
'https://55af-188-211-160-246.ngrok.io'
        ];
        foreach ($endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $projectEnvironments = $client->getProjectEnvironments('1dHTsCjE5x2SZbSaq6TDDvKQUZC');


            $this->assertCount(2, $projectEnvironments);
        }
    }

    public function testGetProjects()
    {

        $client = new Client(\getenv('api_client_id'), \getenv('api_client_secret'));
//        $client->enableDebug();
        $endpoints = [
//            'https://api.attlaz.com',
//            'https://api.attlaz.com/1.6',
//            'https://api.attlaz.com/1.7',
//            'https://api.attlaz.com/1.8',
//            'https://api.attlaz.com/beta'
'https://55af-188-211-160-246.ngrok.io'
        ];
        foreach ($endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $projects = $client->getProjects();


            $this->assertCount(1, $projects);
        }
    }

    public function testGetFlows()
    {

        $client = new Client(\getenv('api_client_id'), \getenv('api_client_secret'));
//        $client->enableDebug();
        $endpoints = [
//            'https://api.attlaz.com',
//            'https://api.attlaz.com/1.6',
//            'https://api.attlaz.com/1.7',
//            'https://api.attlaz.com/1.8',
//            'https://api.attlaz.com/beta'
'https://55af-188-211-160-246.ngrok.io'
        ];
        foreach ($endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $flows = $client->getTasks('1dHTsCjE5x2SZbSaq6TDDvKQUZC');


            $this->assertCount(10, $flows);
        }
    }
}

