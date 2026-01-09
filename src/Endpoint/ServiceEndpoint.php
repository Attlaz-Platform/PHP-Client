<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;


use Attlaz\Model\Service\ServiceOperationRequest;


class ServiceEndpoint extends Endpoint
{
    public function openAI(string $connectionId, string $prompt, string $model = 'gtp-4o'): string
    {

        $command = new ServiceOperationRequest($connectionId, 'prompt');

        $command->addArgument('prompt', $prompt);
        $command->addArgument('model', $model);

        return $this->sendServiceOperationRequest($command);
    }

    public function sendServiceOperationRequest(ServiceOperationRequest $command): string|array
    {
        $response = $this->requestObject('https://gateway.api.attlaz.com/services/' . $command->connectionId . '/test', $command->toJson(), 'POST');

        // TODO: validate response
        return $response['data'];
    }
}
