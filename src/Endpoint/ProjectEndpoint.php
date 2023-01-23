<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

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
     * @return Project[]
     * @throws RequestException
     */
    public function getProjects(): array
    {
        $uri = '/projects/';

        $projects = [];


        $rawProjects = $this->requestCollection($uri);
        if (isset($rawProjects['data'])) {
            $rawProjects = $rawProjects['data'];
        }
        foreach ($rawProjects as $rawProject) {
            $projects[] = $this->parseProject($rawProject);
        }

        return $projects;
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
