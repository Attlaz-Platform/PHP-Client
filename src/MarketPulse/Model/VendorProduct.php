<?php
declare(strict_types=1);

namespace Attlaz\MarketPulse\Model;

class VendorProduct
{
    public string $id;
    public string $name;
    public string $identifier;
    public string $vendorId;
    public string|null $url = null;
    public string|null $gtin = null;
    public string|null $brand = null;
    public string|null $sku = null;
    public string|null $manufacturerPartNumber = null;
    public string|null $variantGroup = null;
    /** @var array<int, array{name: string, value: string, code?: string|null, unit?: string|null, numeric_value?: float|null}>|null */
    public array|null $variantAxes = null;
    public string|null $image = null;
    /** ISO 4217 code. Null falls back to the vendor's primary currency. */
    public string|null $currency = null;
    public float $price;
    public float|null $originalPrice;
    public float|null $shippingCost = null;
    public StockStatus $stockStatus = StockStatus::Unknown;
    public array|null $properties = null;
    public \DateTime $createdAt;
    public \DateTime $updatedAt;
}
