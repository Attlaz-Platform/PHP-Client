<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Client;
use Attlaz\Model\CollectionResult;
use Attlaz\Model\CursorPagination;
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

    /**
     * The response body as raw bytes. For a resource whose representation is binary — a stored
     * image, an archive — where decoding would destroy it.
     *
     * @param array<string, string> $headers
     */
    public function requestBytes(string $uri, string $method = 'GET', string|null $body = null, array $headers = []): string
    {
        $request = $this->client->createBinaryRequest($method, $uri, $body, $headers);

        return $this->client->sendBinaryRequest($request);
    }

    /**
     * Fetch one page of a cursor-paginated collection endpoint. Pass a $pagination to
     * control limit/cursor; inspect CollectionResult::$hasMore to know whether more pages
     * exist. To collect an entire collection, use {@see \Attlaz\Helper\LoadAllHelper::loadAll()}.
     *
     * @template T
     * @param (callable(array): T)|null $parser maps each raw record to a model; null returns raw arrays
     * @return CollectionResult<T>
     */
    public function requestCollection(string $uri, CursorPagination|null $pagination = null, callable|null $parser = null): CollectionResult
    {
        $uri = $this->appendPaginationQuery($uri, $pagination);
        $request = $this->createRequest('GET', $uri);

        $response = $this->client->sendRequest($request);

        if (!isset($response['data'])) {
            throw new \Exception('Unable to parse collection: data is not defined');
        }
        if (!isset($response['has_more'])) {
            throw new \Exception('Unable to parse collection: has_more is not defined');
        }

        $this->parseErrors($response);

        $hasMore = (bool)$response['has_more'];
        $data = $response['data'];
        $items = $parser === null ? $data : $this->parseCollection($data, $parser);

        return new CollectionResult($items, $hasMore);
    }

    /**
     * Append cursor pagination to a uri as query parameters (`limit` / `starting_after` /
     * `ending_before`), mirroring the JS client's QueryString::addPagination. Handles uris
     * that already carry a query string.
     */
    protected function appendPaginationQuery(string $uri, CursorPagination|null $pagination): string
    {
        if ($pagination === null) {
            return $uri;
        }
        $query = [];
        if ($pagination->limit !== null) {
            $query['limit'] = $pagination->limit;
        }
        if ($pagination->startingAfter !== null && $pagination->startingAfter !== '') {
            $query['starting_after'] = $pagination->startingAfter;
        }
        if ($pagination->endingBefore !== null && $pagination->endingBefore !== '') {
            $query['ending_before'] = $pagination->endingBefore;
        }
        if (count($query) === 0) {
            return $uri;
        }
        $separator = \str_contains($uri, '?') ? '&' : '?';

        return $uri . $separator . \http_build_query($query);
    }

    /**
     * @param array<int, array<string, mixed>> $data
     * @param callable(array<string, mixed>): mixed $parser
     * @return list<mixed>
     */
    private function parseCollection(array $data, callable $parser): array
    {
        $result = [];
        foreach ($data as $record) {
            $result[] = $parser($record);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|null the decoded JSON response body, or null on 404
     */
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

    /**
     * A 2xx response that still carries `errors`. Rare — a failing request normally throws a
     * RequestException from sendRequest long before this.
     *
     * @param array<string,mixed> $rawResponse
     */
    private function parseErrors(array $rawResponse): void
    {
        if (!isset($rawResponse['errors']) || !\is_array($rawResponse['errors']) || \count($rawResponse['errors']) === 0) {
            return;
        }

        throw new \Exception('API returned errors: ' . self::describeErrors($rawResponse['errors']));
    }

    /**
     * Render the `errors` payload as a message. Previously the raw array was handed to
     * `new \Exception(...)`, which takes a string — so a real API error surfaced as a TypeError and
     * the actual message was lost.
     *
     * @param array<mixed> $errors
     */
    private static function describeErrors(array $errors): string
    {
        $parts = [];
        foreach ($errors as $error) {
            if (\is_string($error)) {
                $parts[] = $error;
                continue;
            }
            if (\is_array($error) && isset($error['message']) && \is_string($error['message'])) {
                $parts[] = $error['message'];
                continue;
            }
            $parts[] = \json_encode($error, JSON_UNESCAPED_SLASHES) ?: 'unprintable error';
        }

        return \implode('; ', $parts);
    }


}
