<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Client;
use Attlaz\Model\Exception\RequestException;

class ConnectionEndpoint
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $projectId
     * @return array[]
     * @throws RequestException
     */
    public function getConnections(string $projectId): array
    {
        $uri = '/project/' . $projectId . '/connections';

        $request = $this->client->createRequest('GET', $uri);

//        $connections = [];

        $response = $this->client->sendRequest($request);
        $rawConnections = $response['data'];
//        foreach ($rawEnvironments as $rawEnvironment) {
//            $projectEnvironments[] = $this->parseProjectEnvironment($rawEnvironment);
//        }
//
//        return $projectEnvironments;

        return $rawConnections;
    }
}
