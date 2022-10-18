<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Client;
use Attlaz\Model\StorageItem;
use DateTimeInterface;

class StorageEndpoint
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, ?string $poolKey = null): ?StorageItem
    {

        if (!empty($poolKey)) {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/' . $poolKey . '/items/' . $storageItemKey;
        } else {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items/' . $storageItemKey;
        }

        $request = $this->client->createRequest('GET', $uri);

        $rawItem = $this->client->sendRequest($request);

        if (is_null($rawItem['data'])) {
            return null;
        }

        if (isset($rawItem['data']) && isset($rawItem['data']['item'])) {
            // TODO: remove this fallback to old method once no longer needed
            $rawItem = $rawItem['data']['item'];


        } else {
            $rawItem = $rawItem['data'];
        }


        $item = new StorageItem();
        $item->key = $rawItem['key'];
        $item->value = $this->thawValue($rawItem['value']);

        if ($rawItem['expiration'] !== null) {
            $rawItem['expiration'] = \DateTime::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $rawItem['expiration']);
        }
        return $item;
    }

    public function hasItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, ?string $poolKey = null): bool
    {
        return $this->getItem($projectEnvironmentId, $storageType, $storageItemKey, $poolKey) !== null;
    }

    private function freezeValue($value): array
    {
        return ['method' => 'serialize', 'value' => \serialize($value)];
    }

    public function thawValue(array $input)
    {
        if (isset($input['method'])) {
            if (!isset($input['value'])) {
                throw new \Exception('Unable to thaw value: value not defined');
            }
            switch ($input['method']) {
                case 'serialize':
                    return \unserialize($input['value']);
                    break;
                case 'json':
                    return \json_decode($input['value'], true);
                    break;
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

        $request = $this->client->createRequest('POST', $uri, $data);

        $rawResult = $this->client->sendRequest($request);

        if (isset($rawResult['data']) && isset($rawResult['data']['success'])) {
            return $rawResult['data']['success'];
        }
        if (isset($rawResult['errors']) && count($rawResult['errors']) > 0) {
            return false;
        }
        throw new \Exception('Invalid response');
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

        $request = $this->client->createRequest('GET', $uri);

        $rawItem = $this->client->sendRequest($request);

        if (isset($rawItem['data']) && isset($rawItem['data']['item_keys'])) {
            // TODO: remove this fallback to old method once no longer needed
            return $rawItem['data']['item_keys'];
        }
        return $rawItem['data'];

        throw new \Exception('Invalid response');
    }

    public function deleteItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, ?string $poolKey = null): bool
    {
        if (!empty($poolKey)) {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/' . $poolKey . '/items/' . $storageItemKey;
        } else {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items/' . $storageItemKey;
        }

        $request = $this->client->createRequest('DELETE', $uri);

        $rawItem = $this->client->sendRequest($request);

        if (isset($rawItem['data']) && isset($rawItem['data']['success'])) {
            return $rawItem['data']['success'];
        }
        if (isset($rawResult['errors']) && count($rawResult['errors']) > 0) {
            return false;
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

        $request = $this->client->createRequest('GET', $uri);

        $rawItem = $this->client->sendRequest($request);

        if (\is_null($rawItem['data'])) {
            throw new \Exception('Invalid response');
        }

        $rawPools = $rawItem['data'];
        if (isset($rawItem['data']) && isset($rawItem['data']['pools'])) {
            // TODO: remove this fallback to old method once no longer needed
            $rawPools = $rawItem['data']['pools'];


        }
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

        $request = $this->client->createRequest('DELETE', $uri);

        $rawItem = $this->client->sendRequest($request);

        if (isset($rawItem['data']) && isset($rawItem['data']['success'])) {
            return $rawItem['data']['success'];
        }
        if (isset($rawResult['errors']) && count($rawResult['errors']) > 0) {
            return false;
        }
        throw new \Exception('Invalid response');
    }
}
