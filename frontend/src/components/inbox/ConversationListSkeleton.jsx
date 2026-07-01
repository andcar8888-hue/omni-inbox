function SkeletonRow() {
  return (
    <div className="flex items-start gap-3 px-4 py-3">
      <div className="h-9 w-9 shrink-0 rounded-full bg-gray-200" />
      <div className="min-w-0 flex-1 space-y-2">
        <div className="h-3 w-2/3 rounded bg-gray-200" />
        <div className="h-3 w-4/5 rounded bg-gray-100" />
      </div>
      <div className="h-3 w-6 rounded bg-gray-100" />
    </div>
  )
}

/** Loading skeleton for the conversation list. */
export default function ConversationListSkeleton({ rows = 6 }) {
  return (
    <div className="animate-pulse divide-y divide-gray-100" aria-hidden="true">
      {Array.from({ length: rows }).map((_, i) => (
        <SkeletonRow key={i} />
      ))}
    </div>
  )
}
