const STATUS_CONFIG = {
  open: { label: 'Open', classes: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' },
  pending: { label: 'Pending', classes: 'bg-amber-50 text-amber-700 ring-amber-600/20' },
  closed: { label: 'Closed', classes: 'bg-gray-100 text-gray-600 ring-gray-500/20' },
}

/** Conversation status pill (open / pending / closed). */
export default function StatusPill({ status, className = '' }) {
  const config = STATUS_CONFIG[status] || STATUS_CONFIG.closed
  return (
    <span
      className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset ${config.classes} ${className}`}
    >
      {config.label}
    </span>
  )
}
