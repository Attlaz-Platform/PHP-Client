<?php
declare(strict_types=1);

namespace Attlaz\MarketPulse\Model;

/**
 * A shop whose products Market Pulse tracks — your own or a competitor's.
 *
 * One vendor is one storefront view: a single url, a single currency. A merchant selling in
 * several markets is several vendors.
 */
class Vendor
{
    public string $id;
    public string $name;
    /** Storefront root, scheme included, no trailing slash — e.g. `https://shop.com/nl`. */
    public string $url;
    public string|null $icon = null;
    /** ISO 4217 code this storefront quotes prices in. */
    public string|null $currency = null;
    /** Platform hint used to pick a scrape strategy, e.g. `shopify`. */
    public string|null $type = null;
    public string|null $botDetection = null;
    public string|null $parseStrategy = null;
}
