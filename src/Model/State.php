<?php
declare(strict_types=1);

namespace Attlaz\Model;

enum State: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Removed = 'removed';
}
