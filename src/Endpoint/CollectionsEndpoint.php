<?php
declare(strict_types=1);

namespace Attlaz\Endpoint;

use Attlaz\Http\Path;


class CollectionsEndpoint extends Endpoint
{


    public function addRecord(string $collectionId, array $properties): bool
    {

        $uri = Path::build('/collections/:collectionId/records', ['collectionId' => $collectionId]);


        $rawResult = $this->requestObject($uri, ['properties' => $properties], 'POST');

//        if (isset($rawResult['data']) && isset($rawResult['data']['success'])) {
//            return $rawResult['data']['success'];
//        }
//        if (isset($rawResult['errors']) && count($rawResult['errors']) > 0) {
//            return false;
//        }
//        throw new \Exception('Invalid response');

        return true;
    }
}
