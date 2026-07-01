// Mock data shaped after docs/db-schema.sql (businesses / channels / contacts /
// conversations / messages). In Phase 4 this file goes away and the fetch
// functions in ./inboxApi.js start hitting `/api/v1/...` instead — the shape
// of what components consume (enriched conversation objects, message arrays)
// is designed to match what those endpoints will realistically return.

const MIN = 60 * 1000
const HOUR = 60 * MIN
const DAY = 24 * HOUR
const now = Date.now()
const ago = (ms) => new Date(now - ms).toISOString()

export const MOCK_BUSINESS = {
  id: 1,
  name: 'Aroma & Co. Coffee Roasters',
  created_at: ago(365 * DAY),
}

// channels.platform enum: 'whatsapp' | 'messenger' | 'instagram' | 'telegram'
export const MOCK_CHANNELS = [
  { id: 1, business_id: 1, platform: 'whatsapp', external_account_id: '+1 555-0142' },
  { id: 2, business_id: 1, platform: 'messenger', external_account_id: 'aroma.coffee.page' },
  { id: 3, business_id: 1, platform: 'instagram', external_account_id: '@aroma.coffee' },
  { id: 4, business_id: 1, platform: 'telegram', external_account_id: '@AromaCoffeeBot' },
]

const channelById = Object.fromEntries(MOCK_CHANNELS.map((c) => [c.id, c]))

// contacts table, plus a UI-only `is_active_now` flag (not in the real
// schema — see design notes) used to drive the "active now" indicator.
export const MOCK_CONTACTS = [
  { id: 1, channel_id: 1, external_contact_id: 'wa_15550001', display_name: 'Priya Nair', is_active_now: true },
  { id: 2, channel_id: 2, external_contact_id: 'psid_88231', display_name: 'Marcus Idoko', is_active_now: false },
  { id: 3, channel_id: 3, external_contact_id: 'ig_scoped_5521', display_name: 'Lena Fischer', is_active_now: true },
  { id: 4, channel_id: 4, external_contact_id: 'chat_991234', display_name: 'Tomás Reyes', is_active_now: false },
  { id: 5, channel_id: 1, external_contact_id: 'wa_15550002', display_name: 'Grace Okafor', is_active_now: false },
  { id: 6, channel_id: 2, external_contact_id: 'psid_88232', display_name: 'Ben Whitfield', is_active_now: false },
  { id: 7, channel_id: 3, external_contact_id: 'ig_scoped_5522', display_name: 'Sofia Marchetti', is_active_now: false },
]

const contactById = Object.fromEntries(MOCK_CONTACTS.map((c) => [c.id, c]))

// conversations table. status enum: 'open' | 'pending' | 'closed'
const RAW_CONVERSATIONS = [
  { id: 1, channel_id: 1, contact_id: 1, assigned_user_id: 1, status: 'open', last_message_at: ago(3 * MIN), unread_count: 2 },
  { id: 2, channel_id: 3, contact_id: 3, assigned_user_id: 1, status: 'open', last_message_at: ago(40 * MIN), unread_count: 0 },
  { id: 3, channel_id: 2, contact_id: 2, assigned_user_id: null, status: 'pending', last_message_at: ago(2 * HOUR), unread_count: 1 },
  { id: 4, channel_id: 4, contact_id: 4, assigned_user_id: 1, status: 'open', last_message_at: ago(5 * HOUR), unread_count: 0 },
  { id: 5, channel_id: 1, contact_id: 5, assigned_user_id: 1, status: 'closed', last_message_at: ago(2 * DAY), unread_count: 0 },
  { id: 6, channel_id: 2, contact_id: 6, assigned_user_id: null, status: 'pending', last_message_at: ago(6 * DAY), unread_count: 3 },
  { id: 7, channel_id: 3, contact_id: 7, assigned_user_id: 1, status: 'open', last_message_at: ago(21 * DAY), unread_count: 0 },
]

// messages table, keyed by conversation_id.
const RAW_MESSAGES = {
  1: [
    { id: 101, conversation_id: 1, direction: 'inbound', body: 'Hi! Do you still have the Ethiopia Yirgacheffe in stock?', status: 'read', created_at: ago(60 * MIN) },
    { id: 102, conversation_id: 1, direction: 'outbound', sender_user_id: 1, body: 'Hey Priya, yes we do! 250g and 500g bags available.', status: 'read', created_at: ago(55 * MIN) },
    { id: 103, conversation_id: 1, direction: 'inbound', body: "Great, I'll take a 500g bag. Can I collect today?", status: 'read', created_at: ago(50 * MIN) },
    { id: 104, conversation_id: 1, direction: 'outbound', sender_user_id: 1, body: 'Yep, we’re open till 6pm. I’ll set one aside for you.', status: 'delivered', created_at: ago(20 * MIN) },
    { id: 105, conversation_id: 1, direction: 'inbound', body: 'Perfect, thank you!', status: 'read', created_at: ago(4 * MIN) },
    { id: 106, conversation_id: 1, direction: 'inbound', body: 'Oh, one more thing — do you do oat milk?', status: 'read', created_at: ago(3 * MIN) },
  ],
  2: [
    { id: 201, conversation_id: 2, direction: 'inbound', body: 'Saw your story about the new cold brew, is it available yet?', status: 'read', created_at: ago(2 * HOUR) },
    { id: 202, conversation_id: 2, direction: 'outbound', sender_user_id: 1, body: 'Launching Saturday! Want me to save you a bottle?', status: 'read', created_at: ago(90 * MIN) },
    { id: 203, conversation_id: 2, direction: 'inbound', body: 'Yes please, 2 bottles if possible', status: 'read', created_at: ago(41 * MIN) },
    { id: 204, conversation_id: 2, direction: 'outbound', sender_user_id: 1, body: 'Done — see you Saturday!', status: 'sent', created_at: ago(40 * MIN) },
  ],
  3: [
    { id: 301, conversation_id: 3, direction: 'inbound', body: "Hi, I run a small office nearby — do you offer wholesale bags?", status: 'read', created_at: ago(3 * HOUR) },
    { id: 302, conversation_id: 3, direction: 'outbound', sender_user_id: 1, body: 'We do! What volume are you thinking per month?', status: 'failed', created_at: ago(2 * HOUR) },
  ],
  4: [
    { id: 401, conversation_id: 4, direction: 'inbound', body: 'Is the roastery tour still happening this weekend?', status: 'read', created_at: ago(6 * HOUR) },
    { id: 402, conversation_id: 4, direction: 'outbound', sender_user_id: 1, body: 'Yes! Saturday 10am, spots are first-come first-served.', status: 'read', created_at: ago(5 * HOUR) },
  ],
  // Conversation 5: no messages loaded yet — used to exercise the "no
  // messages" empty state inside an otherwise normal thread.
  5: [],
  6: [
    { id: 601, conversation_id: 6, direction: 'inbound', body: 'Hey, following up on the catering order for Friday', status: 'delivered', created_at: ago(6 * DAY) },
    { id: 602, conversation_id: 6, direction: 'inbound', body: 'Also can we add 2 more boxes of pastries?', status: 'delivered', created_at: ago(6 * DAY + 10 * MIN) },
    { id: 603, conversation_id: 6, direction: 'inbound', body: 'Let me know when you can 🙏', status: 'delivered', created_at: ago(6 * DAY + 12 * MIN) },
  ],
  7: [
    { id: 701, conversation_id: 7, direction: 'inbound', body: 'Loved the latte art workshop last month!', status: 'read', created_at: ago(21 * DAY) },
    { id: 702, conversation_id: 7, direction: 'outbound', sender_user_id: 1, body: "So glad you enjoyed it — we're running another one next quarter.", status: 'read', created_at: ago(21 * DAY + 30 * MIN) },
  ],
}

function enrichConversation(conv) {
  const channel = channelById[conv.channel_id]
  const contact = contactById[conv.contact_id]
  const messages = RAW_MESSAGES[conv.id] || []
  const lastMessage = messages[messages.length - 1] || null
  return {
    ...conv,
    channel: { id: channel.id, platform: channel.platform, external_account_id: channel.external_account_id },
    contact: { id: contact.id, display_name: contact.display_name, is_active_now: contact.is_active_now },
    last_message: lastMessage
      ? { direction: lastMessage.direction, body: lastMessage.body, status: lastMessage.status }
      : null,
  }
}

export const MOCK_CONVERSATIONS = RAW_CONVERSATIONS
  .map(enrichConversation)
  .sort((a, b) => new Date(b.last_message_at) - new Date(a.last_message_at))

export function getMockMessages(conversationId) {
  return (RAW_MESSAGES[conversationId] || []).slice()
}
