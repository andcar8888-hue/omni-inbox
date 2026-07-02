// Auth API functions. Components/hooks call these; never fetch() directly.

import { apiRequest } from './client'

/**
 * Exchange credentials for a JWT.
 * @param {{ email: string, password: string }} credentials
 * @returns {Promise<{ token: string, expires_in: number, user: object }>}
 * @throws {ApiError} 422 validation_error | 401 invalid_credentials
 */
export function login({ email, password }) {
  return apiRequest('/auth/login', {
    method: 'POST',
    body: { email, password },
    auth: false,
  })
}
