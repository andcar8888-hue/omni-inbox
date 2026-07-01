import { useRef, useState } from 'react'
import { PaperclipIcon, SendIcon } from './icons'

/** Message composer: text input + attach (stubbed) + send. Enter sends, Shift+Enter newlines. */
export default function MessageComposer({ onSend, disabled = false }) {
  const [value, setValue] = useState('')
  const textareaRef = useRef(null)

  function handleSend() {
    const trimmed = value.trim()
    if (!trimmed || disabled) return
    onSend(trimmed)
    setValue('')
    textareaRef.current?.focus()
  }

  function handleKeyDown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault()
      handleSend()
    }
  }

  return (
    <div className="shrink-0 border-t border-gray-200 bg-white p-3">
      <div className="flex items-end gap-2">
        <button
          type="button"
          disabled
          aria-label="Attach file"
          title="Attachments coming soon"
          className="mb-1 shrink-0 rounded-md p-2 text-gray-400 disabled:cursor-not-allowed disabled:opacity-50"
        >
          <PaperclipIcon className="h-5 w-5" />
        </button>

        <label className="min-w-0 flex-1">
          <span className="sr-only">Message</span>
          <textarea
            ref={textareaRef}
            rows={1}
            value={value}
            disabled={disabled}
            onChange={(e) => setValue(e.target.value)}
            onKeyDown={handleKeyDown}
            placeholder={disabled ? 'Select a conversation to reply' : 'Type a message'}
            className="max-h-32 w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 disabled:cursor-not-allowed disabled:bg-gray-50"
          />
        </label>

        <button
          type="button"
          onClick={handleSend}
          disabled={disabled || !value.trim()}
          aria-label="Send message"
          className="mb-1 inline-flex shrink-0 items-center justify-center rounded-md bg-emerald-600 p-2.5 text-white transition-colors hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 disabled:cursor-not-allowed disabled:bg-gray-300"
        >
          <SendIcon className="h-4 w-4" />
        </button>
      </div>
    </div>
  )
}
