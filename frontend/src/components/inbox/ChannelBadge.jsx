import { getChannelConfig } from './channelConfig'

const SIZE_CLASSES = {
  sm: 'h-4 min-w-4 px-1 text-[10px]',
  md: 'h-5 min-w-5 px-1.5 text-[11px]',
}

/** Small colored abbreviation badge for a channel platform (WA/FB/IG/TG). */
export default function ChannelBadge({ platform, size = 'sm', className = '' }) {
  const config = getChannelConfig(platform)
  return (
    <span
      className={`inline-flex items-center justify-center rounded font-semibold tracking-wide ${SIZE_CLASSES[size]} ${config.badgeClasses} ${className}`}
      title={config.name}
    >
      <span className="sr-only">{config.name} · </span>
      {config.label}
    </span>
  )
}
