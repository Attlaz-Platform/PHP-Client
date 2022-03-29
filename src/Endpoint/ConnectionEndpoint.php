<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Client;
use Attlaz\Model\AdapterConnection;
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
     * @return AdapterConnection[]
     * @throws RequestException
     */
    public function getConnections(string $projectId): array
    {
        $uri = '/project/' . $projectId . '/connections';

        $request = $this->client->createRequest('GET', $uri);

        $response = $this->client->sendRequest($request);
        $rawConnections = $response['data'];

        $connections = [];
        foreach ($rawConnections as $rawConnection) {
            $connections[] = new AdapterConnection($rawConnection);
        }

        return $connections;
    }

    /**
     * @param string $connectionKey
     * @return AdapterConnection|null
     * @throws RequestException
     */
    public function getConnection(string $connectionKey): ?AdapterConnection
    {
        $uri = '/connections/' . $connectionKey . '';

        $request = $this->client->createRequest('GET', $uri);

        $response = $this->client->sendRequest($request);
        
        if (isset($response['errors']) && count($response['errors']) > 0) {
            return null;
        }
        $rawConnection = $response['data'];
        return new AdapterConnection($rawConnection);
    }
}
