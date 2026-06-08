<?php
declare(strict_types=1);

namespace Attlaz\Model;

use DateTimeInterface;

class StorageItem implements \JsonSerializable
{
    public string $key;
    public mixed $value = null;
    public \DateTime|null $expiration = null;

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'key'        => $this->key,
            'value'      => $this->value,
            'expiration' => $this->expiration === null ? null : $this->expiration->format(DateTimeInterface::RFC3339_EXTENDED),
        ];
    }
}
