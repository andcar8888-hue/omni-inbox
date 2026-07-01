/** Loading skeleton for the contact/context panel. */
export default function ContactPanelSkeleton() {
  return (
    <div className="animate-pulse space-y-6 p-4" aria-hidden="true">
      <div className="flex flex-col items-center gap-3">
        <div className="h-16 w-16 rounded-full bg-gray-200" />
        <div className="h-4 w-32 rounded bg-gray-200" />
        <div className="h-3 w-20 rounded bg-gray-100" />
      </div>
      <div className="space-y-3">
        <div className="h-3 w-24 rounded bg-gray-100" />
        <div className="h-3 w-full rounded bg-gray-100" />
        <div className="h-3 w-4/5 rounded bg-gray-100" />
      </div>
    </div>
  )
}
