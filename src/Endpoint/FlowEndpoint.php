<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\Flow;
use Attlaz\Model\FlowRunRequestResponse;
use Attlaz\Model\FlowRunSummary;
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
//        var_dump($response);

        //TODO: validate response & handle issues
        $success = ($response['success'] === true || $response['success'] === 'true');
        $result = new FlowRunRequestResponse($success, $response['flow_run_request']);

        $resultData = null;
        if (!\is_null($response['result'])) {
            try {
                $resultData = $response['result']['data'];
            } catch (\Error $error) {
                throw new \Exception('Unable to parse flow run schedule response: ' . $error->getMessage());
            }
        }

        $result->result = $resultData;

        return $result;
    }


    /**
     * @param string $projectId
     * @return Flow[]
     * @throws RequestException
     */
    public function getFlows(string $projectId): array
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
        return $this->requestCollection($uri, null, 'GET', $parser);
    }

    public function createFlowRun(string $flowId, string $projectEnvironmentId): string
    {
        $body = null;

        $uri = '/flows/' . $flowId . '/runs?environment=' . $projectEnvironmentId;


        $response = $this->requestObject($uri, $body, 'POST');

        if (isset($response['id']) && !empty($response['id'])) {
            return $response['id'];
        }

        throw new \Exception('Unable to create flow run');
    }

    public function getFlowRun(string $flowRunId): array|null
    {
        $uri = '/flowruns/' . $flowRunId . '/summaries';

        $rawResult = $this->requestObject($uri);
        //TODO: handle when no execution is found
        return $rawResult;

    }

    /**
     * @param string $flowRunId
     * @return FlowRunSummary[]
     * @throws \Exception
     */
    public function getFlowRunSummaries(string $flowRunId): array
    {
        $uri = '/flows/' . $flowRunId . '/runsummaries';

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
        return $this->requestCollection($uri, null, 'GET', $parser);
    }

    public function updateFlowRun(string $flowRunId, string $status, int $time = null): void
    {
        $body = [
            'status' => $status,
            'time' => $time,
        ];

        $uri = '/flowruns/' . $flowRunId;


        $savedFlowRun = $this->requestObject($uri, $body, 'POST');
    }


}
