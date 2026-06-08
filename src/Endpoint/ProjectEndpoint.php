<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Model\CollectionResult;
use Attlaz\Model\CursorPagination;
use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\Project;
use Attlaz\Model\State;


class ProjectEndpoint extends Endpoint
{


    public function getProjectById(string $projectId): Project
    {
        $uri = '/projects/' . $projectId;

        $rawProject = $this->requestObject($uri);
        if ($rawProject === null) {
            throw new \Exception('No project with id "' . $projectId . '" found');
        }
        return $this->parseProject($rawProject);
    }

    /**
     * @return CollectionResult<Project>
     * @throws RequestException
     */
    public function getProjects(CursorPagination|null $pagination = null): CollectionResult
    {
        $uri = '/projects/';

        $parser = fn(array $rawProject): Project => $this->parseProject($rawProject);

        return $this->requestCollection($uri, $pagination, $parser);
    }

    private function parseProject(array $rawProject): Project
    {

        $project = new Project();
        $project->id = $rawProject['id'];
        $project->key = $rawProject['key'];
        $project->name = $rawProject['name'];
        $project->workspaceId = $rawProject['workspace'];
        $project->defaultEnvironmentId = $rawProject['default_environment'];
        $project->state = State::from($rawProject['state']);

        return $project;
    }
}
