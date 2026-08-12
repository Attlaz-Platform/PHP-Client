<?php
declare(strict_types=1);

namespace Attlaz\Helper;

/**
 * The platform wire format for a timestamp: RFC 3339, milliseconds, always UTC — `2026-05-01T10:30:00.000Z`.
 *
 * Normalised to UTC so one instant has one rendering everywhere. Formatting in the server's local
 * zone denotes the same moment but makes the same event look different depending on which machine
 * wrote it. Matches what the JS client emits via toISOString().
 */
final class Rfc3339
{
    public static function format(\DateTimeInterface $date): string
    {
        // createFromInterface first: \DateTime::setTimezone() mutates, and the caller owns that object.
        return \DateTimeImmutable::createFromInterface($date)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.v\Z');
    }
}
