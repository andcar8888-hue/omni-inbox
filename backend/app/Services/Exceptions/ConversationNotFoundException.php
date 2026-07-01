<?php

namespace App\Services\Exceptions;

use RuntimeException;

/**
 * Thrown when a conversation does not exist OR does not belong to the caller's
 * business. Both cases map to the same 404 so we never leak the existence of
 * another tenant's conversation (CLAUDE.md domain isolation).
 */
class ConversationNotFoundException extends RuntimeException
{
}
