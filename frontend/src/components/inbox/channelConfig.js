// Single source of truth for how each platform (channels.platform enum) is
// represented in the UI: abbreviation badge, full name, and brand color.
// Design decision: rather than shipping a full icon set per platform, each
// channel is a colored two-letter badge (WA/FB/IG/TG) — cheap to render at
// list-item scale, colorblind-safe because the letters differ (not color
// alone), and easy to swap for real platform logos later.
export const CHANNEL_CONFIG = {
  whatsapp: {
    label: 'WA',
    name: 'WhatsApp',
    badgeClasses: 'bg-emerald-600 text-white',
    dotClasses: 'bg-emerald-600',
  },
  messenger: {
    label: 'FB',
    name: 'Messenger',
    badgeClasses: 'bg-blue-600 text-white',
    dotClasses: 'bg-blue-600',
  },
  instagram: {
    label: 'IG',
    name: 'Instagram',
    badgeClasses: 'bg-gradient-to-br from-purple-600 via-pink-600 to-orange-400 text-white',
    dotClasses: 'bg-pink-600',
  },
  telegram: {
    label: 'TG',
    name: 'Telegram',
    badgeClasses: 'bg-sky-500 text-white',
    dotClasses: 'bg-sky-500',
  },
  // TikTok's brand mark is black with cyan/pink accents, but since every
  // other channel here reads as one solid dot color, we use the cyan accent
  // (Tailwind's `teal-500`) — it's the most recognizable single color from
  // the TikTok logo and doesn't collide with any existing dot color
  // (emerald/blue/pink/sky above).
  tiktok: {
    label: 'TT',
    name: 'TikTok',
    badgeClasses: 'bg-teal-500 text-white',
    dotClasses: 'bg-teal-500',
  },
}

export function getChannelConfig(platform) {
  return (
    CHANNEL_CONFIG[platform] || {
      label: '?',
      name: 'Unknown channel',
      badgeClasses: 'bg-gray-400 text-white',
      dotClasses: 'bg-gray-400',
    }
  )
}
