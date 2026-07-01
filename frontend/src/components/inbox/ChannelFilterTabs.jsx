import { getChannelConfig } from './channelConfig'

// Fixed display order for the filter bar — intentionally not derived from
// CHANNEL_CONFIG's key order, since the product order here (Facebook first)
// differs from the schema/mock-data order used elsewhere.
const TABS = [
  { key: 'all', label: 'All' },
  { key: 'messenger', label: 'Facebook' },
  { key: 'instagram', label: 'Instagram' },
  { key: 'whatsapp', label: 'WhatsApp' },
  { key: 'telegram', label: 'Telegram' },
  { key: 'tiktok', label: 'TikTok' },
]

/**
 * Horizontal tab bar for filtering the conversation list by channel.
 * Sits between the "Inbox" header/search box and the list body.
 */
export default function ChannelFilterTabs({ activeFilter, onChange }) {
  return (
    <div
      role="tablist"
      aria-label="Filter conversations by channel"
      className="shrink-0 overflow-x-auto border-b border-gray-200 bg-white px-2 py-2 dark:border-gray-700 dark:bg-gray-900"
    >
      <div className="flex w-max gap-1">
        {TABS.map((tab) => {
          const isActive = activeFilter === tab.key
          const config = tab.key === 'all' ? null : getChannelConfig(tab.key)
          return (
            <button
              key={tab.key}
              type="button"
              role="tab"
              aria-selected={isActive}
              aria-label={`Filter by ${tab.label}`}
              onClick={() => onChange(tab.key)}
              className={`flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 ${
                isActive
                  ? 'bg-emerald-700 text-white'
                  : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'
              }`}
            >
              {config && (
                <span
                  aria-hidden="true"
                  className={`h-2 w-2 shrink-0 rounded-full ${config.dotClasses} ${
                    isActive ? 'ring-2 ring-white/60' : ''
                  }`}
                />
              )}
              {tab.label}
            </button>
          )
        })}
      </div>
    </div>
  )
}
