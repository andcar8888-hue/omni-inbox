const WIDTHS = ['w-40', 'w-56', 'w-32', 'w-48', 'w-28']

/** Loading skeleton for the active thread's message list. */
export default function ThreadSkeleton() {
  return (
    <div className="flex flex-1 flex-col justify-end gap-3 p-4" aria-hidden="true">
      {WIDTHS.map((w, i) => (
        <div key={i} className={`flex ${i % 2 === 0 ? 'justify-start' : 'justify-end'}`}>
          <div className={`h-9 ${w} animate-pulse rounded-2xl bg-gray-200`} />
        </div>
      ))}
    </div>
  )
}
