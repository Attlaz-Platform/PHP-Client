<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\Flow;
use Attlaz\Model\RunFlowResult;
use Attlaz\Model\State;


class FlowEndpoint extends Endpoint
{


    public function requestRunFlow(string $flowId, array $arguments = [], string|null $projectEnvironmentId = null): RunFlowResult
    {
        $body = [
            'arguments' => $arguments,
        ];
        if ($projectEnvironmentId !== null) {
            $body['projectEnvironment'] = $projectEnvironmentId;
        }

        $uri = '/flows/' . $flowId . '/flowrunrequests';

        $response = $this->requestObject($uri, $body, 'POST');
//        var_dump($response);

        //TODO: validate response & handle issues
        $success = ($response['success'] === true || $response['success'] === 'true');
        $result = new RunFlowResult($success, $response['flow_run_request']);

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

        $rawFlows = $this->requestCollection($uri);


        $flows = [];

        foreach ($rawFlows as $rawFlow) {
            $flow = new Flow();
            $flow->id = $rawFlow['id'];
            $flow->key = $rawFlow['key'];
            $flow->name = $rawFlow['name'];
            $flow->description = $rawFlow['description'] ?? '';
            $flow->projectId = $rawFlow['project'];
            $flow->isDirect = $rawFlow['is_direct'];
            $flow->state = State::from($rawFlow['state']);
            $flows[] = $flow;
        }


        return $flows;
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

    public function getFlowRun(string $flowRunId): ?array
    {
        $uri = '/flowruns/' . $flowRunId . '/summaries';
        //TODO: handle when no execution is found
        return $this->requestObject($uri);

    }

    public function updateFlowRun(string $flowRunId, string $status, int $time = null): void
    {
        $body = [
            'status' => $status,
            'time'   => $time,
        ];

        $uri = '/flowruns/' . $flowRunId;


        $savedFlowRun = $this->requestObject($uri, $body, 'POST');
    }


}
