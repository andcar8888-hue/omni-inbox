import Avatar from './Avatar'
import ChannelBadge from './ChannelBadge'
import UnreadBadge from './UnreadBadge'
import { formatRelativeTime } from '../../lib/time'

function previewText(lastMessage) {
  if (!lastMessage) return 'No messages yet'
  const prefix = lastMessage.direction === 'outbound' ? 'You: ' : ''
  return `${prefix}${lastMessage.body || ''}`
}

/** A single row in the conversation list. */
export default function ConversationListItem({ conversation, isSelected, onSelect }) {
  const { contact, channel, unread_count: unreadCount, last_message_at: lastMessageAt, last_message: lastMessage } = conversation
  const isUnread = unreadCount > 0

  return (
    <li>
      <button
        type="button"
        onClick={() => onSelect(conversation.id)}
        aria-current={isSelected ? 'true' : undefined}
        className={`flex w-full items-start gap-3 border-l-2 px-4 py-3 text-left transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-emerald-600 ${
          isSelected
            ? 'border-emerald-600 bg-emerald-50'
            : 'border-transparent hover:bg-gray-50'
        }`}
      >
        <Avatar id={contact.id} name={contact.display_name} isActiveNow={contact.is_active_now} />
        <span className="min-w-0 flex-1">
          <span className="flex items-center gap-1.5">
            <span className={`truncate text-sm ${isUnread ? 'font-semibold text-gray-900' : 'font-medium text-gray-800'}`}>
              {contact.display_name}
            </span>
            <ChannelBadge platform={channel.platform} />
          </span>
          <span className="mt-0.5 flex items-center justify-between gap-2">
            <span className={`truncate text-sm ${isUnread ? 'font-medium text-gray-700' : 'text-gray-500'}`}>
              {previewText(lastMessage)}
            </span>
          </span>
        </span>
        <span className="flex shrink-0 flex-col items-end gap-1.5">
          <span className="text-xs text-gray-400">{formatRelativeTime(lastMessageAt)}</span>
          <UnreadBadge count={unreadCount} />
        </span>
      </button>
    </li>
  )
}
