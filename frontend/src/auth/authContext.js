import { createContext, useContext } from 'react'

/**
 * Auth context object + the `useAuth` hook, kept in a plain module (no JSX)
 * separate from the provider component so the provider file only exports a
 * component — satisfies the `react/only-export-components` lint rule.
 */
export const AuthContext = createContext(null)

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within an AuthProvider')
  return ctx
}
