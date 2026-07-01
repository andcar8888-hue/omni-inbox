import { CheckIcon, DoubleCheckIcon, AlertIcon } from './icons'

const CONFIG = {
  sent: { Icon: CheckIcon, className: 'text-white/70', label: 'Sent' },
  delivered: { Icon: DoubleCheckIcon, className: 'text-white/70', label: 'Delivered' },
  read: { Icon: DoubleCheckIcon, className: 'text-sky-300', label: 'Read' },
  failed: { Icon: AlertIcon, className: 'text-red-200', label: 'Failed to send' },
}

/** Delivery status indicator for outbound messages (messages.status enum). */
export default function MessageStatusIcon({ status, className = '' }) {
  const config = CONFIG[status] || CONFIG.sent
  const { Icon } = config
  return (
    <span className={`inline-flex items-center ${config.className} ${className}`}>
      <span className="sr-only">{config.label}</span>
      <Icon className="h-3.5 w-3.5" />
    </span>
  )
}
