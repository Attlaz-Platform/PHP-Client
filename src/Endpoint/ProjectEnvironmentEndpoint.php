<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Helper\LoadAllHelper;
use Attlaz\Model\CollectionResult;
use Attlaz\Model\CursorPagination;
use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\ProjectEnvironment;
use Attlaz\Model\State;


class ProjectEnvironmentEndpoint extends Endpoint
{


    public function getProjectEnvironmentById(string $projectEnvironmentId): ProjectEnvironment|null
    {
        $uri = '/projectenvironments/' . $projectEnvironmentId;

        $rawEnvironment = $this->requestObject($uri);
        if ($rawEnvironment === null) {
            return null;
        }


        return $this->parseProjectEnvironment($rawEnvironment);
    }

    public function getProjectEnvironmentByKey(string $projectId, string $projectEnvironmentKey): ProjectEnvironment
    {
        //TODO: handle when environment is not found
        $projectEnvironments = LoadAllHelper::loadAll(fn(CursorPagination $pagination): CollectionResult => $this->getProjectEnvironments($projectId, $pagination));
        foreach ($projectEnvironments as $projectEnvironment) {
            if ($projectEnvironment->key === $projectEnvironmentKey) {
                return $projectEnvironment;
            }
        }

        throw new \Exception('No project environment with key "' . $projectEnvironmentKey . '" found');
    }


    /**
     * @param string $projectId
     * @return CollectionResult<ProjectEnvironment>
     * @throws RequestException
     */
    public function getProjectEnvironments(string $projectId, CursorPagination|null $pagination = null): CollectionResult
    {
        $uri = '/projects/' . $projectId . '/environments';

        $parser = fn(array $rawEnvironment): ProjectEnvironment => $this->parseProjectEnvironment($rawEnvironment);

        return $this->requestCollection($uri, $pagination, $parser);
    }

    private function parseProjectEnvironment(array $rawEnvironment): ProjectEnvironment
    {
        $projectEnvironment = new ProjectEnvironment();
        $projectEnvironment->id = (string)$rawEnvironment['id'];
        $projectEnvironment->key = $rawEnvironment['key'];
        $projectEnvironment->name = $rawEnvironment['name'];
        $projectEnvironment->projectId = $rawEnvironment['project'];
        $projectEnvironment->type = $rawEnvironment['type'];
        $projectEnvironment->state = State::from($rawEnvironment['state']);
        return $projectEnvironment;
    }

}
