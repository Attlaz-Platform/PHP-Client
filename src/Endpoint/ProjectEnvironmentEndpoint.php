<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\ProjectEnvironment;
use Attlaz\Model\State;


class ProjectEnvironmentEndpoint extends Endpoint
{


    public function getProjectEnvironmentById(string $projectEnvironmentId): ProjectEnvironment
    {
        $uri = '/projectenvironments/' . $projectEnvironmentId;

        $rawEnvironment = $this->requestObject($uri);


        return $this->parseProjectEnvironment($rawEnvironment);
    }

    public function getProjectEnvironmentByKey(string $projectId, string $projectEnvironmentKey): ProjectEnvironment
    {
        //TODO: handle when environment is not found
        $projectEnvironments = $this->getProjectEnvironments($projectId);
        foreach ($projectEnvironments as $projectEnvironment) {
            if ($projectEnvironment->key === $projectEnvironmentKey) {
                return $projectEnvironment;
            }
        }

        throw new \Exception('No project environment with key "' . $projectEnvironmentKey . '" found');
    }


    /**
     * @param string $projectId
     * @return ProjectEnvironment[]
     * @throws RequestException
     */
    public function getProjectEnvironments(string $projectId): array
    {
        $uri = '/projects/' . $projectId . '/environments';


        $projectEnvironments = [];
        //TODO: handle when environment is not found
        $rawEnvironments = $this->requestCollection($uri);

        foreach ($rawEnvironments as $rawEnvironment) {
            $projectEnvironments[] = $this->parseProjectEnvironment($rawEnvironment);
        }

        return $projectEnvironments;
    }

    private function parseProjectEnvironment(array $rawEnvironment): ProjectEnvironment
    {
        $projectEnvironment = new ProjectEnvironment();
        $projectEnvironment->id = (string)$rawEnvironment['id'];
        $projectEnvironment->key = $rawEnvironment['key'];
        $projectEnvironment->name = $rawEnvironment['name'];
        $projectEnvironment->projectId = $rawEnvironment['project'];
        $projectEnvironment->isLocal = $rawEnvironment['is_local'];
        $projectEnvironment->state = State::from($rawEnvironment['state']);
        return $projectEnvironment;
    }

}
