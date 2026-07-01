import { useEffect, useState } from 'react'
import { fetchConversations, fetchMessages } from '../api/inboxApi'

// Both hooks below resolve to { status: 'loading'|'success'|'error', data, error }
// — the same shape a `useQuery` result exposes — so swapping the mock
// fetchers for real API calls (and these hooks for React Query) in Phase 4
// shouldn't require touching any consuming component.
//
// `scenario` is a dev-only override ('success' | 'loading' | 'empty' |
// 'error') used to exercise every UI state without a real backend;
// `retryKey` is bumped by "Try again" buttons to force a refetch attempt.

/** Fetches the conversation list for the left pane. */
export function useConversations(scenario = 'success', retryKey = 0) {
  const [state, setState] = useState({ status: 'loading', data: null, error: null })

  useEffect(() => {
    let cancelled = false

    if (scenario === 'loading') {
      setState({ status: 'loading', data: null, error: null })
      return () => {
        cancelled = true
      }
    }

    setState((prev) => ({ ...prev, status: 'loading' }))
    fetchConversations(scenario)
      .then((data) => {
        if (!cancelled) setState({ status: 'success', data, error: null })
      })
      .catch((error) => {
        if (!cancelled) setState({ status: 'error', data: null, error })
      })

    return () => {
      cancelled = true
    }
  }, [scenario, retryKey])

  return state
}

/** Fetches messages for the selected conversation, for the center pane. */
export function useMessages(conversationId, scenario = 'success', retryKey = 0) {
  const [state, setState] = useState({ status: 'loading', data: null, error: null })

  useEffect(() => {
    let cancelled = false

    if (scenario === 'loading') {
      setState({ status: 'loading', data: null, error: null })
      return () => {
        cancelled = true
      }
    }

    setState((prev) => ({ ...prev, status: 'loading' }))
    fetchMessages(conversationId, scenario)
      .then((data) => {
        if (!cancelled) setState({ status: 'success', data, error: null })
      })
      .catch((error) => {
        if (!cancelled) setState({ status: 'error', data: null, error })
      })

    return () => {
      cancelled = true
    }
  }, [conversationId, scenario, retryKey])

  return state
}
