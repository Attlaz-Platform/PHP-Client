<?php
declare(strict_types=1);


namespace Attlaz\MarketPulse\Model;


class CatalogProduct
{
    public string $id;
    public string $identifier;
    public string $catalogId;
    public string $vendorProductId;
    public float|null $costPrice = null;


    public \DateTime $createdAt;
    public \DateTime $updatedAt;
}
