<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Client;
use Attlaz\Model\StorageItem;

class StorageEndpoint
{
    private array $data = [];

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

    public function hasValue(string $key, ?string $pool = null): bool
    {
        return isset($this->data[$key]);
    }

    public function setValue(string $projectEnvironmentId, string $storageType, StorageItem $storageItem, ?string $pool = null):bool
    {
        // TODO: how to handle overrides?
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items/' . $storageItem->key;

        $request = $this->client->createRequest('POST', $uri, ['storage_item' => $storageItem]);

        $rawResult = $this->client->sendRequest($request);
//        \var_dump($rawResult['data']['succes']);
        return $rawResult['data']['succes'];
    }

    public function getKeys(?string $pool = null): array
    {
        return \array_keys($this->data);
    }

    public function delValue(string $key, ?string $pool = null): bool
    {
        unset($this->data[$key]);
        return true;
    }

    public function delValues(array $keys, ?string $pool = null): array
    {
        foreach ($keys as $key) {
            unset($this->data[$key]);
        }
        return [true];
    }
}
