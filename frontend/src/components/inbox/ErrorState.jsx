import { AlertIcon, RefreshIcon } from './icons'

/** Reusable centered error-state block with a retry action. */
export default function ErrorState({ message, onRetry, className = '' }) {
  return (
    <div
      role="alert"
      className={`flex flex-1 flex-col items-center justify-center gap-3 px-6 py-12 text-center ${className}`}
    >
      <span className="flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-500">
        <AlertIcon className="h-6 w-6" />
      </span>
      <p className="text-sm font-medium text-gray-900">Something went wrong</p>
      <p className="max-w-xs text-sm text-gray-500">{message}</p>
      {onRetry && (
        <button
          type="button"
          onClick={onRetry}
          className="mt-1 inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
        >
          <RefreshIcon className="h-4 w-4" />
          Try again
        </button>
      )}
    </div>
  )
}
