<?php
declare(strict_types=1);

namespace Attlaz\Model;

/**
 * Display/filter status of a personal access token. Mirrors the server enum
 * (Library/Core Attlaz-OAuth/Model/UserAccessTokenStatus) and the JavaScript client's
 * UserAccessTokenStatus. Folds the derived "expired" concept — computed from the expiry date, not
 * stored — on top of the stored state.
 */
enum UserAccessTokenStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
