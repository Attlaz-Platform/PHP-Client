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
            'https://api.attlaz.com',
            'https://api.attlaz.com/1.6',
            'https://api.attlaz.com/1.7',
            'https://api.attlaz.com/1.8',
            'https://api.attlaz.com/beta'
        ];
        foreach ($endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $project = $client->getProjectById('0DF2CCCDF');
            $this->assertEquals('0DF2CCCDF', $project->id);
            $this->assertEquals('webshop', $project->key);
            $this->assertEquals('verlichting', $project->team);

            $projectEnvironment = $client->getProjectEnvironmentByKey('0DF2CCCDF', 'production');
            $this->assertEquals('0DF2CCCDF', $projectEnvironment->projectId);
            $this->assertEquals('61', $projectEnvironment->id);
            $this->assertEquals('production', $projectEnvironment->key);

            $projectEnvironments = $client->getProjectEnvironments('0DF2CCCDF');
            foreach ($projectEnvironments as $projectEnvironment) {
                $this->assertEquals('0DF2CCCDF', $projectEnvironment->projectId);
            }
        }


    }

    public function testGetProjectEnvironments()
    {

        $client = new Client(\getenv('api_client_id'), \getenv('api_client_secret'));
//        $client->enableDebug();
        $endpoints = [
            'https://api.attlaz.com',
            'https://api.attlaz.com/1.6',
            'https://api.attlaz.com/1.7',
            'https://api.attlaz.com/1.8',
            'https://api.attlaz.com/beta'
        ];
        foreach ($endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $projectEnvironments = $client->getProjectEnvironments('0DF2CCCDF');


            $this->assertCount(3, $projectEnvironments);
        }
    }

    public function testGetProjects()
    {

        $client = new Client(\getenv('api_client_id'), \getenv('api_client_secret'));
//        $client->enableDebug();
        $endpoints = [
            'https://api.attlaz.com',
            'https://api.attlaz.com/1.6',
            'https://api.attlaz.com/1.7',
            'https://api.attlaz.com/1.8',
            'https://api.attlaz.com/beta'
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
            'https://api.attlaz.com',
            'https://api.attlaz.com/1.6',
            'https://api.attlaz.com/1.7',
            'https://api.attlaz.com/1.8',
            'https://api.attlaz.com/beta'
        ];
        foreach ($endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $flows = $client->getTasks('0DF2CCCDF');


            $this->assertCount(6, $flows);
        }
    }
}

