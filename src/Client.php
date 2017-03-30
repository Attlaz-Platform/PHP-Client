<?php
declare(strict_types=1);

namespace Attlaz;

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

    public function __construct(string $endPoint)
    {
        $this->endPoint = $endPoint;
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => $endPoint,
            'timeout'  => 20.0,
        ]);
    }

    public function setCredentials(string $authorizationCode, string $clientId, string $clientSecret)
    {
        $this->authorizationCode = $authorizationCode;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function setBranch(string $branchCode)
    {
        $this->branchCode = $branchCode;
    }

    private function requestToken()
    {
        $headers = [
            'headers'     => [
                'Authorization' => 'Basic ' . \base64_encode($this->clientId . ':' . $this->clientSecret),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code'       => $this->authorizationCode,
            ],

        ];

        $response = $this->client->request('POST', '/oauth/token', $headers);

        $response = $response->getBody()
                             ->getContents();

        $jsonResponse = \json_decode($response, true);

        $token = $jsonResponse['access_token'];

        return $token;
    }

    private function getToken()
    {
        if (\is_null($this->bearerToken)) {
            $this->bearerToken = $this->requestToken();
        }

        return $this->bearerToken;
    }

    public function scheduleTask(string $method, array $arguments = [])
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type'  => 'application/json',

        ];
        $body = [
            'method'    => $method,
            'arguments' => $arguments,
        ];
        $body = \json_encode($body);
        $request = new Request('POST', '/task/execute?branch=' . $this->branchCode, $headers, $body);

        $response = $this->client->send($request);

        $jsonResponse = \json_decode($response->getBody()
                                              ->getContents(), true);

//        var_dump($jsonResponse['success']);
        // $token = $jsonResponse['access_token'];

        //    echo 'Token: ' . $token . \PHP_EOL;
    }
}