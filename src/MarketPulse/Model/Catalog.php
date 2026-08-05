<?php
declare(strict_types=1);


namespace Attlaz\MarketPulse\Model;


class Catalog
{
    public string $id;
    public string $project;
    public string $name;
    public string $description;


    public string|null $source;
    public string $vendor;
}
