<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Client;
use Attlaz\Model\StorageItem;

class StorageEndpoint
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, ?string $pool = null): ?StorageItem
    {
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items/' . $storageItemKey;

        $request = $this->client->createRequest('GET', $uri);

        $rawItem = $this->client->sendRequest($request);

        if (isset($rawItem['data']) && isset($rawItem['data']['item'])) {
            $rawItem = $rawItem['data']['item'];


            $item = new StorageItem();
            $item->key = $rawItem['key'];
            $item->value = $rawItem['value'];
            if ($rawItem['expiration'] !== null) {
                $rawItem['expiration'] = \DateTime::createFromFormat(\DateTime::RFC3339_EXTENDED, $rawItem['expiration']);
            }
            return $item;

        }

        return null;
    }

    public function hasItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, ?string $pool = null): bool
    {
        return $this->getItem($projectEnvironmentId, $storageType, $storageItemKey, $pool) !== null;
    }

    public function setItem(string $projectEnvironmentId, string $storageType, StorageItem $storageItem, ?string $pool = null): bool
    {
        // TODO: how to handle overrides?
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items/' . $storageItem->key;

        $request = $this->client->createRequest('POST', $uri, ['storage_item' => $storageItem]);

        $rawResult = $this->client->sendRequest($request);
//        \var_dump($rawResult['data']['succes']);
        if (isset($rawItem['data']) && isset($rawItem['data']['success'])) {
            return $rawItem['data']['success'];
        }
        throw new \Exception('Invalid response');
    }

    /**
     * @return string[]
     */
    public function getItemKeys(string $projectEnvironmentId, string $storageType, ?string $pool = null): array
    {
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items';

        $request = $this->client->createRequest('GET', $uri);

        $rawItem = $this->client->sendRequest($request);

        if (isset($rawItem['data']) && isset($rawItem['data']['item_keys'])) {
            return $rawItem['data']['item_keys'];

        }

        throw new \Exception('Invalid response');
    }

    public function deleteItem(string $projectEnvironmentId, string $storageType, string $key, ?string $pool = null): bool
    {
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items/' . $key;

        $request = $this->client->createRequest('DELETE', $uri);

        $rawItem = $this->client->sendRequest($request);

        if (isset($rawItem['data']) && isset($rawItem['data']['success'])) {
            return $rawItem['data']['success'];
        }

        throw new \Exception('Invalid response');
    }

    public function deleteItems(string $projectEnvironmentId, string $storageType, array $keys, ?string $pool = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->deleteItem($projectEnvironmentId, $storageType, $key, $pool);
        }
        return $result;
    }

    /**
     * @return string[]
     */
    public function getPoolKeys(string $projectEnvironmentId, string $storageType): array
    {
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items';

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

    public function clearPool(string $projectEnvironmentId, string $storageType, string $pool): bool
    {
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType;

        $request = $this->client->createRequest('DELETE', $uri);

        $rawItem = $this->client->sendRequest($request);

        if (isset($rawItem['data']) && isset($rawItem['data']['success'])) {
            return $rawItem['data']['success'];
        }

        throw new \Exception('Invalid response');
    }
}
