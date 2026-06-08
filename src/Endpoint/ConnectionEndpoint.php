<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Model\AdapterConfiguration;
use Attlaz\Model\AdapterConnection;
use Attlaz\Model\AdapterConnectionConfigurationValue;
use Attlaz\Model\CollectionResult;
use Attlaz\Model\CursorPagination;
use Attlaz\Model\Exception\RequestException;

class ConnectionEndpoint extends Endpoint
{


    /**
     * @param string $projectId
     * @return CollectionResult<AdapterConnection>
     * @throws RequestException
     */
    public function getConnections(string $projectId, CursorPagination|null $pagination = null): CollectionResult
    {
        $uri = '/projects/' . $projectId . '/connections';

        $parser = static fn(array $rawConnection): AdapterConnection => new AdapterConnection($rawConnection);

        return $this->requestCollection($uri, $pagination, $parser);
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
        if ($rawConnection === null) {
            return null;
        }
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
        if ($rawConnection === null) {
            return null;
        }
        return new AdapterConnection($rawConnection);
    }

    /**
     * @param string $adapterId
     * @return CollectionResult<AdapterConfiguration>
     * @throws \Exception
     */
    public function getAdapterConfiguration(string $adapterId, CursorPagination|null $pagination = null): CollectionResult
    {
        $uri = '/adapters/' . $adapterId . '/configuration';

        $parser = static fn(array $rawConfiguration): AdapterConfiguration => new AdapterConfiguration($rawConfiguration);

        return $this->requestCollection($uri, $pagination, $parser);
    }

    /**
     * @param string $connectionId
     * @return CollectionResult<AdapterConnectionConfigurationValue>
     * @throws \Exception
     */
    public function getConnectionConfiguration(string $connectionId, CursorPagination|null $pagination = null): CollectionResult
    {
        $uri = '/connections/' . $connectionId . '/configuration';

        $parser = static fn(array $rawConfiguration): AdapterConnectionConfigurationValue => new AdapterConnectionConfigurationValue($rawConfiguration);

        return $this->requestCollection($uri, $pagination, $parser);
    }

    public function createConnectionEvent(string $adapterConnectionId, string $type): bool
    {
        $uri = '/adapters/connections/' . $adapterConnectionId . '/events';

        $body = [
            'type' => $type,
            'time' => (new \DateTime('now'))->format(\DateTimeInterface::RFC3339_EXTENDED),
            'data' => null,
        ];
        $result = $this->requestObject($uri, $body, 'POST');

        if (!isset($result['created'])) {
            return false;
        }
        return (bool)$result['created'];


    }
}
