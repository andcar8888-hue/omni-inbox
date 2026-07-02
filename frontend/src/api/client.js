// Real HTTP client for the Omni-Inbox backend (CodeIgniter 4, /api/v1).
//
// Responsibilities:
//   - resolve the API base URL from a Vite env var (VITE_API_BASE_URL),
//     defaulting to the local `php spark serve` origin,
//   - read the stored JWT and attach `Authorization: Bearer <token>`,
//   - unwrap the backend's `{ data }` success envelope,
//   - turn the backend's `{ error: { code, message, details } }` shape into a
//     typed ApiError,
//   - notify a registered handler on any 401 so the auth layer can log out.
//
// API request functions live in the sibling modules (auth.js, conversations.js)
// and call `apiRequest` — components never call fetch() directly.

const BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api/v1'

const TOKEN_STORAGE_KEY = 'omni_inbox_token'

/** A structured error thrown for any non-2xx API response (or network failure). */
export class ApiError extends Error {
  constructor(message, { status, code, details } = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status ?? null
    this.code = code ?? null
    this.details = details ?? null
  }
}

// --- Token storage (localStorage for this phase) ---------------------------

export function getToken() {
  try {
    return localStorage.getItem(TOKEN_STORAGE_KEY)
  } catch {
    return null
  }
}

export function setToken(token) {
  try {
    if (token) localStorage.setItem(TOKEN_STORAGE_KEY, token)
    else localStorage.removeItem(TOKEN_STORAGE_KEY)
  } catch {
    // Ignore storage failures (private mode, quota); the in-memory auth state
    // still drives the UI for the session.
  }
}

export function clearToken() {
  setToken(null)
}

// --- Global 401 handler ----------------------------------------------------
// The auth layer registers a callback here so an expired/invalid token on ANY
// request forces a logout, without every caller having to special-case it.

let onUnauthorized = null

export function setUnauthorizedHandler(handler) {
  onUnauthorized = handler
}

// --- Core request ----------------------------------------------------------

/**
 * Perform a JSON API request.
 *
 * @param {string} path   Path relative to the API base, e.g. '/conversations'.
 * @param {object} [opts]
 * @param {string} [opts.method]  HTTP method (default 'GET').
 * @param {object} [opts.body]    JSON-serializable request body.
 * @param {boolean} [opts.auth]   Attach the bearer token (default true).
 * @returns {Promise<any>} The unwrapped `data` payload.
 * @throws {ApiError}
 */
export async function apiRequest(path, { method = 'GET', body, auth = true } = {}) {
  const headers = { Accept: 'application/json' }
  if (body !== undefined) headers['Content-Type'] = 'application/json'

  if (auth) {
    const token = getToken()
    if (token) headers.Authorization = `Bearer ${token}`
  }

  let response
  try {
    response = await fetch(`${BASE_URL}${path}`, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    })
  } catch {
    throw new ApiError(
      'Could not reach the inbox service. Check your connection and try again.',
      { status: null, code: 'network_error' },
    )
  }

  // Parse the JSON envelope if there is one (some responses may be empty).
  let payload = null
  const text = await response.text()
  if (text) {
    try {
      payload = JSON.parse(text)
    } catch {
      payload = null
    }
  }

  if (!response.ok) {
    const err = payload?.error ?? {}
    if (response.status === 401) {
      // Fire the global logout hook. The login endpoint itself passes auth:false
      // and surfaces `invalid_credentials`, so wrong-password 401s do NOT reach
      // here as an auth-token failure — they still throw an ApiError the login
      // form can render, but we only trigger the global logout for token issues.
      if (err.code !== 'invalid_credentials' && onUnauthorized) {
        onUnauthorized()
      }
    }
    throw new ApiError(err.message || `Request failed (${response.status}).`, {
      status: response.status,
      code: err.code,
      details: err.details,
    })
  }

  return payload?.data ?? null
}
