<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Client;
use Attlaz\Model\StorageItem;

class StorageEndpoint
{
    /** @var Client */
    private $client;

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

        if (isset($rawItem['data']) && isset($rawItem['data']['item'])) {
            $rawItem = $rawItem['data']['item'];


            $item = new StorageItem();
            $item->key = $rawItem['key'];
            $item->value = $this->thawValue($rawItem['value']);

            if ($rawItem['expiration'] !== null) {
                $rawItem['expiration'] = \DateTime::createFromFormat(\DateTime::RFC3339_EXTENDED, $rawItem['expiration']);
            }
            return $item;

        }

        return null;
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


        $storageItem->value = $this->freezeValue($storageItem->value);

        $request = $this->client->createRequest('POST', $uri, ['storage_item' => $storageItem]);

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
            return $rawItem['data']['item_keys'];

        }

        throw new \Exception('Invalid response');
    }

    public function deleteItem(string $projectEnvironmentId, string $storageType, string $key, ?string $poolKey = null): bool
    {
        if (!empty($poolKey)) {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/' . $poolKey . '/items/' . $key;
        } else {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items/' . $key;
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

    public function deleteItems(string $projectEnvironmentId, string $storageType, array $keys, ?string $poolKey = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->deleteItem($projectEnvironmentId, $storageType, $key, $poolKey);
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

        if (isset($rawItem['data']) && isset($rawItem['data']['pools'])) {
            $rawPools = $rawItem['data']['pools'];
            $result = [];
            foreach ($rawPools as $rawPool) {
                $result[] = $rawPool['name'];
            }
            return $result;

        }

        throw new \Exception('Invalid response');
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

        if (isset($rawItem['deleted'])) {
            return $rawItem['deleted'];
        }
//        if (isset($rawItem['data']) && isset($rawItem['data']['success'])) {
//            return $rawItem['data']['success'];
//        }
//        if (isset($rawResult['errors']) && count($rawResult['errors']) > 0) {
//            return false;
//        }
        throw new \Exception('Invalid response');
    }
}
