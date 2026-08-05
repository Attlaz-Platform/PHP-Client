<?php
declare(strict_types=1);


namespace Attlaz\MarketPulse\Endpoint;


use Attlaz\Endpoint\Endpoint;
use Attlaz\MarketPulse\Model\CrawlJobPage;


class CrawlJobPageEndpoint extends Endpoint
{
    public function createCrawlJobPage(CrawlJobPage $vendorPage): CrawlJobPage
    {


        $data = [
            'url' => $vendorPage->url,
            'content' => $vendorPage->content,
        ];


        $response = $this->requestObject('/pulse/crawl-jobs/' . $vendorPage->crawlJobId . '/page', $data, 'POST');


        return $this->parseCrawlJobPage($response);
    }


    public function updateCrawlJobPage(CrawlJobPage $crawlJobPage): bool
    {


        $data = [
            ['op' => 'add', 'path' => 'content', 'value' => $crawlJobPage->content],
            ['op' => 'add', 'path' => 'crawled_at', 'value' =>
                $crawlJobPage->crawledAt === null ? null : $crawlJobPage->crawledAt->format(\DateTimeInterface::RFC3339_EXTENDED),


            ],
        ];


        $response = $this->requestObject('/pulse/crawl-job-pages/' . $crawlJobPage->id, $data, 'PATCH');
        // TODO: validate response
        return true;
    }


    public function getById(string $crawlJobPageId): CrawlJobPage|null
    {
        $response = $this->requestObject('/pulse/crawl-job-pages/' . $crawlJobPageId . '', null, 'GET');
        if ($response === null) {
            return null;
        }
        return $this->parseCrawlJobPage($response);
    }


    private function parseCrawlJobPage(array $record): CrawlJobPage
    {
        $product = new CrawlJobPage();
        $product->id = $record['id'];
        $product->crawlJobId = $record['crawl_job'];
        $product->url = $record['url'];
        $product->content = $record['content'];


        return $product;
    }


}
