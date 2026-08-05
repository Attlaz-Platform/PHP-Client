<?php
declare(strict_types=1);

namespace Attlaz\MarketPulse\Model;

class Vendor
{
    public string $id;
    public string $name;
    public string $domain;
    public string $index;
    public string|null $type;
    public string|null $botDetection;
    public string|null $parseStrategy;
}
