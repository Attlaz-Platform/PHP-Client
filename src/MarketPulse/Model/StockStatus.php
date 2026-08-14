<?php
declare(strict_types=1);

namespace Attlaz\MarketPulse\Model;

/**
 * Whether a vendor product is purchasable. Mirrors the server enum
 * (Library/Apps MarketPulse/Model/StockStatus) and the JavaScript client's StockStatus.
 *
 * Three states rather than a nullable boolean: most crawlers never report stock, and "we don't
 * know" is a different fact from "it is out of stock" — one is a coverage gap, the other is a
 * market signal.
 */
enum StockStatus: string
{
    case InStock = 'in_stock';
    case OutOfStock = 'out_of_stock';
    case Unknown = 'unknown';

    /**
     * Read `stock_status` from an API record. An unrecognised or absent value is Unknown, never
     * OutOfStock — guessing the latter would make a consumer hide a product that is probably on sale.
     */
    public static function fromRecord(mixed $status): self
    {
        if (\is_string($status)) {
            return self::tryFrom($status) ?? self::Unknown;
        }

        return self::Unknown;
    }
}
