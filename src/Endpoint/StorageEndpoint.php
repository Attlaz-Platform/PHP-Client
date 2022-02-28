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

    public function getValue(string $projectEnvironmentId, string $storageType, string $storageItemKey, ?string $pool = null): ?StorageItem
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

    public function hasValue(string $projectEnvironmentId, string $storageType, string $storageItemKey, ?string $pool = null): bool
    {
        return $this->getValue($projectEnvironmentId, $storageType, $storageItemKey, $pool) !== null;
    }

    public function setValue(string $projectEnvironmentId, string $storageType, StorageItem $storageItem, ?string $pool = null): bool
    {
        // TODO: how to handle overrides?
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items/' . $storageItem->key;

        $request = $this->client->createRequest('POST', $uri, ['storage_item' => $storageItem]);

        $rawResult = $this->client->sendRequest($request);
//        \var_dump($rawResult['data']['succes']);
        return $rawResult['data']['succes'];
    }

    public function getKeys(string $projectEnvironmentId, string $storageType, ?string $pool = null): array
    {
        throw new \Exception('Not implemented');
    }

    public function delValue(string $projectEnvironmentId, string $storageType, string $key, ?string $pool = null): bool
    {
        throw new \Exception('Not implemented');
    }

    public function delValues(string $projectEnvironmentId, string $storageType, array $keys, ?string $pool = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->delValue($projectEnvironmentId, $storageType, $key, $pool);
        }
        return $result;
    }
}
