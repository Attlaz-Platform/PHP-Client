<?php
declare(strict_types=1);

namespace Attlaz\Endpoint\MarketPulse;


use Attlaz\Endpoint\Endpoint;
use Attlaz\Model\MarketPulse\CrawlJob;

class CrawlJobEndpoint extends Endpoint
{

    public function saveCrawlJob(CrawlJob $crawlJob): CrawlJob
    {

        $data = [
            'vendor' => $crawlJob->vendorId,
        ];

        $response = $this->requestObject('/pulse/crawl-jobs/', $data, 'POST');

        return $this->parseCrawlJob($response);
    }

    public function getById(string $crawlJobId): CrawlJob|null
    {
        $response = $this->requestObject('/pulse/crawl-jobs/' . $crawlJobId . '', null, 'GET');
        if ($response === null) {
            return null;
        }
        return $this->parseCrawlJob($response);
    }

    private function parseCrawlJob(array $record): CrawlJob
    {
        $crawlJob = new CrawlJob();
        $crawlJob->id = $record['id'];
        $crawlJob->vendorId = $record['vendor'];

        return $crawlJob;
    }
}
