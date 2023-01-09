<?php
declare(strict_types=1);

namespace Attlaz\Model;

use DateTimeInterface;

class StorageItem implements \JsonSerializable
{
    public string $key;
    /** @var string|int|float|array|object|null|bool */
    public $value;
    public ?\DateTime $expiration = null;

    public function jsonSerialize(): array
    {
        return [
            'key'        => $this->key,
            'value'      => $this->value,
            'expiration' => $this->expiration === null ? null : $this->expiration->format(DateTimeInterface::RFC3339_EXTENDED),
        ];
    }
}
