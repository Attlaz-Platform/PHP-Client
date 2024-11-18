<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Model\Config;
use Attlaz\Model\Exception\RequestException;


class ConfigEndpoint extends Endpoint
{


    /**
     * @param string $projectId
     * @param int|null $projectEnvironmentId
     * @return Config[]
     * @throws RequestException
     */
    public function getConfigByProject(string|null $projectEnvironmentId = null): array
    {
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/configvalues';


        $rawConfigValues = $this->requestCollection($uri);
        $result = [];


        foreach ($rawConfigValues as $rawConfigValue) {
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

            $result[] = $configValue;
        }


        return $result;
    }

}
