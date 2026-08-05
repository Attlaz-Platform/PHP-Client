<?php
declare(strict_types=1);

namespace Attlaz;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /**
     * The project these tests read against. Its contents change over time, so assert on shape and
     * membership rather than on exact counts — an exact count turns "someone added a flow" into a
     * failing test, which is how the previous fixtures rotted.
     */
    private const PROJECT_ID = '1dCxPOug1npDYEPY7W719a9CszW';
    private const PRODUCTION_ENVIRONMENT_ID = '1F6GQAEc8GYLZ5ohnaTudLOL3OG';

    private array $endpoints = [
        'https://gateway.api.attlaz.com',
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

            $project = $client->getProjectEndpoint()->getProjectById(self::PROJECT_ID);
            $this->assertEquals(self::PROJECT_ID, $project->id);
            $this->assertEquals('webshop', $project->key);
            $this->assertEquals('0yYdGTNLivhDAha0rnebH9VyFdi', $project->workspaceId);

            // Takes the environment *key*, not its id — this used to be passed an id, so it looked
            // for an environment keyed "1F6GQ…" and could only ever 404.
            $projectEnvironment = $client->getProjectEnvironmentEndpoint()->getProjectEnvironmentByKey(self::PROJECT_ID, 'production');
            $this->assertEquals(self::PROJECT_ID, $projectEnvironment->projectId);
            $this->assertEquals(self::PRODUCTION_ENVIRONMENT_ID, $projectEnvironment->id);
            $this->assertEquals('production', $projectEnvironment->key);

            $projectEnvironments = $client->getProjectEnvironmentEndpoint()->getProjectEnvironments(self::PROJECT_ID);
            $this->assertNotEmpty($projectEnvironments->getData());
            foreach ($projectEnvironments->getData() as $environment) {
                $this->assertEquals(self::PROJECT_ID, $environment->projectId);
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

            $projectEnvironments = $client->getProjectEnvironmentEndpoint()->getProjectEnvironments(self::PROJECT_ID);

            $keys = \array_map(static fn($environment): string => $environment->key, $projectEnvironments->getData());
            $this->assertContains('production', $keys);
            $this->assertContains('staging', $keys);
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

            $ids = \array_map(static fn($project): string => $project->id, $projects->getData());
            $this->assertContains(self::PROJECT_ID, $ids);
        }
    }

    public function testGetFlows()
    {

        $client = new Client();
        $client->authWithClient($_ENV['api_client_id'], $_ENV['api_client_secret']);
//        $client->enableDebug();

        foreach ($this->endpoints as $endpoint) {
            $client->setEndPoint($endpoint);

            $flows = $client->getFlowEndpoint()->getFlows(self::PROJECT_ID);

            $this->assertNotEmpty($flows->getData());
            foreach ($flows->getData() as $flow) {
                $this->assertNotEmpty($flow->id);
                $this->assertEquals(self::PROJECT_ID, $flow->projectId);
            }
        }
    }
}
