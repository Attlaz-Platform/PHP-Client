<?php
declare(strict_types=1);

namespace Attlaz\MarketPulse\Endpoint;

use Attlaz\Http\Path;


use Attlaz\Endpoint\Endpoint;
use Attlaz\MarketPulse\Model\Catalog;

class CatalogEndpoint extends Endpoint
{
    public function getById(string $catalogId): Catalog|null
    {
        $response = $this->requestObject(Path::build('/catalogs/:catalogId', ['catalogId' => $catalogId]), null, 'GET');
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
