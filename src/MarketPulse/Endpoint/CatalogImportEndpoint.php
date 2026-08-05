<?php
declare(strict_types=1);


namespace Attlaz\MarketPulse\Endpoint;

use Attlaz\Http\Path;


use Attlaz\Endpoint\Endpoint;
use Attlaz\MarketPulse\Model\CatalogImport;


class CatalogImportEndpoint extends Endpoint
{
    public function create(string $catalogId): CatalogImport|null
    {
        $data = [
            // 'id' => $crawlJob->vendorId,
        ];
        $response = $this->requestObject(Path::build('/catalogs/:catalogId/imports', ['catalogId' => $catalogId]), $data, 'POST');
        var_dump($response);
        if ($response === null) {
            return null;
        }
        return $this->parseCatalogImport($response);
    }


    public function update(CatalogImport $catalogImport): CatalogImport|null
    {
        // TODO: implement
        $data = [
            'id' => $catalogImport->id,
        ];
        $response = $this->requestObject(Path::build('/catalogs/:catalogId/imports', ['catalogId' => $catalogImport->catalogId]), $data, 'POST');
        if ($response === null) {
            return null;
        }
        return $this->parseCatalogImport($response);
    }


    private function parseCatalogImport(array $record): CatalogImport
    {
        $catalog = new CatalogImport();
        $catalog->id = $record['id'];
        $catalog->catalogId = $record['catalog'];


        return $catalog;
    }
}
