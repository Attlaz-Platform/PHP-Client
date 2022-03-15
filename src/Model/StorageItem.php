<?php
declare(strict_types=1);

namespace Attlaz\Model;

class StorageItem
{
    public $key;
    /** @var string|int|float|array|object|null|bool */
    public $value;
    public $expiration = null;
}
