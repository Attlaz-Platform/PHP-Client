<?php
declare(strict_types=1);


namespace Attlaz\MarketPulse\Endpoint;


use Attlaz\Endpoint\Endpoint;
use Attlaz\MarketPulse\Model\Vendor;


class VendorEndpoint extends Endpoint
{
    public function getById(string $vendorId): Vendor|null
    {
        $response = $this->requestObject('/pulse/vendors/' . $vendorId, null, 'GET');
        if ($response === null) {
            return null;
        }
        return $this->parseVendorProduct($response);
    }


    private function parseVendorProduct(array $record): Vendor
    {
        $vendor = new Vendor();
        $vendor->id = $record['id'];
        $vendor->name = $record['name'];
        $vendor->domain = $record['domain'];
        $vendor->index = $record['index'];
        $vendor->type = $record['type'];
        $vendor->botDetection = $record['bot_detection'];
        $vendor->parseStrategy = $record['parse_strategy'];


        return $vendor;
    }
}
