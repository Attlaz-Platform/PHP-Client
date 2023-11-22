<?php
declare(strict_types=1);

namespace Attlaz;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
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

    public function testGet()
    {

        $client = new Client();
        $client->authWithClient($_ENV['api_client_id'], $_ENV['api_client_secret']);
//        $client->enableDebug();

        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $project = $client->getProjectEndpoint()->getProjectById('1dCxPOug1npDYEPY7W719a9CszW');
            $this->assertEquals('1dCxPOug1npDYEPY7W719a9CszW', $project->id);
            $this->assertEquals('webshop', $project->key);
            $this->assertEquals('verlichting', $project->workspaceId);

            $projectEnvironment = $client->getProjectEnvironmentEndpoint()->getProjectEnvironmentByKey('1dCxPOug1npDYEPY7W719a9CszW', '1F6GQAEc8GYLZ5ohnaTudLOL3OG');
            $this->assertEquals('1dCxPOug1npDYEPY7W719a9CszW', $projectEnvironment->projectId);
            $this->assertEquals('1F6GQAEc8GYLZ5ohnaTudLOL3OG', $projectEnvironment->id);
            $this->assertEquals('production', $projectEnvironment->key);

            $projectEnvironments = $client->getProjectEnvironmentEndpoint()->getProjectEnvironments('0DF2CCCDF');
            foreach ($projectEnvironments as $projectEnvironment) {
                $this->assertEquals('0DF2CCCDF', $projectEnvironment->projectId);
            }
        }


    }

    public function testGetProjectEnvironments()
    {

        $client = new Client();
        $client->authWithClient($_ENV['api_client_id'], $_ENV['api_client_secret']);
//        $client->enableDebug();

        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $projectEnvironments = $client->getProjectEnvironmentEndpoint()->getProjectEnvironments('0DF2CCCDF');


            $this->assertCount(3, $projectEnvironments);
        }
    }

    public function testGetProjects()
    {

        $client = new Client();
        $client->authWithClient($_ENV['api_client_id'], $_ENV['api_client_secret']);
//        $client->enableDebug();

        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $projects = $client->getProjectEndpoint()->getProjects();


            $this->assertCount(1, $projects);
        }
    }

    public function testGetFlows()
    {

        $client = new Client();
        $client->authWithClient($_ENV['api_client_id'], $_ENV['api_client_secret']);
//        $client->enableDebug();

        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $flows = $client->getFlowEndpoint()->getFlows('1dCxPOug1npDYEPY7W719a9CszW');


            $this->assertCount(8, $flows);
        }
    }
}

