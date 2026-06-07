<?php
declare(strict_types=1);

namespace Attlaz\Model;

use DateTimeInterface;

/**
 * Metadata for a single storage item (no value). Mirrors the JS client's
 * StorageItemInformation, but keeps `id` (the JS client omits it) because it is the
 * cursor used to paginate the items listing.
 */
class StorageItemInformation
{
    public ?string $id = null;
    public string $key;
    public int $bytes = 0;
    public ?\DateTime $expiration = null;
    public ?\DateTime $created = null;
    public ?\DateTime $updated = null;

    public static function fromArray(array $raw): self
    {
        $info = new self();
        $info->id = isset($raw['id']) ? (string) $raw['id'] : null;
        $info->key = (string) $raw['key'];
        $info->bytes = isset($raw['bytes']) ? (int) $raw['bytes'] : 0;
        $info->expiration = self::parseDate($raw['expiration'] ?? null);
        $info->created = self::parseDate($raw['created'] ?? null);
        $info->updated = self::parseDate($raw['updated'] ?? null);

        return $info;
    }

    private static function parseDate(?string $value): ?\DateTime
    {
        if ($value === null) {
            return null;
        }
        $date = \DateTime::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $value);

        return $date === false ? null : $date;
    }
}
