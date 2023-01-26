<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Model\Log\LogEntry;
use Attlaz\Model\Log\LogStream;
use Attlaz\Model\Log\LogStreamId;


class LogEndpoint extends Endpoint
{


    public function saveLog(LogEntry $logEntry): LogEntry
    {
        $body = $logEntry;

        $uri = '/logstreams/' . \base64_encode($logEntry->getLogStreamId()->__toString()) . '/logs';


        $rawLogEntry = $this->requestObject($uri, $body, 'POST');


        // TODO: validate of saving was successfull

        $logEntry->id = $rawLogEntry['id'];
        return $logEntry;


    }

    /**
     * @param string $projectId
     * @return LogStream[]
     * @throws \Attlaz\Model\Exception\RequestException
     */
    public function getLogStreams(string $projectId): array
    {

        $uri = '/projects/' . $projectId . '/logstreams';


        $logStreams = $this->requestCollection($uri);


        $result = [];
        foreach ($logStreams as $logStream) {

            $id = $logStream['id'];
            if (\is_array($id)) {
                $id = $id['id'];
            }
            $result[] = new LogStream(new LogStreamId($id), $logStream['name']);
        }

        return $result;
    }
}
