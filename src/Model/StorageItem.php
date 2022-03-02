<?php
declare(strict_types=1);

namespace Attlaz\Model;

class StorageItem
{
    public string $key;
    /** @var string|int|float|array|object|null|bool */
    public $value;
    public ?\DateTime $expiration = null;
}
