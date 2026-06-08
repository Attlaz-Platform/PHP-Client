<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Model\CollectionResult;
use Attlaz\Model\CursorPagination;
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
     * @return CollectionResult<LogStream>
     * @throws \Attlaz\Model\Exception\RequestException
     */
    public function getLogStreams(string $projectId, CursorPagination|null $pagination = null): CollectionResult
    {
        $uri = '/projects/' . $projectId . '/logstreams';

        $parser = static function (array $logStream): LogStream {
            $id = $logStream['id'];
            if (\is_array($id)) {
                $id = $id['id'];
            }

            return new LogStream(new LogStreamId($id), $logStream['name']);
        };

        return $this->requestCollection($uri, $pagination, $parser);
    }
}
