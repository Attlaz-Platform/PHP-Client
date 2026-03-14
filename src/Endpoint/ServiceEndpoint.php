<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;


use Attlaz\Model\Service\ServiceOperationRequest;


class ServiceEndpoint extends Endpoint
{
    public function getCapabilities(string $connectionId): array
    {
        $response = $this->requestObject('/services/' . $connectionId . '/capabilities');

        return $response['data'];
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
        $response = $this->requestObject('/services/' . $command->connectionId . '/' . $command->operation, $command->toJson(), 'POST');

        // TODO: validate response
        return $response['data'];
    }
}
