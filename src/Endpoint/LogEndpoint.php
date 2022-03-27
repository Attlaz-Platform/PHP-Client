<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Client;
use Attlaz\Model\Log\LogEntry;
use Attlaz\Model\Log\LogStream;
use Attlaz\Model\Log\LogStreamId;

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

        $uri = '/logstreams/' . \base64_encode($logEntry->getLogStreamId()->__toString()) . '/logs';

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

    /**
     * @param string $projectId
     * @return LogStream[]
     * @throws \Attlaz\Model\Exception\RequestException
     */
    public function getLogStreams(string $projectId): array
    {

        $uri = '/project/' . $projectId . '/logstreams';

        $request = $this->client->createRequest('GET', $uri);

        $response = $this->client->sendRequest($request);


        if (isset($response['data'])) {

            $result = [];
            foreach ($response['data'] as $logStream) {

                $id = $logStream['id'];
                if (\is_array($id)) {
                    $id = $id['id'];
                }
                $result[] = new LogStream(new LogStreamId($id), $logStream['name']);
            }

            return $result;
        }
        throw new \Exception('Unable to get log streams: invalid response');
    }
}
