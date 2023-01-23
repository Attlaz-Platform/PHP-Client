<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;


class DeployEndpoint extends Endpoint
{

    public function requestDeploy(string $codeSourceId): int
    {
        $uri = '/codesources/' . $codeSourceId . '/deploys';


        $rawDeploy = $this->requestObject($uri, null, 'POST');

        if (!\is_null($rawDeploy) && isset($rawDeploy['id'])) {
            return $rawDeploy['id'];
        }
        throw new \Exception('Something went wrong');
    }

}
