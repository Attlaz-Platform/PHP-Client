<?php
declare(strict_types=1);

namespace Attlaz\MarketPulse\Model;

class CrawlJob
{
    public string $id;
    public string $vendorId;
    public string $status = 'pending';
}
