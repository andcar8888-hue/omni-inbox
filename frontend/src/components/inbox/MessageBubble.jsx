import MessageStatusIcon from './MessageStatusIcon'
import { formatMessageTime } from '../../lib/time'

/**
 * A single message bubble. Inbound messages align left in a neutral bubble;
 * outbound messages align right in the brand color and carry a delivery
 * status icon. Failed outbound messages get a red-ringed bubble plus an
 * inline "Failed to send · Retry" affordance underneath — this is the
 * default we picked for the ambiguous "how does failed-to-send look" case.
 */
export default function MessageBubble({ message, onRetry }) {
  const isOutbound = message.direction === 'outbound'
  const isFailed = message.status === 'failed'

  return (
    <div className={`flex ${isOutbound ? 'justify-end' : 'justify-start'}`}>
      <div className={`flex max-w-[75%] flex-col ${isOutbound ? 'items-end' : 'items-start'}`}>
        <div
          className={`whitespace-pre-wrap break-words rounded-2xl px-3.5 py-2 text-sm shadow-sm ${
            isOutbound
              ? `rounded-br-sm bg-emerald-600 text-white ${isFailed ? 'ring-2 ring-red-400' : ''}`
              : 'rounded-bl-sm border border-gray-200 bg-white text-gray-900'
          }`}
        >
          {message.body}
          <span
            className={`ml-2 mt-1 inline-flex items-center gap-1 align-bottom text-[11px] ${
              isOutbound ? 'text-white/70' : 'text-gray-400'
            }`}
          >
            {formatMessageTime(message.created_at)}
            {isOutbound && <MessageStatusIcon status={message.status} />}
          </span>
        </div>
        {isFailed && (
          <p className="mt-1 flex items-center gap-1 text-xs text-red-600">
            Failed to send
            <button
              type="button"
              onClick={() => onRetry?.(message.id)}
              className="font-medium underline decoration-dotted underline-offset-2 hover:text-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
            >
              Retry
            </button>
          </p>
        )}
      </div>
    </div>
  )
}
