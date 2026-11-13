<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * The consent package could not be built or handed to the mailer — a mail
 * server refusing the message, say. Callers show the reason to the admin
 * instead of failing the whole request.
 */
class ConsentDeliveryFailed extends Exception
{
    public static function because(Throwable $previous): self
    {
        return new self($previous->getMessage(), 0, $previous);
    }
}
