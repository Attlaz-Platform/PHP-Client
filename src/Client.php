<?php
declare(strict_types=1);

namespace Attlaz;

use Attlaz\Model\Exception\RequestException;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Http\Message\RequestInterface;

class Client
{
    private $endPoint;
    private $branchCode;

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
        $this->branchCode = $branchCode;
    }

    private function sendRequest(RequestInterface $request)
    {
        try {
            $response = $this->provider->getResponse($request);

            $jsonResponse = \json_decode($response->getBody()
                                                  ->getContents(), true);
        } catch (\Throwable $ex) {
            throw new RequestException($ex->getMessage());
        }

        return $jsonResponse;
    }

    public function scheduleTask(string $task, array $arguments = [], bool $wait = false)
    {
        $body = [
            'method'    => $task,
            'arguments' => $arguments,
        ];

        $request = $this->createRequest('POST', '/task/execute?branch=' . $this->branchCode, $body);

        $response = $this->sendRequest($request);

        return $response;
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

        $options['debug'] = $this->debug;

        return $this->provider->getAuthenticatedRequest($method, $this->endPoint . $uri, $this->accessToken, $options);
    }

}