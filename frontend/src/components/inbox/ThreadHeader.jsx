import Avatar from './Avatar'
import ChannelBadge from './ChannelBadge'
import StatusPill from './StatusPill'
import { BackIcon, InfoIcon } from './icons'

/** Header for the active thread: contact identity + mobile back + contact-panel toggle. */
export default function ThreadHeader({ conversation, onBack, onToggleContactPanel, isContactPanelOpen }) {
  const { contact, channel, status } = conversation
  return (
    <div className="flex shrink-0 items-center gap-2 border-b border-gray-200 bg-white px-3 py-2.5">
      <button
        type="button"
        onClick={onBack}
        aria-label="Back to conversations"
        className="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 md:hidden"
      >
        <BackIcon className="h-5 w-5" />
      </button>

      <Avatar id={contact.id} name={contact.display_name} isActiveNow={contact.is_active_now} />

      <div className="min-w-0 flex-1">
        <div className="flex items-center gap-1.5">
          <p className="truncate text-sm font-semibold text-gray-900">{contact.display_name}</p>
          <ChannelBadge platform={channel.platform} />
        </div>
        <p className="truncate text-xs text-gray-500">
          {contact.is_active_now ? 'Active now' : 'Offline'}
        </p>
      </div>

      <StatusPill status={status} className="hidden sm:inline-flex" />

      <button
        type="button"
        onClick={onToggleContactPanel}
        aria-label={isContactPanelOpen ? 'Hide contact info' : 'Show contact info'}
        aria-pressed={isContactPanelOpen}
        className={`rounded-md p-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 ${
          isContactPanelOpen ? 'bg-emerald-50 text-emerald-700' : 'text-gray-500 hover:bg-gray-100'
        }`}
      >
        <InfoIcon className="h-5 w-5" />
      </button>
    </div>
  )
}
