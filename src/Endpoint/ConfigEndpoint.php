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
     * @param string|null $projectEnvironmentId
     * @return CollectionResult<Config>
     * @throws RequestException
     */
    public function getConfigByProject(string|null $projectEnvironmentId = null, CursorPagination|null $pagination = null): CollectionResult
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
