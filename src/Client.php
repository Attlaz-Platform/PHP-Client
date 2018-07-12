<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\ScheduleTaskResult;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Http\Message\RequestInterface;

class Client
{
    private $endPoint;
    private $branchId;

    private $debug = false;
    private $provider;

    private $accessToken;

    public function __construct(string $endPoint, string $clientId, string $clientSecret)
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
        $this->accessToken = $this->provider->getAccessToken('client_credentials');
    }

    public function enableDebug()
    {
        $this->debug = true;
    }

    public function disableDebug()
    {
        $this->debug = false;
    }

    public function setBranch(string $branchCode)
    {
        if (empty($branchCode)) {
            throw new \InvalidArgumentException('Branch code cannot be empty');
        }
        $this->branchId = $branchCode;
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

    public function scheduleTask(string $command, array $arguments = [], bool $wait = false): ScheduleTaskResult
    {
        $body = [
            'command'   => $command,
            'arguments' => $arguments,
        ];

        $uri = '/branch/' . $this->branchId . '/taskexecutionrequest';
        if ($wait) {
            $uri = $uri . '?wait=true';
        }
        $request = $this->createRequest('POST', $uri, $body);

        $response = $this->sendRequest($request);

        //TODO: handle issues
        $success = ((string)$response['success'] === "true");

        $data = json_decode($response['result'], true);
        $data = $data['data'];

        $result = new ScheduleTaskResult($success, $response['taskExecutionRequest']);
        $result->result = $data;

        return $result;
    }

    public function ping()
    {
        $request = $this->createRequest('GET', '/ping');
        $response = $this->sendRequest($request);

        return $response;
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

}