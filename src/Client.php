<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Helper\TokenStorage;
use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\LogEntry;
use Attlaz\Model\Project;
use Attlaz\Model\ProjectEnvironment;
use Attlaz\Model\TaskExecutionResult;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;

class Client
{
    private $endPoint;
    private $clientId;
    private $clientSecret;
    private $storeToken = false;
    private $timeout = 20;

    private $debug = false;

    /** @var GenericProvider */
    private $provider;

    private $accessToken;

    public function __construct(string $endPoint, string $clientId, string $clientSecret, bool $storeToken = false)
    {
        if (empty($endPoint)) {
            throw new \InvalidArgumentException('Endpoint cannot be empty');
        }
        $this->endPoint = rtrim($endPoint, '/');

        if (empty($clientId)) {
            throw new \InvalidArgumentException('ClientId cannot be empty');
        }
        $this->clientId = $clientId;

        if (empty($clientSecret)) {
            throw new \InvalidArgumentException('ClientSecret secret cannot be empty');
        }
        $this->clientSecret = $clientSecret;
        $this->storeToken = $storeToken;
    }

    private function authenticate()
    {
        if (\is_null($this->accessToken)) {
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
    }

    public function setAccessToken(AccessToken $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    private function createRequest(string $method, string $uri, $body = null): RequestInterface
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

    private function sendRequest(RequestInterface $request)
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

    public function scheduleTask(string $taskId, array $arguments = []): TaskExecutionResult
    {
        $body = [
            'arguments' => $arguments,
        ];

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

    public function saveLog(LogEntry $logEntry): bool
    {
        $body = $logEntry;

        $uri = '/system/logs';

        $request = $this->createRequest('POST', $uri, $body);

        $response = $this->sendRequest($request);

        if (isset($response['_id']) && !empty($response['_id'])) {
            return true;
        }

        return false;
    }

    public function createTaskExecution(string $taskId, int $projectEnvironmentId): string
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

    public function getConfigByProject(string $projectId, int $projectEnvironmentId = null): array
    {
        $uri = '/projects/' . $projectId . '/config';

        if (!\is_null($projectEnvironmentId)) {
            $uri = $uri . '?environment=' . $projectEnvironmentId;
        }

        $request = $this->createRequest('GET', $uri);

        $response = $this->sendRequest($request);

        //TODO: parse configuration
        return $response;
    }

    public function getProjectById(string $projectId): Project
    {
        $uri = '/projects';

        $request = $this->createRequest('GET', $uri);

        $rawProjects = $this->sendRequest($request);

        foreach ($rawProjects as $rawProject) {
            if ($rawProject['id'] === $projectId) {
                $project = new Project();
                $project->id = $rawProject['id'];
                $project->key = $rawProject['key'];
                $project->name = $rawProject['name'];
                $project->state = $rawProject['state'];

                return $project;
            }
        }
        throw new \Exception('No project with id "' . $projectId . '" found');
    }

    public function getProjectEnvironmentById(int $projectEnvironmentId): ProjectEnvironment
    {
        $uri = '/projectenvironments/' . $projectEnvironmentId;

        $request = $this->createRequest('GET', $uri);

        //TODO: handle when environment is not found
        $rawEnvironment = $this->sendRequest($request);

        $projectEnvironment = new ProjectEnvironment();
        $projectEnvironment->id = $rawEnvironment['id'];
        $projectEnvironment->key = $rawEnvironment['key'];
        $projectEnvironment->name = $rawEnvironment['name'];
        $projectEnvironment->projectId = $rawEnvironment['projectId'];
        $projectEnvironment->isLocal = $rawEnvironment['isLocal'];

        return $projectEnvironment;
    }

    public function enableDebug()
    {
        $this->debug = true;
    }

    public function disableDebug()
    {
        $this->debug = false;
    }

}
