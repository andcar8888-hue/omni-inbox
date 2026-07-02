// Conversation + message API functions and the adapters that reshape the
// backend's flat rows into the nested objects the inbox UI components already
// consume (conversation.channel.*, conversation.contact.*, etc.).
//
// This adapter layer is deliberate: the presentation components were built
// against a nested shape, and mapping here (rather than rewriting every
// component) keeps churn minimal. Fields the current API does not provide are
// filled with safe defaults and documented inline.

import { apiRequest } from './client'

/**
 * The GET /conversations rows are flat:
 *   { id, channel_id, contact_id, assigned_user_id, status, last_message_at,
 *     unread_count, channel_platform, contact_display_name, contact_external_id }
 *
 * The UI expects nested `channel`, `contact`, and a `last_message` preview.
 */
function adaptConversation(row) {
  return {
    id: row.id,
    channel_id: row.channel_id,
    contact_id: row.contact_id,
    assigned_user_id: row.assigned_user_id,
    status: row.status,
    last_message_at: row.last_message_at,
    unread_count: row.unread_count ?? 0,
    channel: {
      id: row.channel_id,
      platform: row.channel_platform,
      // The list endpoint does not expose the channel's own account handle;
      // the contact's external id is the closest identifier we have, and the
      // ContactPanel renders it under the channel name.
      external_account_id: row.contact_external_id ?? null,
    },
    contact: {
      id: row.contact_id,
      display_name: row.contact_display_name,
      // Presence ("Active now") is a UI-only concept the mock had; the real
      // schema/API has no presence signal, so contacts read as offline until
      // a presence source exists.
      is_active_now: false,
    },
    // The list endpoint returns no last-message preview. Rather than fetch
    // messages per row (N+1), the preview shows a neutral placeholder until a
    // backend preview field is added.
    last_message: null,
  }
}

function adaptMessage(row) {
  // Messages already match what MessageBubble consumes
  // (id, direction, body, status, created_at). Passed through as-is.
  return row
}

/** GET /conversations — list for the authenticated user's business. */
export async function fetchConversations() {
  const rows = await apiRequest('/conversations')
  return (rows ?? []).map(adaptConversation)
}

/** GET /conversations/{id}/messages — chronological messages. */
export async function fetchMessages(conversationId) {
  const rows = await apiRequest(`/conversations/${conversationId}/messages`)
  return (rows ?? []).map(adaptMessage)
}

/** POST /conversations/{id}/messages — send an outbound reply. */
export async function sendMessage(conversationId, body) {
  const row = await apiRequest(`/conversations/${conversationId}/messages`, {
    method: 'POST',
    body: { body },
  })
  return adaptMessage(row)
}
