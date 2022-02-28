<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Client;
use Attlaz\Model\LogEntry;

class LogEndpoint
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function saveLog(LogEntry $logEntry): ?string
    {
        $body = $logEntry;

        $uri = '/logstreams/' . $logEntry->logStreamId->getId() . '/logs';

        $request = $this->client->createRequest('POST', $uri, $body);

        $response = $this->client->sendRequest($request);

        if (isset($response['id']) && !empty($response['id'])) {
            return $response['id'];
        }

        if (isset($response['_id']) && !empty($response['_id'])) {
            return $response['_id'];
        }

        //TODO: should we throw an exception when we are unable to save the log
        return null;
    }
}
