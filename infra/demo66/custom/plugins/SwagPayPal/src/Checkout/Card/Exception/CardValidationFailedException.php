<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Card\Exception;

use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class CardValidationFailedException extends PaymentException
{
    public static function asyncCardValidationFailed(string $orderTransactionId, ?string $message = null): PaymentException
    {
        return PaymentException::asyncProcessInterrupted(
            $orderTransactionId,
            $message ?? 'Credit card validation failed, 3D secure was not validated.'
        );
    }

    public static function syncCardValidationFailed(string $orderTransactionId, ?string $message = null): PaymentException
    {
        return PaymentException::syncProcessInterrupted(
            $orderTransactionId,
            $message ?? 'Credit card validation failed, 3D secure was not validated.'
        );
    }
}
