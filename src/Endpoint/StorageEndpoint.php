<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;


use Attlaz\Model\CollectionResult;
use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\StorageItem;
use Attlaz\Model\StorageItemInformation;
use DateTimeInterface;


class StorageEndpoint extends Endpoint
{


    public function getItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, ?string $bucketKey = null): ?StorageItem
    {

        if (!empty($bucketKey)) {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/' . $bucketKey . '/items/' . $storageItemKey;
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

    public function hasItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, ?string $bucketKey = null): bool
    {
        return $this->getItem($projectEnvironmentId, $storageType, $storageItemKey, $bucketKey) !== null;
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

    public function setItem(string $projectEnvironmentId, string $storageType, StorageItem $storageItem, ?string $bucketKey = null): bool
    {
        // TODO: how to handle overrides?
        if (!empty($bucketKey)) {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/' . $bucketKey . '/items/' . $storageItem->key;
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
     * Fetch one page of item-information records for the (optional) bucket.
     *
     * Mirrors the JS client's getBucketItemsInformation. This endpoint returns
     * item-information records (key, bytes, expiration, ...), NOT bare keys, and is
     * cursor-paginated: pass the previous page's last item id as $startingAfter to
     * fetch the next page, and inspect CollectionResult::$hasMore for further pages.
     *
     * To collect every key in a bucket, use StorageEngine::getItemKeys() which walks
     * all pages on top of this method.
     *
     * @return CollectionResult<StorageItemInformation>
     */
    public function getBucketItemsInformation(string $projectEnvironmentId, string $storageType, ?string $bucketKey = null, ?string $startingAfter = null, int $limit = 1000): CollectionResult
    {
        if (!empty($bucketKey)) {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/' . $bucketKey . '/items';
        } else {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items';
        }
        $uri .= '?limit=' . $limit;
        if ($startingAfter !== null) {
            $uri .= '&starting_after=' . \rawurlencode($startingAfter);
        }

        $rawResult = $this->requestObject($uri);
        if ($rawResult === null || !isset($rawResult['data']) || !\is_array($rawResult['data'])) {
            return new CollectionResult([], false);
        }

        $items = [];
        foreach ($rawResult['data'] as $record) {
            $items[] = StorageItemInformation::fromArray($record);
        }

        $hasMore = $rawResult['has_more'] ?? false;

        return new CollectionResult($items, (bool) $hasMore);
    }

    public function deleteItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, ?string $bucketKey = null): bool
    {
        if (!empty($bucketKey)) {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/' . $bucketKey . '/items/' . $storageItemKey;
        } else {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/items/' . $storageItemKey;
        }


        $rawItem = $this->requestObject($uri, null, 'DELETE');

        if (isset($rawItem['deleted'])) {
            return $rawItem['deleted'];
        }
        throw new \Exception('Invalid response');
    }

    public function deleteItems(string $projectEnvironmentId, string $storageType, array $storageItemKeys, ?string $bucketKey = null): array
    {
        $result = [];
        foreach ($storageItemKeys as $storageItemKey) {
            $result[$storageItemKey] = $this->deleteItem($projectEnvironmentId, $storageType, $storageItemKey, $bucketKey);
        }
        return $result;
    }

    /**
     * @return string[]
     */
    public function getBucketKeys(string $projectEnvironmentId, string $storageType): array
    {
        $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType;


        $rawItem = $this->requestObject($uri);

        // Prefer the new `buckets` field; fall back to the legacy `pools` for older API responses.
        $rawBuckets = $rawItem['buckets'] ?? $rawItem['pools'] ?? null;
        if (\is_null($rawBuckets)) {
            throw new \Exception('Invalid response');
        }

        $result = [];
        foreach ($rawBuckets as $rawBucket) {
            $result[] = $rawBucket['name'];
        }
        return $result;
    }

    public function clearBucket(string $projectEnvironmentId, string $storageType, ?string $bucketKey = null): bool
    {
        if (!empty($bucketKey)) {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType . '/' . $bucketKey;
        } else {
            $uri = '/projectenvironments/' . $projectEnvironmentId . '/storage/' . $storageType;
        }


        $rawItem = $this->requestObject($uri, null, 'DELETE');


        if (isset($rawItem['deleted'])) {
            return $rawItem['deleted'];
        }

        throw new \Exception('Invalid response');
    }

    /** @deprecated Renamed to getBucketItemsInformation(). */
    public function getPoolItemsInformation(string $projectEnvironmentId, string $storageType, ?string $bucketKey = null, ?string $startingAfter = null, int $limit = 1000): CollectionResult
    {
        return $this->getBucketItemsInformation($projectEnvironmentId, $storageType, $bucketKey, $startingAfter, $limit);
    }

    /** @deprecated Renamed to clearBucket(). */
    public function clearPool(string $projectEnvironmentId, string $storageType, ?string $bucketKey = null): bool
    {
        return $this->clearBucket($projectEnvironmentId, $storageType, $bucketKey);
    }

    /** @deprecated Renamed to getBucketKeys(). */
    public function getPoolKeys(string $projectEnvironmentId, string $storageType): array
    {
        return $this->getBucketKeys($projectEnvironmentId, $storageType);
    }

    private function freezeValue(mixed $value): array|string
    {
        // TODO: should there be a way to force php serialisation?
//        if (is_object($value) || is_array($value)) {
//            return ['method' => 'serialize', 'value' => \serialize($value)];
//        }
        if (is_object($value) || is_array($value)) {
            return ['method' => 'json', 'value' => json_encode($value)];
        }
        return $value;
    }
}
