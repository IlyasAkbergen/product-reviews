import { client } from './client'

export interface LoginResponse {
  token: string
}

export interface RegisterResponse {
  id: string
  email: string
  name: string
}

export const loginApi = (email: string, password: string) =>
  client.post<LoginResponse>('/api/auth/login', { email, password })

export const registerApi = (email: string, password: string, name: string) =>
  client.post<RegisterResponse>('/api/auth/register', { email, password, name })
