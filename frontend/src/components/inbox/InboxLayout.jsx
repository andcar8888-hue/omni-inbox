import { useMemo, useState } from 'react'
import ConversationList from './ConversationList'
import ActiveThread from './ActiveThread'
import ContactPanel from './ContactPanel'
import DevScenarioBar from './DevScenarioBar'
import { useConversations, useMessages } from '../../hooks/useInboxData'
import { MOCK_BUSINESS } from '../../api/mockData'

const DESKTOP_QUERY = '(min-width: 768px)'

function isDesktopViewport() {
  return typeof window !== 'undefined' && window.matchMedia(DESKTOP_QUERY).matches
}

/**
 * Top-level 3-pane inbox: conversation list / active thread / contact panel.
 *
 * Mobile (<768px): one pane visible at a time, driven by `view`
 * ('list' | 'thread' | 'contact'), with a back button in the thread header.
 * Desktop (>=768px): list + thread are always visible side by side; the
 * contact panel is independently toggled via `isContactPanelOpen`, which
 * defaults to open only on wide (xl, >=1280px) screens so it collapses
 * automatically on narrower desktop/tablet widths per the spec.
 */
export default function InboxLayout() {
  const [view, setView] = useState('list')
  const [selectedId, setSelectedId] = useState(null)
  const [isContactPanelOpen, setIsContactPanelOpen] = useState(
    () => typeof window !== 'undefined' && window.matchMedia('(min-width: 1280px)').matches,
  )

  // Dev-only harness state — lets every loading/empty/error state be
  // reached from the UI instead of living as untested dead code.
  const [conversationsScenario, setConversationsScenario] = useState('success')
  const [messagesScenario, setMessagesScenario] = useState('success')
  const [conversationsRetryKey, setConversationsRetryKey] = useState(0)
  const [messagesRetryKey, setMessagesRetryKey] = useState(0)

  // Local-only optimistic state layered over the mock "server" data so the
  // composer, retry, and status changer feel real without a backend yet.
  const [localMessagesByConversation, setLocalMessagesByConversation] = useState({})
  const [messageStatusOverrides, setMessageStatusOverrides] = useState({})
  const [conversationStatusOverrides, setConversationStatusOverrides] = useState({})
  const [readConversationIds, setReadConversationIds] = useState({})

  const conversationsResult = useConversations(conversationsScenario, conversationsRetryKey)

  const conversations = useMemo(() => {
    if (!conversationsResult.data) return null
    return conversationsResult.data.map((conversation) => ({
      ...conversation,
      status: conversationStatusOverrides[conversation.id] ?? conversation.status,
      unread_count: readConversationIds[conversation.id] ? 0 : conversation.unread_count,
    }))
  }, [conversationsResult.data, conversationStatusOverrides, readConversationIds])

  const selectedConversation = useMemo(
    () => conversations?.find((c) => c.id === selectedId) ?? null,
    [conversations, selectedId],
  )

  const messagesResult = useMessages(selectedId, messagesScenario, messagesRetryKey)

  const messages = useMemo(() => {
    const fetched = messagesResult.data || []
    const local = localMessagesByConversation[selectedId] || []
    return [...fetched, ...local].map((message) =>
      messageStatusOverrides[message.id]
        ? { ...message, status: messageStatusOverrides[message.id] }
        : message,
    )
  }, [messagesResult.data, localMessagesByConversation, selectedId, messageStatusOverrides])

  function handleSelectConversation(id) {
    setSelectedId(id)
    setReadConversationIds((prev) => ({ ...prev, [id]: true }))
    setView('thread')
  }

  function handleBackToList() {
    setView('list')
  }

  function handleToggleContactPanel() {
    if (isDesktopViewport()) {
      setIsContactPanelOpen((open) => !open)
    } else {
      setView((current) => (current === 'contact' ? 'thread' : 'contact'))
    }
  }

  function handleCloseContactPanel() {
    if (isDesktopViewport()) {
      setIsContactPanelOpen(false)
    } else {
      setView('thread')
    }
  }

  function handleSend(text) {
    if (!selectedId) return
    const message = {
      id: `local-${Date.now()}`,
      conversation_id: selectedId,
      direction: 'outbound',
      sender_user_id: 1,
      body: text,
      status: 'sent',
      created_at: new Date().toISOString(),
    }
    setLocalMessagesByConversation((prev) => ({
      ...prev,
      [selectedId]: [...(prev[selectedId] || []), message],
    }))
  }

  function handleRetryMessage(messageId) {
    setMessageStatusOverrides((prev) => ({ ...prev, [messageId]: 'sent' }))
  }

  function handleStatusChange(newStatus) {
    if (!selectedId) return
    setConversationStatusOverrides((prev) => ({ ...prev, [selectedId]: newStatus }))
  }

  return (
    <div className="flex h-full flex-col bg-white">
      <header className="flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-2.5">
        <span className="text-sm font-semibold text-gray-900">Omni-Inbox</span>
        <span className="hidden text-xs text-gray-500 sm:inline">{MOCK_BUSINESS.name}</span>
      </header>

      <DevScenarioBar
        conversationsScenario={conversationsScenario}
        onConversationsScenarioChange={setConversationsScenario}
        messagesScenario={messagesScenario}
        onMessagesScenarioChange={setMessagesScenario}
      />

      <div className="flex min-h-0 flex-1 overflow-hidden">
        <div
          className={`${view === 'list' ? 'flex' : 'hidden'} md:flex w-full flex-col border-r border-gray-200 md:w-80 lg:w-96`}
        >
          <ConversationList
            status={conversationsResult.status}
            conversations={conversations}
            error={conversationsResult.error}
            selectedId={selectedId}
            onSelect={handleSelectConversation}
            onRetry={() => setConversationsRetryKey((k) => k + 1)}
          />
        </div>

        <div className={`${view === 'thread' ? 'flex' : 'hidden'} md:flex min-w-0 flex-1 flex-col`}>
          <ActiveThread
            conversation={selectedConversation}
            status={messagesResult.status}
            messages={messages}
            error={messagesResult.error}
            onRetry={() => setMessagesRetryKey((k) => k + 1)}
            onSend={handleSend}
            onRetryMessage={handleRetryMessage}
            onBack={handleBackToList}
            onToggleContactPanel={handleToggleContactPanel}
            isContactPanelOpen={isContactPanelOpen}
          />
        </div>

        <div
          className={[
            'flex-col overflow-hidden border-l border-gray-200',
            view === 'contact' ? 'flex' : 'hidden',
            isContactPanelOpen ? 'md:flex' : 'md:hidden',
            'w-full md:w-72 lg:w-80',
          ].join(' ')}
        >
          <ContactPanel
            status={conversationsResult.status}
            conversation={selectedConversation}
            error={conversationsResult.error}
            onRetry={() => setConversationsRetryKey((k) => k + 1)}
            onClose={handleCloseContactPanel}
            onStatusChange={handleStatusChange}
          />
        </div>
      </div>
    </div>
  )
}
