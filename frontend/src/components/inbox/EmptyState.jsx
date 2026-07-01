/** Reusable centered empty-state block used by every pane. */
export default function EmptyState({ icon: Icon, title, description, action, className = '' }) {
  return (
    <div className={`flex flex-1 flex-col items-center justify-center gap-2 px-6 py-12 text-center ${className}`}>
      {Icon && (
        <span className="mb-1 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
          <Icon className="h-6 w-6" />
        </span>
      )}
      <p className="text-sm font-medium text-gray-900">{title}</p>
      {description && <p className="max-w-xs text-sm text-gray-500">{description}</p>}
      {action}
    </div>
  )
}
