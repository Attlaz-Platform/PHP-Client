<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Model\AdapterConfiguration;
use Attlaz\Model\AdapterConnection;
use Attlaz\Model\AdapterConnectionConfigurationValue;
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
     * @param string $connectionId
     * @return AdapterConnection|null
     * @throws RequestException
     */
    public function getConnection(string $connectionId): AdapterConnection|null
    {
        $uri = '/connections/' . $connectionId;
        $rawConnection = $this->requestObject($uri);
        return new AdapterConnection($rawConnection);
    }

    /**
     * @param string $projectId
     * @param string $connectionKey
     * @return AdapterConnection|null
     */
    public function getConnectionByKey(string $projectId, string $connectionKey): AdapterConnection|null
    {
        $uri = '/projects/' . $projectId . '/connections/' . $connectionKey;
        $rawConnection = $this->requestObject($uri);
        return new AdapterConnection($rawConnection);
    }

    /**
     * @param string $adapterId
     * @return AdapterConfiguration[]
     * @throws \Exception
     */
    public function getAdapterConfiguration(string $adapterId): array
    {
        $uri = '/adapters/' . $adapterId . '/configuration';
        $rawConfigurations = $this->requestCollection($uri, null, 'GET');

        $configurations = [];
        foreach ($rawConfigurations as $rawConfiguration) {
            $configurations[] = new  AdapterConfiguration($rawConfiguration);
        }

        return $configurations;
    }

    /**
     * @param string $connectionId
     * @return AdapterConnectionConfigurationValue[]
     * @throws \Exception
     */
    public function getConnectionConfiguration(string $connectionId): array
    {
        $uri = '/connections/' . $connectionId . '/configuration';
        $rawConfigurations = $this->requestCollection($uri, null, 'GET');
        $configurations = [];
        foreach ($rawConfigurations as $rawConfiguration) {
            $configurations[] = new AdapterConnectionConfigurationValue($rawConfiguration);
        }

        return $configurations;
    }
}
