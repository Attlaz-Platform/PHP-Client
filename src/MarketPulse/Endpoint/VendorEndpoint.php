<?php
declare(strict_types=1);


namespace Attlaz\MarketPulse\Endpoint;

use Attlaz\Http\Path;


use Attlaz\Endpoint\Endpoint;
use Attlaz\MarketPulse\Model\Vendor;


/**
 * Vendors — the shops Market Pulse knows about, independent of who watches them.
 *
 * Creating a vendor and watching it as a competitor are separate steps: create here, then link it
 * to a catalog with CompetitorEndpoint::add(). That split is what lets one vendor be watched by
 * several catalogs without being duplicated.
 *
 * There is deliberately no "list vendors" method: vendors are global, so listing them would expose
 * every customer's tracked shops. Discovery gets designed alongside the UI that needs it.
 */
class VendorEndpoint extends Endpoint
{
    public function getById(string $vendorId): Vendor|null
    {
        $response = $this->requestObject(Path::build('/pulse/vendors/:vendorId', ['vendorId' => $vendorId]), null, 'GET');
        if ($response === null) {
            return null;
        }
        return $this->parseVendor($response);
    }

    /**
     * Register a shop.
     *
     * The url is normalised server-side (scheme added, trailing slash dropped, host lowercased),
     * and a storefront that already has a vendor is rejected — so this is safe to call without
     * checking first, but it throws rather than returning the existing vendor.
     *
     * @param string $url Storefront root. A locale path is significant: `https://shop.com/nl` is a
     *                    different storefront from `https://shop.com`.
     */
    public function createVendor(string $name, string $url, string|null $currency = null, string|null $type = null): Vendor
    {
        $data = [
            'name' => $name,
            'url' => $url,
            'currency' => $currency,
            'type' => $type,
        ];

        $response = $this->requestObject('/pulse/vendors', $data, 'POST');

        return $this->parseVendor($response);
    }


    private function parseVendor(array $record): Vendor
    {
        $vendor = new Vendor();
        $vendor->id = $record['id'];
        $vendor->name = $record['name'];
        $vendor->url = $record['url'];
        $vendor->icon = $record['icon'] ?? null;
        $vendor->currency = $record['currency'] ?? null;
        $vendor->type = $record['type'] ?? null;
        $vendor->botDetection = $record['bot_detection'] ?? null;
        $vendor->parseStrategy = $record['parse_strategy'] ?? null;


        return $vendor;
    }
}
