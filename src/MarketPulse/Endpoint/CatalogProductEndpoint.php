<?php
declare(strict_types=1);

namespace Attlaz\MarketPulse\Endpoint;

use Attlaz\Http\Path;


use Attlaz\Endpoint\Endpoint;
use Attlaz\MarketPulse\Model\CatalogProduct;

class CatalogProductEndpoint extends Endpoint
{
    public function getProductByIdentifier(string $catalogId, string $identifier): CatalogProduct|null
    {
        $uri = Path::build('/catalogs/:catalogId/products', ['catalogId' => $catalogId])
            . '?identifier=' . \rawurlencode($identifier);
        $response = $this->requestCollection($uri)->getData();

        if (count($response) === 0) {
            return null;
        }
//        var_dump($response);
        return $this->parseCatalogProduct($response[0]);
    }

    public function createProduct(CatalogProduct $product): CatalogProduct
    {

        $data = [
            'identifier' => $product->identifier,
            'vendor_product' => $product->vendorProductId,
            'cost_price' => $product->costPrice,
        ];

        $response = $this->requestObject(Path::build('/catalogs/:catalogId/products', ['catalogId' => $product->catalogId]), $data, 'POST');

        return $this->parseCatalogProduct($response);
    }

    public function updateProduct(CatalogProduct $product): bool
    {
        $data = [
            ['op' => 'add', 'path' => 'cost_price', 'value' => $product->costPrice],
        ];


        $response = $this->requestObject(Path::build('/catalogs/:catalogId/products/:id', ['catalogId' => $product->catalogId, 'id' => $product->id]), $data, 'PATCH');
        // TODO: validate response
        return true;
    }

    private function parseCatalogProduct(array $record): CatalogProduct
    {
        $product = new CatalogProduct();
        $product->id = $record['id'];
        $product->identifier = $record['identifier'];
        $product->catalogId = $record['catalog'];
        $product->vendorProductId = $record['vendor_product'];
        $product->costPrice = $record['cost_price'];

        $product->createdAt = \DateTime::createFromFormat(\DateTimeInterface::RFC3339_EXTENDED, $record['created_at']);
        $product->updatedAt = \DateTime::createFromFormat(\DateTimeInterface::RFC3339_EXTENDED, $record['updated_at']);

        return $product;
    }


}
