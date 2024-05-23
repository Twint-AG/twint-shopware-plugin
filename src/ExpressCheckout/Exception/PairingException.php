<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Exception;

use Exception;

class PairingException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
