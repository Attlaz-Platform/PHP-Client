<?php
declare(strict_types=1);

namespace Attlaz\Endpoint\MarketPulse;


use Attlaz\Endpoint\Endpoint;
use Attlaz\Model\MarketPulse\CatalogProduct;

class CatalogProductEndpoint extends Endpoint
{
    public function getProductByIdentifier(string $catalogId, string $identifier): CatalogProduct|null
    {
        $response = $this->requestCollection('/catalogs/' . $catalogId . '/products?identifier=' . $identifier, null, 'GET');

        if (!is_array($response)) {
            throw new \Error('Invalid response');
        }
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

        $response = $this->requestObject('/catalogs/' . $product->catalogId . '/products', $data, 'POST');

        return $this->parseCatalogProduct($response);
    }

    public function updateProduct(CatalogProduct $product): bool
    {
        $data = [
            ['op' => 'add', 'path' => 'cost_price', 'value' => $product->costPrice],
        ];


        $response = $this->requestObject('/catalogs/' . $product->catalogId . '/products/' . $product->id, $data, 'PATCH');
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
