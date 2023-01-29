<?php
declare(strict_types=1);

namespace Attlaz\Model\Exception;

class RequestException extends \Exception
{
    public int $httpCode = 0;


}
