<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Model\Exception\RequestException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;

class Client
{
    private $endPoint;
    private $client;

    private $authorizationCode;
    private $clientId;
    private $clientSecret;

    private $bearerToken;

    private $branchCode;

    private $debug = false;

    public function __construct(string $endPoint)
    {
        if (empty($endPoint)) {
            throw new \InvalidArgumentException('Endpoint cannot be empty');
        }
        $this->endPoint = $endPoint;

        try {
            $this->client = new GuzzleClient([
                'base_uri' => $endPoint,
                'timeout'  => 20.0,
            ]);
        } catch (\Throwable $ex) {
        }
    }

    public function enableDebug()
    {
        $this->debug = true;
    }

    public function disableDebug()
    {
        $this->debug = false;
    }

    public function setCredentials(string $authorizationCode, string $clientId, string $clientSecret)
    {
        if (empty($authorizationCode)) {
            throw new \InvalidArgumentException('AuthorizationCode cannot be empty');
        }
        if (empty($clientId)) {
            throw new \InvalidArgumentException('ClientId cannot be empty');
        }
        if (empty($clientSecret)) {
            throw new \InvalidArgumentException('ClientSecret secret cannot be empty');
        }
        $this->authorizationCode = $authorizationCode;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function setBranch(string $branchCode)
    {
        if (empty($branchCode)) {
            throw new \InvalidArgumentException('Branch code cannot be empty');
        }
        $this->branchCode = $branchCode;
    }

    private function requestToken()
    {
        $options = [
            'headers'     => [
                'Authorization' => 'Basic ' . \base64_encode($this->clientId . ':' . $this->clientSecret),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code'       => $this->authorizationCode,
            ],
            'debug'       => $this->debug,

        ];

        $response = $this->client->request('POST', '/oauth/token', $options);

        $response = $response->getBody()
                             ->getContents();

        $response = \json_decode($response, true);

        $token = $response['access_token'];

        return $token;
    }

    private function getToken()
    {
        if (\is_null($this->bearerToken)) {
            $this->bearerToken = $this->requestToken();
        }

        return $this->bearerToken;
    }

    public function createPostRequest(string $uri, array $body): Request
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type'  => 'application/json',

        ];

        $body = \json_encode($body);

        return new Request('POST', $uri, $headers, $body);
    }

    public function createGetRequest(string $uri): Request
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type'  => 'application/json',

        ];

        return new Request('GET', $uri, $headers);
    }

    private function sendRequest(Request $request)
    {
        try {
            $response = $this->client->send($request, ['debug' => $this->debug]);

            $jsonResponse = \json_decode($response->getBody()
                                                  ->getContents(), true);
        } catch (\Throwable $ex) {
            throw new RequestException($ex->getMessage());
        }

        return $jsonResponse;
    }

    public function scheduleTask(string $task, array $arguments = [], bool $wait = false)
    {
        $request = $this->createScheduleTaskRequest($task, $arguments);
        $response = $this->sendRequest($request);

        return $response;
    }

    public function ping()
    {
        $request = $this->createGetRequest('/ping');
        $response = $this->sendRequest($request);

        return $response;
    }

    public function createScheduleTaskRequest(string $task, array $arguments)
    {
        $body = [
            'method'    => $task,
            'arguments' => $arguments,
        ];

        return $this->createPostRequest('/task/execute?branch=' . $this->branchCode, $body);
    }

}