<?php
declare(strict_types=1);

namespace Attlaz\MarketPulse\Model;

/**
 * A vendor as one catalog watches it. The same vendor can be a competitor in several catalogs,
 * which is why this is a separate entity from Vendor.
 */
class Competitor
{
    public string $id;
    public string $catalogId;
    public string $vendorId;
}
