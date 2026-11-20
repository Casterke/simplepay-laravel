<?php

declare(strict_types=1);

namespace Casterke\SimplePayLaravel\Exceptions;

use Throwable;

final class InvalidSignatureException extends \Exception
{
    public function __construct($message = "", $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Invalid signature', $code, $previous);
    }
}
