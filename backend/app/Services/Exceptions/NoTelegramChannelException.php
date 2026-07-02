<?php

namespace App\Services\Exceptions;

use RuntimeException;

/**
 * Thrown by TelegramInboundService when an inbound update arrives but no
 * telegram channel row exists to attribute it to. This is a setup/onboarding
 * problem (there is no "connect a channel" UI yet), not a per-request error, so
 * the webhook controller logs it loudly and still returns 200 (see the ADR note
 * in TelegramWebhook) so Telegram does not retry a request we can never satisfy.
 */
class NoTelegramChannelException extends RuntimeException
{
}
