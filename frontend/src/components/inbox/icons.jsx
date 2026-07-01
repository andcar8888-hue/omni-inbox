// Small, dependency-free inline icon set used across the inbox UI.
// Each icon is purely decorative (aria-hidden) — the interactive element
// wrapping it (button/link) is responsible for its own accessible name.

const base = {
  fill: 'none',
  stroke: 'currentColor',
  strokeWidth: 1.8,
  strokeLinecap: 'round',
  strokeLinejoin: 'round',
  viewBox: '0 0 24 24',
  'aria-hidden': 'true',
  focusable: 'false',
}

export function SearchIcon(props) {
  return (
    <svg {...base} className={props.className}>
      <circle cx="11" cy="11" r="7" />
      <path d="M21 21l-4.3-4.3" />
    </svg>
  )
}

export function SendIcon(props) {
  return (
    <svg {...base} className={props.className}>
      <path d="M4 12L20 4l-6 16-3-7-7-3z" />
    </svg>
  )
}

export function PaperclipIcon(props) {
  return (
    <svg {...base} className={props.className}>
      <path d="M21 12.5l-8.5 8.5a4.2 4.2 0 01-6-6l8-8a2.8 2.8 0 014 4l-7.9 7.9a1.4 1.4 0 01-2-2l7.1-7.1" />
    </svg>
  )
}

export function BackIcon(props) {
  return (
    <svg {...base} className={props.className}>
      <path d="M15 18l-6-6 6-6" />
    </svg>
  )
}

export function InfoIcon(props) {
  return (
    <svg {...base} className={props.className}>
      <circle cx="12" cy="12" r="9" />
      <path d="M12 11v6" />
      <path d="M12 7.5v.01" />
    </svg>
  )
}

export function CloseIcon(props) {
  return (
    <svg {...base} className={props.className}>
      <path d="M6 6l12 12M18 6L6 18" />
    </svg>
  )
}

export function CheckIcon(props) {
  return (
    <svg {...base} className={props.className}>
      <path d="M4 12l5 5L20 6" />
    </svg>
  )
}

export function DoubleCheckIcon(props) {
  return (
    <svg {...base} className={props.className}>
      <path d="M1 12l5 5L17 6" />
      <path d="M7 12l5 5L23 6" />
    </svg>
  )
}

export function AlertIcon(props) {
  return (
    <svg {...base} className={props.className}>
      <path d="M12 9v4" />
      <path d="M12 16.5v.01" />
      <path d="M10.3 3.9L2.5 18a1.6 1.6 0 001.4 2.4h16.2a1.6 1.6 0 001.4-2.4L13.7 3.9a1.6 1.6 0 00-2.8 0z" />
    </svg>
  )
}

export function InboxIcon(props) {
  return (
    <svg {...base} className={props.className}>
      <path d="M4 12h4l2 3h4l2-3h4" />
      <path d="M5.5 5h13l2 7v6a1.5 1.5 0 01-1.5 1.5h-14A1.5 1.5 0 013.5 18v-6l2-7z" />
    </svg>
  )
}

export function ChatIcon(props) {
  return (
    <svg {...base} className={props.className}>
      <path d="M21 11.5a8.4 8.4 0 01-8.9 8.4 8.7 8.7 0 01-3.6-.8L3 20l1-5.2A8.4 8.4 0 1121 11.5z" />
    </svg>
  )
}

export function RefreshIcon(props) {
  return (
    <svg {...base} className={props.className}>
      <path d="M3 12a9 9 0 0115.4-6.4L21 8" />
      <path d="M21 3v5h-5" />
      <path d="M21 12a9 9 0 01-15.4 6.4L3 16" />
      <path d="M3 21v-5h5" />
    </svg>
  )
}
