<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

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
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/configvalues';

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
