<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Helper\TokenStorage;
use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\LogEntry;
use Attlaz\Model\ScheduleTaskResult;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;

class Client
{
    private $endPoint;

    private $debug = false;
    private $provider;

    private $accessToken;

    private $storeToken = false;

    public function __construct(string $endPoint, string $clientId, string $clientSecret, bool $storeToken = false)
    {
        if (empty($endPoint)) {
            throw new \InvalidArgumentException('Endpoint cannot be empty');
        }
        if (empty($clientId)) {
            throw new \InvalidArgumentException('ClientId cannot be empty');
        }
        if (empty($clientSecret)) {
            throw new \InvalidArgumentException('ClientSecret secret cannot be empty');
        }
        $this->endPoint = $endPoint;
        $this->storeToken = $storeToken;

        $this->provider = new GenericProvider([
            'clientId'                => $clientId,
            'clientSecret'            => $clientSecret,
            'redirectUri'             => 'https://example.com/your-redirect-url/',
            'urlAuthorize'            => $endPoint . '/oauth/authorize',
            'urlAccessToken'          => $endPoint . '/oauth/token',
            'urlResourceOwnerDetails' => $endPoint . '/oauth/resource',
            'base_uri'                => $endPoint,
            'timeout'                 => 20.0,
        ]);
        $this->authenticate($clientId, $clientSecret, $storeToken);
    }

    private function authenticate(string $clientId, string $clientSecret, bool $storeToken)
    {
        $accessToken = null;
        if ($storeToken) {
            $accessToken = TokenStorage::loadAccessToken($clientId, $clientSecret);
        }

        if (!\is_null($accessToken)) {
            $this->accessToken = $accessToken;
        } else {
            $this->accessToken = $this->provider->getAccessToken('client_credentials', [
                'scope' => 'all',
            ]);
            if ($storeToken) {
                TokenStorage::saveAccessToken($this->accessToken, $clientId, $clientSecret);
            }
        }
    }

    public function setAccessToken(AccessToken $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    private function createRequest(string $method, string $uri, $body = null): RequestInterface
    {
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

    public function scheduleTaskByCommand(string $branch, string $command, array $arguments = []): ScheduleTaskResult
    {
        $body = [
            'command'   => $command,
            'arguments' => $arguments,
        ];

        $uri = '/branches/' . $branch . '/taskexecutionrequest';

        $request = $this->createRequest('POST', $uri, $body);

        $response = $this->sendRequest($request);

        //TODO: validate response & handle issues
        $success = ($response['success'] === true || $response['success'] === 'true');

        $data = null;
        if (isset($response['result']) && !empty($response['result'])) {
            $data = json_decode($response['result'], true);
            $data = $data['data'];
        }

        $result = new ScheduleTaskResult($success, $response['taskExecutionRequest']);
        $result->result = $data;

        return $result;
    }

    public function scheduleTask(string $task, array $arguments = []): ScheduleTaskResult
    {
        $body = [
            'arguments' => $arguments,
        ];

        $uri = '/tasks/' . $task . '/taskexecutionrequests';

        $request = $this->createRequest('POST', $uri, $body);

        $response = $this->sendRequest($request);

        //TODO: validate response & handle issues
        $success = ($response['success'] === true || $response['success'] === 'true');

        $data = json_decode($response['result'], true);
        $data = $data['data'];

        $result = new ScheduleTaskResult($success, $response['taskExecutionRequest']);
        $result->result = $data;

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

    public function enableDebug()
    {
        $this->debug = true;
    }

    public function disableDebug()
    {
        $this->debug = false;
    }

}