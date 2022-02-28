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

    public function saveLog(LogEntry $logEntry): LogEntry
    {
        $body = $logEntry;

        $uri = '/logstreams/' . \base64_encode($logEntry->getLogStreamId()->getId()) . '/logs';

        $request = $this->client->createRequest('POST', $uri, $body);

        $response = $this->client->sendRequest($request);


        if (isset($response['data'])) {
            // TODO: validate of saving was successfull
            $savedEntry = $response['data'];
            $logEntry->id = $savedEntry['id'];
            return $logEntry;
        }
        throw new \Exception('Unable to save log entry: invalid response');
    }
}
