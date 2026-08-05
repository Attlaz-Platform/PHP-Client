<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Http\Path;

use Attlaz\Model\CollectionResult;
use Attlaz\Model\Config;
use Attlaz\Model\CursorPagination;
use Attlaz\Model\Exception\RequestException;


class ConfigEndpoint extends Endpoint
{


    /**
     * Config values resolved for one project environment, parents included.
     *
     * The id is required: the route is `/projectenvironments/:projectEnvironmentId/configvalues` and
     * the API rejects a request without it. It used to default to null, which built
     * `/projectenvironments//configvalues` — a request that could never succeed.
     *
     * Named for the environment it takes, matching the JavaScript client's
     * `getByProjectEnvironment()`. The old name said "by project" while taking an environment id.
     *
     * @return CollectionResult<Config>
     * @throws RequestException
     */
    public function getByProjectEnvironment(string $projectEnvironmentId, CursorPagination|null $pagination = null): CollectionResult
    {
        $uri = Path::build('/projectenvironments/:projectEnvironmentId/configvalues', ['projectEnvironmentId' => $projectEnvironmentId]);

        $parser = static function (array $rawConfigValue): Config {
            $configValue = new Config();
            $configValue->id = (string)$rawConfigValue['id'];
            $configValue->inheritable = $rawConfigValue['inheritable'];
            $configValue->sensitive = $rawConfigValue['sensitive'];
            $configValue->state = $rawConfigValue['state'];

            if ($rawConfigValue['project'] !== null) {
                $configValue->project = $rawConfigValue['project'];
            }

            $configValue->projectEnvironment = $rawConfigValue['project_environment'];

            $configValue->key = $rawConfigValue['key'];
            $configValue->value = $rawConfigValue['value'];

            return $configValue;
        };

        return $this->requestCollection($uri, $pagination, $parser);
    }

}
