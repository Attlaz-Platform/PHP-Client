<?php
declare(strict_types=1);

namespace Attlaz\DataQuality\Model;

/**
 * One problem found on one entity, as sent to the report endpoint.
 *
 * `code` is the machine key the problem definition hangs off (`shop.ean_invalid_checkdigit`);
 * `property` is what the problem is about (`ean`, `name`, `image`). Leave `severity` null and the
 * platform applies the definition's default.
 */
class ReportProblem
{
    public string|null $severity = null;
    public string|null $note = null;
    /** @var array<string,mixed>|null */
    public array|null $data = null;
    public string|null $channel = null;
    public string|null $locale = null;

    public function __construct(public string $code, public string $property)
    {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'property' => $this->property,
            'severity' => $this->severity,
            'note' => $this->note,
            'data' => $this->data,
            'channel' => $this->channel,
            'locale' => $this->locale,
        ];
    }
}
