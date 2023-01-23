<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Model\AdapterConnection;
use Attlaz\Model\Exception\RequestException;

class ConnectionEndpoint extends Endpoint
{


    /**
     * @param string $projectId
     * @return AdapterConnection[]
     * @throws RequestException
     */
    public function getConnections(string $projectId): array
    {
        $uri = '/project/' . $projectId . '/connections';


        $rawConnections = $this->requestCollection($uri, null, 'GET');

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
        $uri = '/connections/' . $connectionKey;
        $rawConnection = $this->requestObject($uri);
        return new AdapterConnection($rawConnection);
    }
}
