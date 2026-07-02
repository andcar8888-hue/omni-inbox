import { useCallback, useEffect, useMemo, useState } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import {
  clearToken,
  getToken,
  setToken,
  setUnauthorizedHandler,
} from '../api/client'
import { AuthContext } from './authContext'

const USER_STORAGE_KEY = 'omni_inbox_user'

function readStoredUser() {
  try {
    const raw = localStorage.getItem(USER_STORAGE_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

function persistUser(user) {
  try {
    if (user) localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user))
    else localStorage.removeItem(USER_STORAGE_KEY)
  } catch {
    // ignore storage failures
  }
}

/**
 * Holds the authenticated session (JWT + user) and exposes login/logout.
 *
 * A token in localStorage on load counts as "authenticated" for gating the UI;
 * if it is actually expired/invalid, the first protected request returns 401,
 * which the global unauthorized handler below converts into a logout — so the
 * user lands back on the login screen without a manual step.
 */
export function AuthProvider({ children }) {
  const queryClient = useQueryClient()
  const [token, setTokenState] = useState(() => getToken())
  const [user, setUser] = useState(() => readStoredUser())

  const logout = useCallback(() => {
    clearToken()
    persistUser(null)
    setTokenState(null)
    setUser(null)
    // Drop any cached protected data so the next login starts clean and no
    // previous tenant's conversations flash on screen.
    queryClient.clear()
  }, [queryClient])

  const applySession = useCallback(({ token: nextToken, user: nextUser }) => {
    setToken(nextToken)
    persistUser(nextUser)
    setTokenState(nextToken)
    setUser(nextUser)
  }, [])

  // Wire the client's global 401 hook to this provider's logout. Any request
  // that comes back 401 with a token problem resets app state to logged-out.
  useEffect(() => {
    setUnauthorizedHandler(() => logout())
    return () => setUnauthorizedHandler(null)
  }, [logout])

  const value = useMemo(
    () => ({
      isAuthenticated: Boolean(token),
      user,
      applySession,
      logout,
    }),
    [token, user, applySession, logout],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
