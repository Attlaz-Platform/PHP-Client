<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;


use Attlaz\Model\Service\ServiceCommand;


class ServiceEndpoint extends Endpoint
{
    public function openAI(string $prompt, string $model = 'gtp-4o'): string
    {

        $command = new ServiceCommand();
        $command->service = 'openai';
        $command->command = 'prompt';

        $command->addArgument('prompt', $prompt);
        $command->addArgument('model', $model);

        return $this->sendCommand($command);
    }

    public function sendCommand(ServiceCommand $command): string|array
    {
        $response = $this->requestObject('https://services.api.attlaz.com/command', $command->toJson(), 'POST');
        // TODO: validate response
        return $response['data'];
    }
}
