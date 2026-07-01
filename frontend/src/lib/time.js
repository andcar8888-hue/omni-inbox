// Small formatting helpers shared across the inbox UI.
// All timestamps in mock/real data are UTC ISO strings; we only convert to
// local time for display here (per CLAUDE.md: "convert in the frontend only").

const MINUTE = 60 * 1000
const HOUR = 60 * MINUTE
const DAY = 24 * HOUR

/**
 * WhatsApp-style relative time for conversation list rows: "now", "5m",
 * "3h", weekday for the last 7 days, otherwise a short date.
 */
export function formatRelativeTime(isoString, now = Date.now()) {
  if (!isoString) return ''
  const then = new Date(isoString).getTime()
  const diff = now - then
  if (Number.isNaN(diff)) return ''

  if (diff < MINUTE) return 'now'
  if (diff < HOUR) return `${Math.floor(diff / MINUTE)}m`
  if (diff < DAY) return `${Math.floor(diff / HOUR)}h`
  if (diff < 7 * DAY) {
    return new Date(then).toLocaleDateString(undefined, { weekday: 'short' })
  }
  return new Date(then).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
  })
}

/** Short clock time for message bubbles, e.g. "10:32 AM". */
export function formatMessageTime(isoString) {
  if (!isoString) return ''
  const date = new Date(isoString)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleTimeString(undefined, {
    hour: 'numeric',
    minute: '2-digit',
  })
}

/** Longer date + time, used in the contact panel. */
export function formatFullDateTime(isoString) {
  if (!isoString) return ''
  const date = new Date(isoString)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
