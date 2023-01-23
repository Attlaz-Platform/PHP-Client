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

        $client = new Client($_ENV['api_client_id'], $_ENV['api_client_secret']);
//        $client->enableDebug();

        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $project = $client->getProjectEndpoint()->getProjectById('0DF2CCCDF');
            $this->assertEquals('0DF2CCCDF', $project->id);
            $this->assertEquals('webshop', $project->key);
            $this->assertEquals('verlichting', $project->workspaceId);

            $projectEnvironment = $client->getProjectEnvironmentEndpoint()->getProjectEnvironmentByKey('0DF2CCCDF', 'production');
            $this->assertEquals('0DF2CCCDF', $projectEnvironment->projectId);
            $this->assertEquals('61', $projectEnvironment->id);
            $this->assertEquals('production', $projectEnvironment->key);

            $projectEnvironments = $client->getProjectEnvironmentEndpoint()->getProjectEnvironments('0DF2CCCDF');
            foreach ($projectEnvironments as $projectEnvironment) {
                $this->assertEquals('0DF2CCCDF', $projectEnvironment->projectId);
            }
        }


    }

    public function testGetProjectEnvironments()
    {

        $client = new Client($_ENV['api_client_id'], $_ENV['api_client_secret']);
//        $client->enableDebug();

        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $projectEnvironments = $client->getProjectEnvironmentEndpoint()->getProjectEnvironments('0DF2CCCDF');


            $this->assertCount(3, $projectEnvironments);
        }
    }

    public function testGetProjects()
    {

        $client = new Client($_ENV['api_client_id'], $_ENV['api_client_secret']);
//        $client->enableDebug();

        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $projects = $client->getProjectEndpoint()->getProjects();


            $this->assertCount(1, $projects);
        }
    }

    public function testGetFlows()
    {

        $client = new Client($_ENV['api_client_id'], $_ENV['api_client_secret']);
//        $client->enableDebug();

        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $flows = $client->getFlowEndpoint()->getFlows('0DF2CCCDF');


            $this->assertCount(6, $flows);
        }
    }
}

