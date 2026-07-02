<?php

namespace App\Services\Channels;

use App\Services\Channels\Exceptions\UnsupportedPlatformException;

/**
 * Maps a channel `platform` string to its adapter instance.
 *
 * This is the single registration point for platform adapters. Adding
 * WhatsApp/Messenger/Instagram/TikTok later means adding one entry here (and one
 * adapter class) — ConversationService and the webhook layer never branch on
 * platform themselves, per CLAUDE.md's folder-ownership rule.
 *
 * A resolved adapter may be null for platforms whose adapter is not yet built,
 * so callers can decide (e.g. persist-only) without a hard failure — but an
 * outright unknown/unsupported platform string throws.
 */
class ChannelResolver
{
    /**
     * platform => factory callable returning a ChannelAdapterInterface|null.
     * null means "known platform, adapter not implemented in this phase".
     *
     * @var array<string, callable(): ?ChannelAdapterInterface>
     */
    private array $factories;

    /**
     * @param array<string, callable(): ?ChannelAdapterInterface>|null $factories
     *        Optional override for tests (inject a fake adapter).
     */
    public function __construct(?array $factories = null)
    {
        $this->factories = $factories ?? [
            TelegramChannel::PLATFORM => static fn (): ChannelAdapterInterface => new TelegramChannel(),
            // Future phases register here:
            //   'whatsapp'  => fn () => new WhatsAppChannel(),
            //   'messenger' => fn () => new MessengerChannel(),
            //   'instagram' => fn () => new InstagramChannel(),
            //   'tiktok'    => fn () => new TikTokChannel(),  // blocked on partner approval
            'whatsapp'  => static fn (): ?ChannelAdapterInterface => null,
            'messenger' => static fn (): ?ChannelAdapterInterface => null,
            'instagram' => static fn (): ?ChannelAdapterInterface => null,
            'tiktok'    => static fn (): ?ChannelAdapterInterface => null,
        ];
    }

    /**
     * Resolve an adapter for the platform, or null if the platform is known but
     * has no adapter implemented yet (caller falls back to persist-only).
     *
     * @throws UnsupportedPlatformException for an entirely unknown platform value.
     */
    public function resolve(string $platform): ?ChannelAdapterInterface
    {
        if (! array_key_exists($platform, $this->factories)) {
            throw new UnsupportedPlatformException("No channel adapter registered for platform '{$platform}'.");
        }

        return ($this->factories[$platform])();
    }
}
