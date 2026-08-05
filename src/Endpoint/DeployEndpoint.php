<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Http\Path;


class DeployEndpoint extends Endpoint
{

    public function requestDeploy(string $codeSourceId): int
    {
        $uri = Path::build('/codesources/:codeSourceId/deploys', ['codeSourceId' => $codeSourceId]);


        $rawDeploy = $this->requestObject($uri, null, 'POST');

        if (!\is_null($rawDeploy) && isset($rawDeploy['id'])) {
            return $rawDeploy['id'];
        }
        throw new \Exception('Something went wrong');
    }

}
