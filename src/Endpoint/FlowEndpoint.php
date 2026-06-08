<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Model\CollectionResult;
use Attlaz\Model\CursorPagination;
use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\Flow;
use Attlaz\Model\FlowRun;
use Attlaz\Model\FlowRunRequestResponse;
use Attlaz\Model\FlowRunSummary;
use Attlaz\Model\Log\LogStreamId;
use Attlaz\Model\State;


class FlowEndpoint extends Endpoint
{


    public function requestRunFlow(string $flowId, array $arguments = [], string|null $projectEnvironmentId = null): FlowRunRequestResponse
    {
        $body = [
            'arguments' => $arguments,
        ];
        if ($projectEnvironmentId !== null) {
            $body['project_environment'] = $projectEnvironmentId;
        }

        $uri = '/flows/' . $flowId . '/flowrunrequests';

        $response = $this->requestObject($uri, $body, 'POST');

        if (!isset($response['success'])) {
            throw new \Exception('Unable to parse flow run request response: success property not defined');
        }
        $success = ($response['success'] === true || $response['success'] === 'true');
        $result = new FlowRunRequestResponse($success, $response['flow_run_request']);

        $resultData = null;
        if (!\is_null($response['result'])) {
            try {
                $resultData = $response['result']['data'];
            } catch (\Error $error) {
                throw new \Exception('Unable to parse flow run request response: ' . $error->getMessage());
            }
        }

        $result->result = $resultData;

        return $result;
    }


    /**
     * @param string $projectId
     * @return CollectionResult<Flow>
     * @throws RequestException
     */
    public function getFlows(string $projectId, CursorPagination|null $pagination = null): CollectionResult
    {
        $uri = '/projects/' . $projectId . '/flows';

        $parser = function (array $record) {
            $flow = new Flow();
            $flow->id = $record['id'];
            $flow->key = $record['key'];
            $flow->name = $record['name'];
            $flow->description = $record['description'] ?? '';
            $flow->projectId = $record['project'];
            $flow->isDirect = $record['is_direct'];
            $flow->state = State::from($record['state']);
            return $flow;
        };
        return $this->requestCollection($uri, $pagination, $parser);
    }

    public function createFlowRun(string $flowId, string $projectEnvironmentId): FlowRun
    {
        $body = null;

        $uri = '/flows/' . $flowId . '/runs?environment=' . $projectEnvironmentId;


        $response = $this->requestObject($uri, $body, 'POST');

        if (isset($response['id']) && !empty($response['id'])) {

            $flowRun = new FlowRun();
            $flowRun->id = $response['id'];
            $flowRun->flowId = $response['request']['flow'];
            $flowRun->projectEnvironmentId = $response['project_environment'];
            $flowRun->logStreamId = new LogStreamId($response['request']['log_stream']);
            $flowRun->arguments = $response['request']['arguments'];

            return $flowRun;
        }

        throw new \Exception('Unable to create flow run');
    }

    public function getFlowRun(string $flowRunId): FlowRun|null
    {
        $uri = '/flowruns/' . $flowRunId . '/summaries';

        $rawResult = $this->requestObject($uri);

        if ($rawResult === null) {
            return null;
        }
        $flowRun = new FlowRun();
        $flowRun->id = $rawResult['id'];
        $flowRun->flowId = $rawResult['flow'];
        $flowRun->projectEnvironmentId = $rawResult['project_environment'];
        $flowRun->logStreamId = new LogStreamId($rawResult['log_stream']);

        $arguments = $rawResult['arguments'];
        if (is_array($arguments)) {
            $flowRun->arguments = $arguments;
        } elseif (is_null($arguments)) {
            $flowRun->arguments = [];
        }
        //TODO: handle when no execution is found
        return $flowRun;

    }

    /**
     * @param string $flowId
     * @return CollectionResult<FlowRunSummary>
     * @throws \Exception
     */
    public function getFlowRunSummaries(string $flowId, CursorPagination|null $pagination = null): CollectionResult
    {
        $uri = '/flows/' . $flowId . '/runsummaries';

        $parser = function ($record) {
            $flowRunSummary = new FlowRunSummary();
            $flowRunSummary->id = $record['id'];
            $flowRunSummary->flowId = $record['flow'];
            //  $flowRunSummary->name = $record['name'];
            $flowRunSummary->time = \DateTime::createFromFormat(\DateTimeInterface::RFC3339_EXTENDED, $record['time']);
            $flowRunSummary->runDuration = $record['run_duration'];
            $flowRunSummary->pendingDuration = $record['pending_duration'];
            $flowRunSummary->status = $record['status'];
            return $flowRunSummary;
        };
        return $this->requestCollection($uri, $pagination, $parser);
    }

    public function updateFlowRun(string $flowRunId, string $status, int|null $time = null): void
    {
        $body = [
            'status' => $status,
            'time' => $time,
        ];

        $uri = '/flowruns/' . $flowRunId;


        $savedFlowRun = $this->requestObject($uri, $body, 'POST');
    }


}
