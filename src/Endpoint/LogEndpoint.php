<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Client;
use Attlaz\Model\LogEntry;

class LogEndpoint
{
    /** @var Client */
    private $client;

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


        if (isset($response['id'])) {
            $logEntry->id = $response['id'];
            return $logEntry;
        }
        throw new \Exception('Unable to save log entry: invalid response');
    }
}
