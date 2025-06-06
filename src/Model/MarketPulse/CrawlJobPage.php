<?php
declare(strict_types=1);

namespace Attlaz\Model\MarketPulse;

class CrawlJobPage
{
    public string $id;
    public string $crawlJobId;
    public string $url;
    public string|null $content = null;
    public \DateTime|null $crawledAt = null;
}
