<?php
declare(strict_types=1);

namespace Attlaz\Model\MarketPulse;

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
    public string|null $image = null;
    public float $price;
    public float|null $originalPrice;
    public float|null $shippingCost = null;
    public bool|null $isInStock = null;
    public \DateTime $createdAt;
    public \DateTime $updatedAt;
}
