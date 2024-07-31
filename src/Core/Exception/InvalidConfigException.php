<?php

declare(strict_types=1);

namespace Twint\Core\Exception;

use RuntimeException;
use Throwable;

class InvalidConfigException extends RuntimeException
{
    public const ERROR_INVALID_STORE_UUID = 'Invalid store UUID';

    public const ERROR_INVALID_CERTIFICATE = 'Invalid certificate';

    public const ERROR_NOT_VALIDATED = 'Configuration not validated';

    public const ERROR_UNAVAILABLE = 'Service unavailable';

    public const ERROR_UNDEFINED = 'Undefined error';

    public function __construct(string $message = 'Plugin Invalid configuration', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
