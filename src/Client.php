<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Endpoint\ConnectionEndpoint;
use Attlaz\Endpoint\LogEndpoint;
use Attlaz\Endpoint\StorageEndpoint;
use Attlaz\Helper\TokenStorage;
use Attlaz\Model\Config;
use Attlaz\Model\Exception\RequestException;
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
    private $endPoint = 'https://api.attlaz.com';
    private $clientId;
    private $clientSecret;
    private $storeToken = false;
    private $timeout = 20;

    private $debug = false;

    private $provider = null;
    private $accessToken = null;

    private $storageEndpoint;
    private $logEndpoint;

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
            $body['environment'] = $projectEnvironmentId;
        }

        $uri = '/flows/' . $taskId . '/flowrunrequests';

        $request = $this->createRequest('POST', $uri, $body);

        $response = $this->sendRequest($request);

        //TODO: validate response & handle issues
        $success = ($response['success'] === true || $response['success'] === 'true');
        $result = new TaskExecutionResult($success, $response['flow_run_request']);

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
        $uri = '/projects/' . $projectId . '/flows';

        $request = $this->createRequest('GET', $uri);

        $rawFlows = $this->sendRequest($request);

        $flows = [];
        if (!\is_null($rawFlows)) {
            foreach ($rawFlows as $rawFlow) {
                $flow = new Task();
                $flow->id = $rawFlow['id'];
                $flow->key = $rawFlow['key'];
                $flow->name = $rawFlow['name'];
                $flow->description = $rawFlow['description'];
                $flow->project = $rawFlow['project'];
                $flow->state = $rawFlow['state'];
                $flow->direct = $rawFlow['direct'];

                $flows[] = $flow;
            }
        }

        return $flows;
    }

    public function createTaskExecution(string $flowId, string $projectEnvironmentId): string
    {
        $body = null;

        $uri = '/flows/' . $flowId . '/runs?environment=' . $projectEnvironmentId;

        $request = $this->createRequest('POST', $uri, $body);

        $response = $this->sendRequest($request);

        if (isset($response['id']) && !empty($response['id'])) {
            return $response['id'];
        }

        throw new \Exception('Unable to create task execution');
    }

    public function getTaskExecution(string $taskExecutionId): ?array
    {
        $uri = '/flowruns/' . $taskExecutionId . '/summaries';
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

        $uri = '/flowruns/' . $taskExecutionId . '';

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
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/configvalues';

//        if (!\is_null($projectEnvironmentId)) {
//            $uri = $uri . '?environment=' . $projectEnvironmentId;
//        }

        $request = $this->createRequest('GET', $uri);

        $rawConfigValues = $this->sendRequest($request);
        $rawConfigValues = $rawConfigValues['data'];
        $result = [];

        if (!\is_null($rawConfigValues) && \is_iterable($rawConfigValues)) {
            foreach ($rawConfigValues as $rawConfigValue) {
                $configValue = new Config();
                $configValue->id = $rawConfigValue['id'];
                $configValue->inheritable = $rawConfigValue['inheritable'];
                $configValue->sensitive = $rawConfigValue['sensitive'];
                $configValue->state = $rawConfigValue['state'];
                $configValue->project = $rawConfigValue['project'];
                $configValue->projectEnvironment = (string)$rawConfigValue['project_environment'];
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
        $project->team = $rawProject['workspace'];
        $project->defaultEnvironmentId = $rawProject['default_environment'];
        $project->state = $rawProject['state'];

        return $project;
    }

    private function parseProjectEnvironment(array $rawEnvironment): ProjectEnvironment
    {
        $projectEnvironment = new ProjectEnvironment();
        $projectEnvironment->id = (string)$rawEnvironment['id'];
        $projectEnvironment->key = $rawEnvironment['key'];
        $projectEnvironment->name = $rawEnvironment['name'];
        $projectEnvironment->projectId = $rawEnvironment['project'];
        $projectEnvironment->isLocal = $rawEnvironment['is_local'];

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
        $rawProjects = $rawProjects['data'];
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
        $rawEnvironments = $rawEnvironments['data'];
        foreach ($rawEnvironments as $rawEnvironment) {
            $projectEnvironments[] = $this->parseProjectEnvironment($rawEnvironment);
        }

        return $projectEnvironments;
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

    public function getConnectionEndpoint(): ConnectionEndpoint
    {
        return $this->connectionEndpoint;
    }
}
