<?php
declare(strict_types=1);

namespace Attlaz\MarketPulse\Endpoint;

use Attlaz\Http\Path;


use Attlaz\Endpoint\Endpoint;
use Attlaz\MarketPulse\Model\CrawlJob;

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

    public function updateCrawlJob(CrawlJob $crawlJob): CrawlJob
    {
        $data = [
            'status' => $crawlJob->status,
        ];

        $response = $this->requestObject(Path::build('/pulse/crawl-jobs/:id', ['id' => $crawlJob->id]), $data, 'PATCH');

        return $this->parseCrawlJob($response);
    }

    public function getById(string $crawlJobId): CrawlJob|null
    {
        $response = $this->requestObject(Path::build('/pulse/crawl-jobs/:crawlJobId', ['crawlJobId' => $crawlJobId]), null, 'GET');
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
        $crawlJob->status = $record['status'] ?? 'pending';

        return $crawlJob;
    }
}
