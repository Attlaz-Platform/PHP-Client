<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Http\Path;

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

        // The id goes in as it is. The API also accepts a base64-encoded identifier, but only to keep
        // reading the `<type>:<identifier>` form that was retired in May 2025 — nothing should be
        // producing those any more. The JavaScript client sends the plain id too.
        $uri = Path::build('/logstreams/:logStreamId/logs', [
            'logStreamId' => $logEntry->getLogStreamId()->__toString(),
        ]);


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
        $uri = Path::build('/projects/:projectId/logstreams', ['projectId' => $projectId]);

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
