import Avatar from './Avatar'
import ChannelBadge from './ChannelBadge'
import StatusPill from './StatusPill'
import ContactPanelSkeleton from './ContactPanelSkeleton'
import EmptyState from './EmptyState'
import ErrorState from './ErrorState'
import { getChannelConfig } from './channelConfig'
import { CloseIcon, InboxIcon } from './icons'
import { formatFullDateTime } from '../../lib/time'

const STATUS_OPTIONS = ['open', 'pending', 'closed']

/** Right pane: contact identity, channel details, and conversation status. */
export default function ContactPanel({ status, conversation, error, onRetry, onClose, onStatusChange }) {
  return (
    <div className="flex h-full flex-col">
      <div className="flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-2.5">
        <h2 className="text-sm font-semibold text-gray-900">Contact info</h2>
        <button
          type="button"
          onClick={onClose}
          aria-label="Close contact panel"
          className="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
        >
          <CloseIcon className="h-4 w-4" />
        </button>
      </div>

      <div className="min-h-0 flex-1 overflow-y-auto">
        {status === 'loading' && <ContactPanelSkeleton />}

        {status === 'error' && <ErrorState message={error?.message} onRetry={onRetry} />}

        {status === 'success' && !conversation && (
          <EmptyState
            icon={InboxIcon}
            title="No conversation selected"
            description="Select a conversation to see contact details here."
          />
        )}

        {status === 'success' && conversation && (
          <ContactPanelDetails conversation={conversation} onStatusChange={onStatusChange} />
        )}
      </div>
    </div>
  )
}

function ContactPanelDetails({ conversation, onStatusChange }) {
  const { contact, channel, status: convStatus, last_message_at: lastMessageAt } = conversation
  const channelConfig = getChannelConfig(channel.platform)

  return (
    <div className="p-4">
      <div className="flex flex-col items-center gap-2 text-center">
        <Avatar id={contact.id} name={contact.display_name} isActiveNow={contact.is_active_now} size="lg" />
        <p className="text-base font-semibold text-gray-900">{contact.display_name}</p>
        <p className="text-xs text-gray-500">{contact.is_active_now ? 'Active now' : 'Offline'}</p>
      </div>

      <dl className="mt-6 space-y-4 text-sm">
        <div>
          <dt className="text-xs font-medium uppercase tracking-wide text-gray-400">Channel</dt>
          <dd className="mt-1 flex items-center gap-2 text-gray-800">
            <ChannelBadge platform={channel.platform} size="md" />
            {channelConfig.name}
          </dd>
          <dd className="mt-0.5 text-xs text-gray-500">{channel.external_account_id}</dd>
        </div>

        <div>
          <dt className="text-xs font-medium uppercase tracking-wide text-gray-400">Last activity</dt>
          <dd className="mt-1 text-gray-800">{formatFullDateTime(lastMessageAt)}</dd>
        </div>

        <div>
          <dt className="text-xs font-medium uppercase tracking-wide text-gray-400" id="conversation-status-label">
            Conversation status
          </dt>
          <dd className="mt-1.5">
            <div className="mb-2">
              <StatusPill status={convStatus} />
            </div>
            <div className="flex gap-1.5" role="group" aria-labelledby="conversation-status-label">
              {STATUS_OPTIONS.map((option) => (
                <button
                  key={option}
                  type="button"
                  onClick={() => onStatusChange(option)}
                  aria-pressed={option === convStatus}
                  className={`rounded-md px-2 py-1 text-xs font-medium capitalize transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 ${
                    option === convStatus
                      ? 'bg-gray-900 text-white'
                      : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                  }`}
                >
                  {option}
                </button>
              ))}
            </div>
          </dd>
        </div>
      </dl>
    </div>
  )
}
