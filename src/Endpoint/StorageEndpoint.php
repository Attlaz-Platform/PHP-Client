<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;


use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\StorageItem;
use DateTimeInterface;


class StorageEndpoint extends Endpoint
{


    public function getItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, ?string $poolKey = null): ?StorageItem
    {

        if (!empty($poolKey)) {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/' . $poolKey . '/items/' . $storageItemKey;
        } else {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items/' . $storageItemKey;
        }


        try {
            $rawItem = $this->requestObject($uri);

            if ($rawItem === null) {
                return null;
            }


            $item = new StorageItem();
            $item->key = $rawItem['key'];
            if (is_array($rawItem['value'])) {
                $item->value = $this->thawValue($rawItem['value']);
            } else {
                $item->value = $rawItem['value'];
            }
            if ($rawItem['expiration'] !== null) {
                $item->expiration = \DateTime::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $rawItem['expiration']);
            }

            return $item;
        } catch (RequestException $ex) {
            if ($ex->httpCode === 404) {
                return null;
            }
            throw  $ex;
        }

    }

    public function hasItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, ?string $poolKey = null): bool
    {
        return $this->getItem($projectEnvironmentId, $storageType, $storageItemKey, $poolKey) !== null;
    }

    private function freezeValue(mixed $value): array|string
    {
        if (is_object($value) || is_array($value)) {
            return ['method' => 'serialize', 'value' => \serialize($value)];
        }
        return $value;
    }

    public function thawValue(array $input): mixed
    {
        if (isset($input['method'])) {
            if (!isset($input['value'])) {
                throw new \Exception('Unable to thaw value: value not defined');
            }
            switch ($input['method']) {
                case 'serialize':
                    return \unserialize($input['value']);
                case 'json':
                    return \json_decode($input['value'], true);
                default:
                    throw new \Exception('Unable to thaw value: method "' . $input['method'] . '" not recognized');
            }
        }
        return $input;
    }

    public function setItem(string $projectEnvironmentId, string $storageType, StorageItem $storageItem, ?string $poolKey = null): bool
    {
        // TODO: how to handle overrides?
        if (!empty($poolKey)) {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/' . $poolKey . '/items/' . $storageItem->key;
        } else {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items/' . $storageItem->key;
        }

        $data = clone $storageItem;
        $data->value = $this->freezeValue($data->value);


        $rawResult = $this->requestObject($uri, $data, 'POST');

//        if (isset($rawResult['data']) && isset($rawResult['data']['success'])) {
//            return $rawResult['data']['success'];
//        }
//        if (isset($rawResult['errors']) && count($rawResult['errors']) > 0) {
//            return false;
//        }
//        throw new \Exception('Invalid response');

        return true;
    }

    /**
     * @return string[]
     */
    public function getItemKeys(string $projectEnvironmentId, string $storageType, ?string $poolKey = null): array
    {
        if (!empty($poolKey)) {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/' . $poolKey . '/items';
        } else {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items';
        }


        $rawItem = $this->requestObject($uri);

        if (isset($rawItem['data']) && isset($rawItem['data']['item_keys'])) {
            // TODO: remove this fallback to old method once no longer needed
            return $rawItem['data']['item_keys'];
        }
        return $rawItem['data'];
    }

    public function deleteItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, ?string $poolKey = null): bool
    {
        if (!empty($poolKey)) {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/' . $poolKey . '/items/' . $storageItemKey;
        } else {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items/' . $storageItemKey;
        }


        $rawItem = $this->requestObject($uri, null, 'DELETE');

        if (isset($rawItem['deleted'])) {
            return $rawItem['deleted'];
        }
        throw new \Exception('Invalid response');
    }

    public function deleteItems(string $projectEnvironmentId, string $storageType, array $storageItemKeys, ?string $poolKey = null): array
    {
        $result = [];
        foreach ($storageItemKeys as $storageItemKey) {
            $result[$storageItemKey] = $this->deleteItem($projectEnvironmentId, $storageType, $storageItemKey, $poolKey);
        }
        return $result;
    }

    /**
     * @return string[]
     */
    public function getPoolKeys(string $projectEnvironmentId, string $storageType): array
    {
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType;


        $rawItem = $this->requestObject($uri);

        if (\is_null($rawItem['pools'])) {
            throw new \Exception('Invalid response');
        }

        $rawPools = $rawItem['pools'];

        $result = [];
        foreach ($rawPools as $rawPool) {
            $result[] = $rawPool['name'];
        }
        return $result;
    }

    public function clearPool(string $projectEnvironmentId, string $storageType, ?string $poolKey = null): bool
    {
        if (!empty($poolKey)) {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/' . $poolKey;
        } else {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType;
        }


        $rawItem = $this->requestObject($uri, null, 'DELETE');


        if (isset($rawItem['deleted'])) {
            return $rawItem['deleted'];
        }

        throw new \Exception('Invalid response');
    }
}
