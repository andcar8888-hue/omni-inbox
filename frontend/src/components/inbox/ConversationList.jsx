import { useMemo, useState } from 'react'
import ConversationListItem from './ConversationListItem'
import ConversationListSkeleton from './ConversationListSkeleton'
import ChannelFilterTabs from './ChannelFilterTabs'
import EmptyState from './EmptyState'
import ErrorState from './ErrorState'
import { getChannelConfig } from './channelConfig'
import { SearchIcon, InboxIcon } from './icons'

/**
 * Left pane: conversation list with search, channel filter tabs, loading
 * skeleton, empty state, and error state.
 */
export default function ConversationList({
  status,
  conversations,
  error,
  selectedId,
  onSelect,
  onRetry,
}) {
  const [query, setQuery] = useState('')
  // Display-only filter, colocated here (not lifted to InboxLayout) because
  // it should only affect what's rendered in this list — a conversation
  // selected before switching tabs must stay selected/visible in the thread
  // pane even if its channel no longer matches the active tab.
  const [channelFilter, setChannelFilter] = useState('all')

  const filtered = useMemo(() => {
    if (!conversations) return []
    const byChannel =
      channelFilter === 'all'
        ? conversations
        : conversations.filter((c) => c.channel.platform === channelFilter)
    const q = query.trim().toLowerCase()
    if (!q) return byChannel
    return byChannel.filter(
      (c) =>
        c.contact.display_name.toLowerCase().includes(q) ||
        (c.last_message?.body || '').toLowerCase().includes(q),
    )
  }, [conversations, query, channelFilter])

  const activeChannelName = channelFilter === 'all' ? null : getChannelConfig(channelFilter).name
  const trimmedQuery = query.trim()

  let noResultsTitle = 'No matches'
  let noResultsDescription = `Nothing matches "${trimmedQuery}".`
  if (activeChannelName && !trimmedQuery) {
    noResultsTitle = `No ${activeChannelName} conversations`
    noResultsDescription = `You don't have any ${activeChannelName} conversations yet.`
  } else if (activeChannelName && trimmedQuery) {
    noResultsDescription = `Nothing matches "${trimmedQuery}" in ${activeChannelName}.`
  }

  return (
    <div className="flex h-full min-h-0 flex-col">
      <div className="shrink-0 border-b border-gray-200 px-4 py-3">
        <h1 className="text-lg font-semibold text-gray-900">Inbox</h1>
        <label className="relative mt-2 block">
          <span className="sr-only">Search conversations</span>
          <SearchIcon className="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
          <input
            type="search"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Search conversations"
            disabled={status !== 'success'}
            className="w-full rounded-md border border-gray-200 bg-gray-50 py-1.5 pl-8 pr-2 text-sm text-gray-900 placeholder:text-gray-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600 disabled:opacity-60"
          />
        </label>
      </div>

      {status === 'success' && conversations.length > 0 && (
        <ChannelFilterTabs activeFilter={channelFilter} onChange={setChannelFilter} />
      )}

      <div className="min-h-0 flex-1 overflow-y-auto">
        {status === 'loading' && <ConversationListSkeleton />}

        {status === 'error' && <ErrorState message={error?.message} onRetry={onRetry} />}

        {status === 'success' && conversations.length === 0 && (
          <EmptyState
            icon={InboxIcon}
            title="No conversations yet"
            description="New leads from WhatsApp, Messenger, Instagram, and Telegram will show up here."
          />
        )}

        {status === 'success' && conversations.length > 0 && filtered.length === 0 && (
          <EmptyState icon={InboxIcon} title={noResultsTitle} description={noResultsDescription} />
        )}

        {status === 'success' && filtered.length > 0 && (
          <ul className="divide-y divide-gray-100">
            {filtered.map((conversation) => (
              <ConversationListItem
                key={conversation.id}
                conversation={conversation}
                isSelected={conversation.id === selectedId}
                onSelect={onSelect}
              />
            ))}
          </ul>
        )}
      </div>
    </div>
  )
}
