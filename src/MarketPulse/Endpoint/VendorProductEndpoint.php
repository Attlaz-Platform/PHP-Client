<?php
declare(strict_types=1);

namespace Attlaz\MarketPulse\Endpoint;


use Attlaz\Endpoint\Endpoint;
use Attlaz\MarketPulse\Model\VendorProduct;

class VendorProductEndpoint extends Endpoint
{
    public function getProductByIdentifier(string $vendorId, string $identifier): VendorProduct|null
    {
        $response = $this->requestCollection('/pulse/vendors/' . $vendorId . '/products?identifier=' . $identifier)->getData();

        if (count($response) === 0) {
            return null;
        }
        return $this->parseVendorProduct($response[0]);
    }

    public function saveProduct(VendorProduct $product): VendorProduct
    {

        $data = [
            'name' => $product->name,
            'image' => $product->image,
            'identifier' => $product->identifier,
            'gtin' => $product->gtin,
            'brand' => $product->brand,
            // 'sku' => $product->sku,
            'url' => $product->url,
            'price' => $product->price,
            'original_price' => $product->originalPrice,
            'shipping_cost' => $product->shippingCost,
            'is_in_stock' => $product->isInStock,
            'properties' => $product->properties,
        ];

        $response = $this->requestObject('/pulse/vendors/' . $product->vendorId . '/products', $data, 'POST');

        return $this->parseVendorProduct($response);
    }

    public function updateProduct(VendorProduct $product): bool
    {
        $data = [
            ['op' => 'add', 'path' => 'price', 'value' => $product->price],
            ['op' => 'add', 'path' => 'original_price', 'value' => $product->originalPrice],
            ['op' => 'add', 'path' => 'brand', 'value' => $product->brand],
            ['op' => 'add', 'path' => 'shipping_cost', 'value' => $product->shippingCost],
            ['op' => 'add', 'path' => 'is_in_stock', 'value' => $product->isInStock],
            ['op' => 'add', 'path' => 'url', 'value' => $product->url],
            ['op' => 'add', 'path' => 'gtin', 'value' => $product->gtin],
            ['op' => 'add', 'path' => 'image', 'value' => $product->image],
            // ['op' => 'add', 'path' => 'sku', 'value' => $product->sku],
            ['op' => 'add', 'path' => 'properties', 'value' => $product->properties],
        ];

        $response = $this->requestObject('/pulse/vendors/' . $product->vendorId . '/products/' . $product->id, $data, 'PATCH');
        // TODO: validate response
        return true;
    }

    private function parseVendorProduct(array $record): VendorProduct
    {
        $product = new VendorProduct();
        $product->id = $record['id'];
        $product->vendorId = $record['vendor'];
        $product->name = $record['name'];
        $product->image = $record['image'];
        $product->gtin = $record['gtin'];
        $product->brand = $record['brand'];
        $product->identifier = $record['identifier'];
        $product->url = $record['url'];
        // $product->sku = $record['sku'];
        $product->price = (float)$record['price'];
        $product->originalPrice = $record['original_price'] === null ? null : (float)$record['original_price'];
        $product->shippingCost = $record['shipping_cost'];
        $product->isInStock = $record['is_in_stock'];

        $product->createdAt = \DateTime::createFromFormat(\DateTimeInterface::RFC3339_EXTENDED, $record['created_at']);
        $product->updatedAt = \DateTime::createFromFormat(\DateTimeInterface::RFC3339_EXTENDED, $record['updated_at']);

        return $product;
    }


}
