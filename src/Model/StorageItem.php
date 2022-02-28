<?php
declare(strict_types=1);

namespace Attlaz\Model;

class StorageItem
{
    public string $key;
    public string $value;
    public ?\DateTime $expiration = null;
}
