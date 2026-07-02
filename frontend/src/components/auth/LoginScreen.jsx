import { useState } from 'react'
import { useMutation } from '@tanstack/react-query'
import { login as loginRequest } from '../../api/auth'
import { useAuth } from '../../auth/authContext'

/**
 * Minimal email/password login screen. No routing library is installed, so the
 * App root conditionally renders this vs the inbox based on auth state.
 *
 * This form owns its OWN error display (wrong credentials, validation) — that
 * is distinct from the global 401 handler in the client, which only fires a
 * logout for token problems on protected requests, not for a failed login.
 */
export default function LoginScreen() {
  const { applySession } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')

  const mutation = useMutation({
    mutationFn: () => loginRequest({ email: email.trim(), password }),
    onSuccess: (data) => {
      // data = { token, expires_in, user }
      applySession({ token: data.token, user: data.user })
    },
  })

  const errorMessage =
    mutation.error &&
    (mutation.error.code === 'invalid_credentials'
      ? 'That email or password is incorrect.'
      : mutation.error.code === 'validation_error'
        ? 'Enter a valid email and password.'
        : mutation.error.message || 'Could not sign in. Please try again.')

  function handleSubmit(e) {
    e.preventDefault()
    if (mutation.isPending) return
    mutation.mutate()
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 p-4">
      <div className="w-full max-w-sm rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h1 className="text-lg font-semibold text-gray-900">Sign in to Omni-Inbox</h1>
        <p className="mt-1 text-sm text-gray-500">
          Reply to WhatsApp, Messenger, Instagram, and Telegram from one place.
        </p>

        <form onSubmit={handleSubmit} className="mt-6 space-y-4" noValidate>
          <label className="block">
            <span className="text-sm font-medium text-gray-700">Email</span>
            <input
              type="email"
              autoComplete="username"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
              placeholder="owner@test.com"
            />
          </label>

          <label className="block">
            <span className="text-sm font-medium text-gray-700">Password</span>
            <input
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
              placeholder="••••••••"
            />
          </label>

          {errorMessage && (
            <p role="alert" className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
              {errorMessage}
            </p>
          )}

          <button
            type="submit"
            disabled={mutation.isPending || !email.trim() || !password}
            className="w-full rounded-md bg-emerald-600 py-2 text-sm font-semibold text-white transition-colors hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 disabled:cursor-not-allowed disabled:bg-gray-300"
          >
            {mutation.isPending ? 'Signing in…' : 'Sign in'}
          </button>
        </form>
      </div>
    </div>
  )
}
