import { useMemo, useState } from 'react'
import ConversationList from './ConversationList'
import ActiveThread from './ActiveThread'
import ContactPanel from './ContactPanel'
import { useConversations, useMessages, useSendMessage } from '../../hooks/useInboxData'
import { useAuth } from '../../auth/authContext'

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
 *
 * Server state comes from React Query (useConversations / useMessages) with
 * polling, and outbound sends go through a real mutation that invalidates the
 * affected queries. A few UI-only overrides remain local because the current
 * backend has no endpoint for them yet: marking a conversation read on open,
 * and changing conversation status from the contact panel.
 */
export default function InboxLayout() {
  const { user, logout } = useAuth()
  const [view, setView] = useState('list')
  const [selectedId, setSelectedId] = useState(null)
  const [isContactPanelOpen, setIsContactPanelOpen] = useState(
    () => typeof window !== 'undefined' && window.matchMedia('(min-width: 1280px)').matches,
  )

  // UI-only overrides layered over server data (no backend endpoint yet).
  const [conversationStatusOverrides, setConversationStatusOverrides] = useState({})
  const [readConversationIds, setReadConversationIds] = useState({})

  const conversationsResult = useConversations()
  const messagesResult = useMessages(selectedId)
  const sendMutation = useSendMessage(selectedId)

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

  const messages = useMemo(() => messagesResult.data ?? [], [messagesResult.data])

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
    // Mutation success invalidates the messages + conversations queries, so the
    // thread refetches with the persisted message rather than an optimistic
    // stand-in. Polling would also pick it up, but invalidation is immediate.
    sendMutation.mutate(text)
  }

  function handleStatusChange(newStatus) {
    if (!selectedId) return
    setConversationStatusOverrides((prev) => ({ ...prev, [selectedId]: newStatus }))
  }

  const accountLabel = user?.name || user?.email || 'Account'

  return (
    <div className="flex h-full flex-col bg-white">
      <header className="flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-2.5">
        <span className="text-sm font-semibold text-gray-900">Omni-Inbox</span>
        <div className="flex items-center gap-3">
          <span className="hidden text-xs text-gray-500 sm:inline">{accountLabel}</span>
          <button
            type="button"
            onClick={logout}
            className="rounded-md px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
          >
            Sign out
          </button>
        </div>
      </header>

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
            onRetry={() => conversationsResult.refetch()}
          />
        </div>

        <div className={`${view === 'thread' ? 'flex' : 'hidden'} md:flex min-w-0 flex-1 flex-col`}>
          <ActiveThread
            conversation={selectedConversation}
            status={messagesResult.status}
            messages={messages}
            error={messagesResult.error}
            onRetry={() => messagesResult.refetch()}
            onSend={handleSend}
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
            onRetry={() => conversationsResult.refetch()}
            onClose={handleCloseContactPanel}
            onStatusChange={handleStatusChange}
          />
        </div>
      </div>
    </div>
  )
}
