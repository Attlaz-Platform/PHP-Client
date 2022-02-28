<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Endpoint\LogEndpoint;
use Attlaz\Endpoint\StorageEndpoint;
use Attlaz\Helper\TokenStorage;
use Attlaz\Model\Config;
use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\LogEntry;
use Attlaz\Model\Project;
use Attlaz\Model\ProjectEnvironment;
use Attlaz\Model\Task;
use Attlaz\Model\TaskExecutionResult;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;

class Client
{
    private string $endPoint = 'https://api.attlaz.com';
    private string $clientId;
    private string $clientSecret;
    private bool $storeToken = false;
    private int $timeout = 20;

    private bool $debug = false;

    private ?GenericProvider $provider = null;
    private ?AccessToken $accessToken = null;

    private StorageEndpoint $storageEndpoint;
    private LogEndpoint $logEndpoint;

    public function __construct(string $clientId, string $clientSecret, bool $storeToken = false)
    {
        if (empty($clientId)) {
            throw new \InvalidArgumentException('ClientId cannot be empty');
        }
        $this->clientId = $clientId;

        if (empty($clientSecret)) {
            throw new \InvalidArgumentException('ClientSecret secret cannot be empty');
        }
        $this->clientSecret = $clientSecret;
        $this->storeToken = $storeToken;

        $this->storageEndpoint = new StorageEndpoint($this);
        $this->logEndpoint = new LogEndpoint($this);
    }

    public function setEndPoint(string $endPoint): void
    {
        if ($endPoint === '') {
            throw new \InvalidArgumentException('Endpoint cannot be empty');
        }
        $this->endPoint = rtrim($endPoint, "/");
    }

    private function authenticate()
    {


        try {
            if (\is_null($this->accessToken) || $this->accessToken->hasExpired()) {
                $this->provider = new GenericProvider([
                    'clientId'                => $this->clientId,
                    'clientSecret'            => $this->clientSecret,
                    'redirectUri'             => 'https://attlaz.com/',
                    'urlAuthorize'            => $this->endPoint . '/oauth/authorize',
                    'urlAccessToken'          => $this->endPoint . '/oauth/token',
                    'urlResourceOwnerDetails' => $this->endPoint . '/oauth/resource',
                    'base_uri'                => $this->endPoint,
                    'timeout'                 => $this->timeout,
                ]);

                $accessToken = null;
                if ($this->storeToken) {
                    $accessToken = TokenStorage::loadAccessToken($this->clientId, $this->clientSecret);
                }

                if (!\is_null($accessToken)) {
                    $this->accessToken = $accessToken;
                } else {
                    $this->accessToken = $this->provider->getAccessToken('client_credentials', [
                        'scope' => 'all',
                    ]);
                    if ($this->storeToken) {
                        TokenStorage::saveAccessToken($this->accessToken, $this->clientId, $this->clientSecret);
                    }
                }
            }
        } catch (IdentityProviderException $ex) {
            throw new \Exception('Unable to authenticate: ' . $ex->getMessage());
        } catch (\Throwable $ex) {
            if ($this->debug) {
                \var_dump($ex);
            }
//            \var_dump($ex);
            throw new \Exception('Unable to authenticate');
        }
    }

    public function getAccessToken(): ?AccessToken
    {
        return $this->accessToken;
    }

    public function setAccessToken(AccessToken $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function createRequest(string $method, string $uri, $body = null): RequestInterface
    {
        $this->authenticate();
        if (\is_null($this->provider) || \is_null($this->accessToken)) {
            throw new \Exception('Unable to create request: not authenticated');
        }
        $options = [];
        if (!\is_null($body)) {
            $body = \json_encode($body);
            $options['body'] = $body;
        }

        $options['headers'] = ['Content-Type' => 'application/json'];

        $url = $this->endPoint . $uri;

        return $this->provider->getAuthenticatedRequest($method, $url, $this->accessToken, $options);
    }

    public function sendRequest(RequestInterface $request): array
    {
        try {
            $response = $this->provider->getHttpClient()
                ->send($request, ['debug' => $this->debug]);

            $jsonResponse = \json_decode($response->getBody()
                ->getContents(), true);

        } catch (\Throwable $ex) {
            throw new RequestException($ex->getMessage());
        }

        return $jsonResponse;
    }

    //    public function scheduleTaskByCommand(string $branch, string $command, array $arguments = []): ScheduleTaskResult
    //    {
    //        $body = [
    //            'command'   => $command,
    //            'arguments' => $arguments,
    //        ];
    //
    //        $uri = '/branches/' . $branch . '/taskexecutionrequests';
    //
    //        $request = $this->createRequest('POST', $uri, $body);
    //
    //        $response = $this->sendRequest($request);
    //
    //        //TODO: validate response & handle issues
    //        $success = ($response['success'] === true || $response['success'] === 'true');
    //
    //        $data = null;
    //        if (isset($response['result']) && !empty($response['result'])) {
    //            $data = json_decode($response['result'], true);
    //            $data = $data['data'];
    //        }
    //
    //        $result = new ScheduleTaskResult($success, $response['taskExecutionRequest']);
    //        $result->result = $data;
    //
    //        return $result;
    //    }
    public function scheduleProjectTask(string $projectKey, string $taskKey, array $arguments = []): TaskExecutionResult
    {
        throw new \Exception('Not implemented');
    }

    public function requestTaskExecution(
        string $taskId,
        array  $arguments = [],
        string $projectEnvironmentId = null
    )
    {
        $body = [
            'arguments' => $arguments,
        ];
        if (!\is_null($projectEnvironmentId)) {
            $body['projectEnvironment'] = $projectEnvironmentId;
        }

        $uri = '/tasks/' . $taskId . '/taskexecutionrequests';

        $request = $this->createRequest('POST', $uri, $body);

        $response = $this->sendRequest($request);

        //TODO: validate response & handle issues
        $success = ($response['success'] === true || $response['success'] === 'true');

        $result = new TaskExecutionResult($success, $response['taskExecutionRequest']);

        $resultData = null;
        if (!\is_null($response['result'])) {
            try {
                $resultData = $response['result']['data'];
            } catch (\Error $error) {
                throw new \Exception('Unable to parse task schedule response: ' . $error->getMessage());
            }
        }

        $result->result = $resultData;

        return $result;
    }

    /** @deprecated */
    public function scheduleTask(
        string $taskId,
        array  $arguments = [],
        string $projectEnvironmentId = null
    ): TaskExecutionResult
    {
        return $this->requestTaskExecution($taskId, $arguments, $projectEnvironmentId);
    }

    /**
     * @param string $projectId
     * @return Task[]
     * @throws RequestException
     */
    public function getTasks(string $projectId): array
    {
        $uri = '/projects/' . $projectId . '/tasks';

        $request = $this->createRequest('GET', $uri);

        $rawTasks = $this->sendRequest($request);

        $tasks = [];
        if (!\is_null($rawTasks)) {
            foreach ($rawTasks as $rawTask) {
                $task = new Task();
                $task->id = $rawTask['id'];
                $task->key = $rawTask['key'];
                $task->name = $rawTask['name'];
                $task->description = $rawTask['description'];
                $task->project = $rawTask['project'];
                $task->state = $rawTask['state'];
                $task->direct = $rawTask['direct'];

                $tasks[] = $task;
            }
        }

        return $tasks;
    }

    public function createTaskExecution(string $taskId, string $projectEnvironmentId): string
    {
        $body = null;

        $uri = '/tasks/' . $taskId . '/executions?environment=' . $projectEnvironmentId;

        $request = $this->createRequest('POST', $uri, $body);

        $response = $this->sendRequest($request);

        if (isset($response['id']) && !empty($response['id'])) {
            return $response['id'];
        }

        throw new \Exception('Unable to create task execution');
    }

    public function getTaskExecution(string $taskExecutionId): ?array
    {
        $uri = '/taskexecutions/' . $taskExecutionId . '/summaries';
        //TODO: handle when no execution is found
        $request = $this->createRequest('GET', $uri);

        $response = $this->sendRequest($request);

        return $response;
    }

    public function updateTaskExecution(string $taskExecutionId, string $status, int $time = null): void
    {
        $body = [
            'status' => $status,
            'time'   => $time,
        ];

        $uri = '/taskexecutions/' . $taskExecutionId . '';

        $request = $this->createRequest('POST', $uri, $body);

        $response = $this->sendRequest($request);
    }

    /**
     * @param string $projectId
     * @param int|null $projectEnvironmentId
     * @return Config[]
     * @throws RequestException
     */
    public function getConfigByProject(string $projectId, string $projectEnvironmentId = null): array
    {
        $uri = '/projects/' . $projectId . '/config';

        if (!\is_null($projectEnvironmentId)) {
            $uri = $uri . '?environment=' . $projectEnvironmentId;
        }

        $request = $this->createRequest('GET', $uri);

        $rawConfigValues = $this->sendRequest($request);
        $result = [];

        if (!\is_null($rawConfigValues) && \is_iterable($rawConfigValues)) {
            foreach ($rawConfigValues as $rawConfigValue) {
                $configValue = new Config();
                $configValue->id = $rawConfigValue['id'];
                $configValue->inheritable = $rawConfigValue['inheritable'];
                $configValue->sensitive = $rawConfigValue['sensitive'];
                $configValue->state = $rawConfigValue['state'];
                $configValue->project = $rawConfigValue['project'];
                $configValue->projectEnvironment = (string)$rawConfigValue['projectEnvironment'];
                $configValue->key = $rawConfigValue['key'];
                $configValue->value = $rawConfigValue['value'];

                $result[] = $configValue;
            }
        }

        return $result;
    }

    public function getProjectById(string $projectId): Project
    {
        $projects = $this->getProjects();

        foreach ($projects as $project) {
            if ($project->id === $projectId) {
                return $project;
            }
        }
        throw new \Exception('No project with id "' . $projectId . '" found');
    }

    public function getProjectEnvironmentById(string $projectEnvironmentId): ProjectEnvironment
    {
        $uri = '/projectenvironments/' . $projectEnvironmentId;

        $request = $this->createRequest('GET', $uri);

        //TODO: handle when environment is not found
        $rawEnvironment = $this->sendRequest($request);

        return $this->parseProjectEnvironment($rawEnvironment);
    }

    private function parseProject(array $rawProject): Project
    {

        $project = new Project();
        $project->id = $rawProject['id'];
        $project->key = $rawProject['key'];
        $project->name = $rawProject['name'];
        $project->team = $rawProject['team'];
        $project->defaultEnvironmentId = $rawProject['defaultEnvironmentId'];
        $project->state = $rawProject['state'];

        return $project;
    }

    private function parseProjectEnvironment(array $rawEnvironment): ProjectEnvironment
    {
        $projectEnvironment = new ProjectEnvironment();
        $projectEnvironment->id = (string)$rawEnvironment['id'];
        $projectEnvironment->key = $rawEnvironment['key'];
        $projectEnvironment->name = $rawEnvironment['name'];
        $projectEnvironment->projectId = $rawEnvironment['projectId'];
        $projectEnvironment->isLocal = $rawEnvironment['isLocal'];

        return $projectEnvironment;
    }

    public function requestDeploy(string $projectEnvironmentId): int
    {
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/deploys';

        $request = $this->createRequest('POST', $uri);

        $rawDeploy = $this->sendRequest($request);

        if (!\is_null($rawDeploy) && isset($rawDeploy['id'])) {
            return $rawDeploy['id'];
        }
        throw new \Exception('Something went wrong');
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
     * @return Project[]
     * @throws RequestException
     */
    public function getProjects(): array
    {
        $uri = '/projects/';

        $projects = [];
        $request = $this->createRequest('GET', $uri);

        $rawProjects = $this->sendRequest($request);
        foreach ($rawProjects as $rawProject) {
            $project = $this->parseProject($rawProject);
            $projects[] = $project;
        }

        return $projects;
    }

    /**
     * @param string $projectId
     * @return ProjectEnvironment[]
     * @throws RequestException
     */
    public function getProjectEnvironments(string $projectId): array
    {
        $uri = '/projects/' . $projectId . '/environments';

        $request = $this->createRequest('GET', $uri);

        $projectEnvironments = [];
        //TODO: handle when environment is not found
        $rawEnvironments = $this->sendRequest($request);
        foreach ($rawEnvironments as $rawEnvironment) {
            $projectEnvironments[] = $this->parseProjectEnvironment($rawEnvironment);
        }

        return $projectEnvironments;
    }

    /**
     * @param string $projectId
     * @return array[]
     * @throws RequestException
     */
    public function getConnections(string $projectId): array
    {
        $uri = '/connections?projectId=' . $projectId;

        $request = $this->createRequest('GET', $uri);

//        $connections = [];

        $response = $this->sendRequest($request);
        $rawConnections = $response['data'];
//        foreach ($rawEnvironments as $rawEnvironment) {
//            $projectEnvironments[] = $this->parseProjectEnvironment($rawEnvironment);
//        }
//
//        return $projectEnvironments;

        return $rawConnections;
    }

    public function enableDebug()
    {
        $this->debug = true;
    }

    public function disableDebug()
    {
        $this->debug = false;
    }

    public function getStorageEndpoint(): StorageEndpoint
    {
        return $this->storageEndpoint;
    }
    public function getLogEndpoint(): LogEndpoint
    {
        return $this->logEndpoint;
    }
}
