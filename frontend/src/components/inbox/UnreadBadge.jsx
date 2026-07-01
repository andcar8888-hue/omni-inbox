/** Unread message count pill. Renders nothing when count is 0. */
export default function UnreadBadge({ count, className = '' }) {
  if (!count) return null
  const display = count > 9 ? '9+' : String(count)
  return (
    <span
      className={`inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-emerald-600 px-1.5 text-xs font-semibold text-white ${className}`}
    >
      <span className="sr-only">{count} unread messages</span>
      <span aria-hidden="true">{display}</span>
    </span>
  )
}
