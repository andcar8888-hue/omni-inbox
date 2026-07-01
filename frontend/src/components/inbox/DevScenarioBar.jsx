const SCENARIOS = ['success', 'loading', 'empty', 'error']

/**
 * Dev-only harness for exercising loading/empty/error states without a real
 * backend. Not part of the product UI — kept as a native <details> disclosure
 * so it's unobtrusive but always reachable and keyboard operable.
 */
export default function DevScenarioBar({
  conversationsScenario,
  onConversationsScenarioChange,
  messagesScenario,
  onMessagesScenarioChange,
}) {
  return (
    <details className="shrink-0 border-b border-amber-200 bg-amber-50 text-xs text-amber-900">
      <summary className="cursor-pointer select-none px-3 py-1.5 font-medium focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">
        Dev: simulate pane state
      </summary>
      <div className="flex flex-wrap gap-4 px-3 pb-2">
        <label className="flex items-center gap-1.5">
          Conversation list
          <select
            value={conversationsScenario}
            onChange={(e) => onConversationsScenarioChange(e.target.value)}
            className="rounded border border-amber-300 bg-white px-1.5 py-0.5 text-amber-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600"
          >
            {SCENARIOS.map((s) => (
              <option key={s} value={s}>
                {s}
              </option>
            ))}
          </select>
        </label>
        <label className="flex items-center gap-1.5">
          Active thread
          <select
            value={messagesScenario}
            onChange={(e) => onMessagesScenarioChange(e.target.value)}
            className="rounded border border-amber-300 bg-white px-1.5 py-0.5 text-amber-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600"
          >
            {SCENARIOS.map((s) => (
              <option key={s} value={s}>
                {s}
              </option>
            ))}
          </select>
        </label>
      </div>
    </details>
  )
}
