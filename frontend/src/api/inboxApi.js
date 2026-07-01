// Mock "API" for the inbox. Shaped as async functions on purpose: in Phase 4
// these bodies get swapped for real `fetch('/api/v1/...')` calls (and the
// hooks in src/hooks/useInboxData.js can move to React Query with almost no
// change to the components that consume them).

import { MOCK_CONVERSATIONS, getMockMessages } from './mockData'

const NETWORK_DELAY_MS = 500

function wait(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

/**
 * @param {'success'|'empty'|'error'} scenario dev-only override to exercise
 *   loading/empty/error states without a real backend.
 */
export async function fetchConversations(scenario = 'success') {
  await wait(NETWORK_DELAY_MS)
  if (scenario === 'error') {
    throw new Error('Could not reach the inbox service. Check your connection and try again.')
  }
  if (scenario === 'empty') return []
  return MOCK_CONVERSATIONS
}

export async function fetchMessages(conversationId, scenario = 'success') {
  await wait(NETWORK_DELAY_MS)
  if (scenario === 'error') {
    throw new Error('Could not load this conversation. Try again.')
  }
  if (scenario === 'empty' || conversationId == null) return []
  return getMockMessages(conversationId)
}
