<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Http\Path;


use Attlaz\Model\CollectionResult;
use Attlaz\Model\CursorPagination;
use Attlaz\Model\Exception\RequestException;
use Attlaz\Model\StorageItem;
use Attlaz\Model\StorageItemInformation;
use DateTimeInterface;


/**
 * Storage values are sent and returned as they are — no encoding on the way out, no decoding on the
 * way in. The platform records per item how it stored the value (plain, JSON, BSON or binary) and
 * hands it back in the same shape, so an array written here comes back an array.
 *
 * This client used to wrap objects and arrays in a `{method, value}` envelope, because in 2022 the
 * platform had no way to record how a value was encoded. It gained one in June 2025, which made the
 * envelope redundant — and it was never understood by the JavaScript client, so a value written here
 * could not be read there.
 *
 * One consequence: an object comes back as an associative array. Neither JSON nor BSON carries PHP
 * classes, so the original type is not restored.
 */
class StorageEndpoint extends Endpoint
{


    public function getItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, string|null $bucketKey = null): StorageItem|null
    {

        if (!empty($bucketKey)) {
            $uri = Path::build('/projectenvironments/:projectEnvironmentId/storage/:storageType/:bucketKey/items/:storageItemKey', ['projectEnvironmentId' => $projectEnvironmentId, 'storageType' => $storageType, 'bucketKey' => $bucketKey, 'storageItemKey' => $storageItemKey]);
        } else {
            $uri = Path::build('/projectenvironments/:projectEnvironmentId/storage/:storageType/items/:storageItemKey', ['projectEnvironmentId' => $projectEnvironmentId, 'storageType' => $storageType, 'storageItemKey' => $storageItemKey]);
        }


        try {
            $rawItem = $this->requestObject($uri);

            if ($rawItem === null) {
                return null;
            }


            $item = new StorageItem();
            $item->key = $rawItem['key'];
            $item->value = $rawItem['value'];
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

    public function hasItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, string|null $bucketKey = null): bool
    {
        return $this->getItem($projectEnvironmentId, $storageType, $storageItemKey, $bucketKey) !== null;
    }

    public function setItem(string $projectEnvironmentId, string $storageType, StorageItem $storageItem, string|null $bucketKey = null): bool
    {
        // TODO: how to handle overrides?
        if (!empty($bucketKey)) {
            $uri = Path::build('/projectenvironments/:projectEnvironmentId/storage/:storageType/:bucketKey/items/:key', ['projectEnvironmentId' => $projectEnvironmentId, 'storageType' => $storageType, 'bucketKey' => $bucketKey, 'key' => $storageItem->key]);
        } else {
            $uri = Path::build('/projectenvironments/:projectEnvironmentId/storage/:storageType/items/:key', ['projectEnvironmentId' => $projectEnvironmentId, 'storageType' => $storageType, 'key' => $storageItem->key]);
        }

        $rawResult = $this->requestObject($uri, $storageItem, 'POST');

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
     * cursor-paginated: set $pagination->startingAfter to the previous page's last item
     * id to fetch the next page, and inspect CollectionResult::$hasMore for further pages.
     *
     * To collect every key in a bucket, use StorageEngine::getItemKeys() which walks
     * all pages on top of this method.
     *
     * @return CollectionResult<StorageItemInformation>
     */
    public function getBucketItemsInformation(string $projectEnvironmentId, string $storageType, string|null $bucketKey = null, CursorPagination|null $pagination = null): CollectionResult
    {
        if (!empty($bucketKey)) {
            $uri = Path::build('/projectenvironments/:projectEnvironmentId/storage/:storageType/:bucketKey/items', ['projectEnvironmentId' => $projectEnvironmentId, 'storageType' => $storageType, 'bucketKey' => $bucketKey]);
        } else {
            $uri = Path::build('/projectenvironments/:projectEnvironmentId/storage/:storageType/items', ['projectEnvironmentId' => $projectEnvironmentId, 'storageType' => $storageType]);
        }
        $uri = $this->appendPaginationQuery($uri, $pagination);

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

    public function deleteItem(string $projectEnvironmentId, string $storageType, string $storageItemKey, string|null $bucketKey = null): bool
    {
        if (!empty($bucketKey)) {
            $uri = Path::build('/projectenvironments/:projectEnvironmentId/storage/:storageType/:bucketKey/items/:storageItemKey', ['projectEnvironmentId' => $projectEnvironmentId, 'storageType' => $storageType, 'bucketKey' => $bucketKey, 'storageItemKey' => $storageItemKey]);
        } else {
            $uri = Path::build('/projectenvironments/:projectEnvironmentId/storage/:storageType/items/:storageItemKey', ['projectEnvironmentId' => $projectEnvironmentId, 'storageType' => $storageType, 'storageItemKey' => $storageItemKey]);
        }


        $rawItem = $this->requestObject($uri, null, 'DELETE');

        if (isset($rawItem['deleted'])) {
            return $rawItem['deleted'];
        }
        throw new \Exception('Invalid response');
    }

    /**
     * @param string[] $storageItemKeys
     * @return array<string, bool> map of item key => deleted
     */
    public function deleteItems(string $projectEnvironmentId, string $storageType, array $storageItemKeys, string|null $bucketKey = null): array
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
        $uri = Path::build('/projectenvironments/:projectEnvironmentId/storage/:storageType', ['projectEnvironmentId' => $projectEnvironmentId, 'storageType' => $storageType]);


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

    public function clearBucket(string $projectEnvironmentId, string $storageType, string|null $bucketKey = null): bool
    {
        if (!empty($bucketKey)) {
            $uri = Path::build('/projectenvironments/:projectEnvironmentId/storage/:storageType/:bucketKey', ['projectEnvironmentId' => $projectEnvironmentId, 'storageType' => $storageType, 'bucketKey' => $bucketKey]);
        } else {
            $uri = Path::build('/projectenvironments/:projectEnvironmentId/storage/:storageType', ['projectEnvironmentId' => $projectEnvironmentId, 'storageType' => $storageType]);
        }


        $rawItem = $this->requestObject($uri, null, 'DELETE');


        if (isset($rawItem['deleted'])) {
            return $rawItem['deleted'];
        }

        throw new \Exception('Invalid response');
    }

    /** @deprecated Renamed to clearBucket(). */
    public function clearPool(string $projectEnvironmentId, string $storageType, string|null $bucketKey = null): bool
    {
        return $this->clearBucket($projectEnvironmentId, $storageType, $bucketKey);
    }

    /** @deprecated Renamed to getBucketKeys(). */
    public function getPoolKeys(string $projectEnvironmentId, string $storageType): array
    {
        return $this->getBucketKeys($projectEnvironmentId, $storageType);
    }

}
