const PALETTE = [
  'bg-rose-500',
  'bg-amber-500',
  'bg-lime-600',
  'bg-teal-500',
  'bg-cyan-600',
  'bg-indigo-500',
  'bg-fuchsia-500',
]

const SIZE_CLASSES = {
  sm: 'h-9 w-9 text-sm',
  md: 'h-11 w-11 text-base',
  lg: 'h-16 w-16 text-xl',
}

function initialsFor(name) {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  const first = parts[0]?.[0] || ''
  const last = parts.length > 1 ? parts[parts.length - 1][0] : ''
  return (first + last).toUpperCase()
}

/**
 * Initials avatar with an optional "active now" indicator dot — the
 * presence badge is treated as a first-class element per the product spec,
 * not an afterthought bolted onto the name text.
 */
export default function Avatar({ id, name, size = 'sm', isActiveNow = false, className = '' }) {
  const colorIndex = typeof id === 'number' ? id % PALETTE.length : 0
  return (
    <span className={`relative inline-flex shrink-0 ${className}`}>
      <span
        className={`inline-flex items-center justify-center rounded-full font-semibold text-white ${PALETTE[colorIndex]} ${SIZE_CLASSES[size]}`}
      >
        {initialsFor(name)}
      </span>
      {isActiveNow && (
        <span
          className="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white"
          aria-hidden="true"
        />
      )}
      {isActiveNow && <span className="sr-only">Active now</span>}
    </span>
  )
}
