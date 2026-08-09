<?php
declare(strict_types=1);


namespace Attlaz\MarketPulse\Endpoint;

use Attlaz\Http\Path;


use Attlaz\Endpoint\Endpoint;
use Attlaz\MarketPulse\Model\Competitor;


/**
 * The competitors a catalog is watched against.
 *
 * A competitor is a link between a catalog and an existing vendor — create the vendor first with
 * VendorEndpoint::createVendor(), then link it here.
 */
class CompetitorEndpoint extends Endpoint
{
    /**
     * Start watching an existing vendor as a competitor of this catalog.
     *
     * Re-adding a previously removed competitor reactivates the original link, so its matches and
     * price history survive.
     */
    public function add(string $catalogId, string $vendorId): Competitor
    {
        $data = [
            'vendor' => $vendorId,
        ];

        $response = $this->requestObject(
            Path::build('/pulse/catalogs/:catalogId/competing-vendors', ['catalogId' => $catalogId]),
            $data,
            'POST'
        );

        return $this->parseCompetitor($response);
    }

    /**
     * Stop watching a competitor.
     *
     * The vendor, its products and their price history are kept — only the catalog↔vendor link is
     * retired, so add() can resume it later.
     */
    public function remove(string $catalogId, string $competitorId): bool
    {
        $this->requestObject(
            Path::build('/pulse/catalogs/:catalogId/competing-vendors/:competitorId', [
                'catalogId' => $catalogId,
                'competitorId' => $competitorId,
            ]),
            null,
            'DELETE'
        );

        return true;
    }


    private function parseCompetitor(array $record): Competitor
    {
        $competitor = new Competitor();
        $competitor->id = $record['id'];
        $competitor->catalogId = $record['catalog'];
        $competitor->vendorId = $record['vendor'];

        return $competitor;
    }
}
