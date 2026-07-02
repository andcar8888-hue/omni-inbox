<?php

namespace App\Services\Channels\Exceptions;

use RuntimeException;

/**
 * Thrown by ChannelResolver when asked for an adapter for a platform value that
 * is not registered at all (as opposed to a known-but-not-yet-implemented one,
 * which resolves to null).
 */
class UnsupportedPlatformException extends RuntimeException
{
}
