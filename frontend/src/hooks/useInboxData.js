import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { fetchConversations, fetchMessages, sendMessage } from '../api/conversations'

// React Query hooks backing the inbox. They expose the same
// `{ status: 'loading'|'success'|'error', data, error }` shape the components
// already consume — React Query's own status is 'pending'|'success'|'error',
// so we map 'pending' -> 'loading' and pass through `refetch` for real retry
// buttons.

const POLL_INTERVAL_MS = 7000

/** Normalize a React Query result to the app's { status, data, error, refetch }. */
function toResult(query) {
  return {
    status: query.status === 'pending' ? 'loading' : query.status,
    data: query.data ?? null,
    error: query.error ?? null,
    refetch: query.refetch,
  }
}

export const conversationsKey = () => ['conversations']
export const messagesKey = (conversationId) => ['messages', conversationId]

/** Conversation list for the left pane. Polls so new inbound threads appear. */
export function useConversations() {
  const query = useQuery({
    queryKey: conversationsKey(),
    queryFn: fetchConversations,
    refetchInterval: POLL_INTERVAL_MS,
  })
  return toResult(query)
}

/**
 * Messages for the selected conversation (center pane). Only enabled — and only
 * polled — when a conversation is actually selected, so we never poll every
 * conversation's messages, just the open one.
 */
export function useMessages(conversationId) {
  const query = useQuery({
    queryKey: messagesKey(conversationId),
    queryFn: () => fetchMessages(conversationId),
    enabled: conversationId != null,
    refetchInterval: conversationId != null ? POLL_INTERVAL_MS : false,
  })

  // With `enabled:false` (no selection) React Query reports status 'pending'
  // forever; the thread pane handles the "no conversation selected" case via
  // the absence of a `conversation` prop, so that's fine.
  return toResult(query)
}

/**
 * Send an outbound reply. On success, refetch this conversation's messages and
 * the list (whose ordering/last_message_at changes) so the UI never shows a
 * stale thread after sending.
 */
export function useSendMessage(conversationId) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (body) => sendMessage(conversationId, body),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: messagesKey(conversationId) })
      queryClient.invalidateQueries({ queryKey: conversationsKey() })
    },
  })
}
