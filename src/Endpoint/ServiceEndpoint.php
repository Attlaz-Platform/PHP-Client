<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;


use Attlaz\Model\Service\ServiceCommand;
use GuzzleHttp\Client as HttpClient;


class ServiceEndpoint extends Endpoint
{


    public function chatGtp(string $prompt): string
    {

        $command = new ServiceCommand();
        $command->service = 'openai';
        $command->command = 'prompt';

        $command->addArgument('prompt', $prompt);
        $command->addArgument('model', 'gpt-4o');


        $result = $this->sendCommand($command);

//        var_dump($result);
        return $result;
    }

    public function sendCommand(ServiceCommand $command): string
    {
        $options = [];
        $body = \json_encode($command->toJson(), JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
        $options['body'] = $body;
        $options['headers'] = ['Content-Type' => 'application/json'];

        $cl = new HttpClient();
        $resp = $cl->post('https://up-shiner-notably.ngrok-free.app/command', $options);

        $result = json_decode($resp->getBody()->getContents(), true);

//        var_dump($result);
        return $result['result']['data'];
    }
}