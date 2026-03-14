<?php
declare(strict_types=1);

namespace Attlaz\Endpoint\MarketPulse;


use Attlaz\Endpoint\Endpoint;
use Attlaz\Model\MarketPulse\Catalog;

class CatalogEndpoint extends Endpoint
{
    public function getById(string $catalogId): Catalog|null
    {
        $response = $this->requestObject('/catalogs/' . $catalogId, null, 'GET');
        if ($response === null) {
            return null;
        }
        return $this->parseCatalog($response);
    }

    private function parseCatalog(array $record): Catalog
    {
        $catalog = new Catalog();
        $catalog->id = $record['id'];
        $catalog->project = $record['project'];
        $catalog->name = $record['name'];
        $catalog->description = $record['description'];
        $catalog->source = $record['source'];
        $catalog->vendor = $record['vendor'];

        return $catalog;
    }
}
