<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Client;
use Attlaz\Model\Exception\RequestException;
use Psr\Http\Message\RequestInterface;


abstract class Endpoint
{


    public function __construct(private readonly Client $client)
    {

    }

    public function createRequest(string $method, string $uri, array|object|null $body = null): RequestInterface
    {
        return $this->client->createRequest($method, $uri, $body);
    }

    public function requestCollection(string $uri, array|object|null $body = null, string $method = 'GET', callable|null $parser = null): array
    {
        $request = $this->createRequest($method, $uri, $body);

        $response = $this->client->sendRequest($request);

        if (!isset($response['data'])) {
            throw new \Exception('Unable to parse collection: data is not defined');
        }
        if (!isset($response['has_more'])) {
            throw new \Exception('Unable to parse collection: hasMore is not defined');
        }

        $hasMore = $response['has_more'];
        if ($hasMore) {
            echo 'Has more: not implemented yet' . PHP_EOL;
        }

        $this->parseErrors($response);

        $data = $response['data'];
        if ($parser === null) {
            return $data;
        }
        return $this->parseCollection($data, $parser);
    }

    private function parseCollection(array $data, callable $parser): array
    {
        $result = [];
        foreach ($data as $record) {
            $result[] = $parser($record);
        }

        return $result;
    }

    public function requestObject(string $uri, array|object|null $body = null, string $method = 'GET'): array|null
    {
        $request = $this->createRequest($method, $uri, $body);


        try {
            $response = $this->client->sendRequest($request);
        } catch (RequestException $requestException) {

            if ($requestException->httpCode === 404) {
                return null;
            }
            throw $requestException;
        }


        $this->parseErrors($response);


        return $response;
    }

    private function parseErrors(array $rawResponse): void
    {
        if (isset($rawResponse['errors']) && count($rawResponse['errors']) > 0) {
            throw new \Exception($rawResponse['errors']);
        }
    }


}
