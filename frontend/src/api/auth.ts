import { client } from './client'

export interface TokenPair {
  token: string
  refresh_token: string
}

export interface RegisterResponse {
  id: string
  email: string
  name: string
}

export const loginApi = (email: string, password: string) =>
  client.post<TokenPair>('/api/auth/login', { email, password })

export const registerApi = (email: string, password: string, name: string) =>
  client.post<RegisterResponse>('/api/auth/register', { email, password, name })

export const refreshApi = (refreshToken: string) =>
  client.post<TokenPair>('/api/auth/refresh', { refresh_token: refreshToken })

export const logoutApi = (refreshToken: string) =>
  client.post('/api/auth/logout', { refresh_token: refreshToken })
