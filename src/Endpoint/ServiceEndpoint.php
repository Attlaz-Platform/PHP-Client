<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Http\Path;


use Attlaz\Model\Service\ServiceOperationRequest;


class ServiceEndpoint extends Endpoint
{
    public function getCapabilities(string $connectionId): array
    {
        $uri = Path::build('/services/:connectionId/capabilities', ['connectionId' => $connectionId]);
        $response = $this->requestObject($uri);
        $data = self::requireData($response, $uri);
        if (!\is_array($data)) {
            throw new \Exception('Expected a list of capabilities from `' . $uri . '`, got ' . \get_debug_type($data));
        }

        return $data;
    }

    public function openAI(string $connectionId, string $prompt, string $model = 'gtp-4o'): string
    {

        $command = new ServiceOperationRequest($connectionId, 'prompt');

        $command->addArgument('prompt', $prompt);
        $command->addArgument('model', $model);

        return $this->sendServiceOperationRequest($command);
    }

    public function sendServiceOperationRequest(ServiceOperationRequest $command): string|array
    {
        $uri = Path::build('/services/:connectionId/:operation', ['connectionId' => $command->connectionId, 'operation' => $command->operation]);
        $response = $this->requestObject($uri, $command->toJson(), 'POST');

        // TODO: validate response
        $data = self::requireData($response, $uri);
        if (!\is_string($data) && !\is_array($data)) {
            throw new \Exception('Unexpected service operation result from `' . $uri . '`: ' . \get_debug_type($data));
        }

        return $data;
    }

    /**
     * requestObject() returns null on a 404 and the response need not carry `data`. Reading the key
     * straight off that gave "Trying to access array offset on null" followed by a TypeError from the
     * return type — an error from inside the client rather than a description of what went wrong.
     *
     * @param array<string,mixed>|null $response
     */
    private static function requireData(array|null $response, string $uri): mixed
    {
        if ($response === null) {
            throw new \Exception('No service response for `' . $uri . '` (not found)');
        }
        if (!\array_key_exists('data', $response)) {
            throw new \Exception('Service response for `' . $uri . '` contains no data');
        }

        return $response['data'];
    }
}
