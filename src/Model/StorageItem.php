<?php
declare(strict_types=1);

namespace Attlaz\Model;

use Attlaz\Helper\Rfc3339;
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
            'expiration' => $this->expiration === null ? null : Rfc3339::format($this->expiration),
        ];
    }
}
