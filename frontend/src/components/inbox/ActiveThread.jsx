import { useEffect, useRef } from 'react'
import ThreadHeader from './ThreadHeader'
import MessageBubble from './MessageBubble'
import MessageComposer from './MessageComposer'
import ThreadSkeleton from './ThreadSkeleton'
import EmptyState from './EmptyState'
import ErrorState from './ErrorState'
import { ChatIcon } from './icons'

/** Center pane: message thread for the selected conversation. */
export default function ActiveThread({
  conversation,
  status,
  messages,
  error,
  onRetry,
  onSend,
  onRetryMessage,
  onBack,
  onToggleContactPanel,
  isContactPanelOpen,
}) {
  const scrollRef = useRef(null)

  useEffect(() => {
    if (status === 'success') {
      scrollRef.current?.scrollTo({ top: scrollRef.current.scrollHeight })
    }
  }, [status, messages])

  if (!conversation) {
    return (
      <div className="flex h-full flex-col">
        <EmptyState
          icon={ChatIcon}
          title="Select a conversation"
          description="Pick a conversation from the list to see the message history and reply."
        />
      </div>
    )
  }

  return (
    <div className="flex h-full flex-col">
      <ThreadHeader
        conversation={conversation}
        onBack={onBack}
        onToggleContactPanel={onToggleContactPanel}
        isContactPanelOpen={isContactPanelOpen}
      />

      <div ref={scrollRef} className="flex min-h-0 flex-1 flex-col overflow-y-auto bg-gray-50">
        {status === 'loading' && <ThreadSkeleton />}

        {status === 'error' && <ErrorState message={error?.message} onRetry={onRetry} />}

        {status === 'success' && messages.length === 0 && (
          <EmptyState
            icon={ChatIcon}
            title="No messages yet"
            description="Send the first message to start this conversation."
          />
        )}

        {status === 'success' && messages.length > 0 && (
          <div className="flex flex-1 flex-col justify-end gap-2 p-4">
            {messages.map((message) => (
              <MessageBubble key={message.id} message={message} onRetry={onRetryMessage} />
            ))}
          </div>
        )}
      </div>

      <MessageComposer onSend={onSend} disabled={status !== 'success'} />
    </div>
  )
}
