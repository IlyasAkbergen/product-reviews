import axios from 'axios'
import { createSlice, createAsyncThunk } from '@reduxjs/toolkit'
import type { PayloadAction } from '@reduxjs/toolkit'
import { loginApi, registerApi, logoutApi } from '../api/auth'
import type { AuthState } from '../types'

function decodeJwtPayload(token: string): Record<string, unknown> {
  try {
    const payload = token.split('.')[1]
    return JSON.parse(atob(payload.replace(/-/g, '+').replace(/_/g, '/')))
  } catch {
    return {}
  }
}

function userFromToken(token: string) {
  const payload = decodeJwtPayload(token)
  return {
    id: String(payload.id ?? ''),
    email: String(payload.email ?? payload.username ?? ''),
    name: String(payload.name ?? ''),
  }
}

function saveTokens(token: string, refreshToken: string) {
  localStorage.setItem('jwt_token', token)
  localStorage.setItem('jwt_refresh_token', refreshToken)
}

function clearTokens() {
  localStorage.removeItem('jwt_token')
  localStorage.removeItem('jwt_refresh_token')
}

const storedToken = localStorage.getItem('jwt_token')
const initialState: AuthState = {
  token: storedToken,
  user: storedToken ? userFromToken(storedToken) : null,
}

function extractErrorMessage(err: unknown, fallback: string): string {
  if (axios.isAxiosError<{ error?: string; detail?: string; message?: string }>(err)) {
    const d = err.response?.data
    const msg = d?.error ?? d?.detail ?? d?.message
    if (typeof msg === 'string' && msg.length > 0) return msg
  }
  return fallback
}

interface TokenPairPayload { token: string; refreshToken: string }

export const login = createAsyncThunk(
  'auth/login',
  async ({ email, password }: { email: string; password: string }, { rejectWithValue }) => {
    try {
      const { data } = await loginApi(email, password)
      return { token: data.token, refreshToken: data.refresh_token } satisfies TokenPairPayload
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Login failed.'))
    }
  },
)

export const register = createAsyncThunk(
  'auth/register',
  async ({ email, password, name }: { email: string; password: string; name: string }, { rejectWithValue }) => {
    try {
      await registerApi(email, password, name)
      const { data } = await loginApi(email, password)
      return { token: data.token, refreshToken: data.refresh_token } satisfies TokenPairPayload
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Registration failed.'))
    }
  },
)

export const logout = createAsyncThunk('auth/logout', async () => {
  const refreshToken = localStorage.getItem('jwt_refresh_token')
  if (refreshToken) {
    try { await logoutApi(refreshToken) } catch { /* best-effort */ }
  }
  clearTokens()
})

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    // Used by the Axios interceptor when refresh fails — clears state synchronously
    forceLogout(state) {
      state.token = null
      state.user = null
      clearTokens()
    },
    // Used by the Axios interceptor after a successful silent refresh
    setAccessToken(state, action: PayloadAction<string>) {
      state.token = action.payload
      state.user = userFromToken(action.payload)
      localStorage.setItem('jwt_token', action.payload)
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(login.fulfilled, (state, action: PayloadAction<TokenPairPayload>) => {
        state.token = action.payload.token
        state.user = userFromToken(action.payload.token)
        saveTokens(action.payload.token, action.payload.refreshToken)
      })
      .addCase(register.fulfilled, (state, action: PayloadAction<TokenPairPayload>) => {
        state.token = action.payload.token
        state.user = userFromToken(action.payload.token)
        saveTokens(action.payload.token, action.payload.refreshToken)
      })
      .addCase(logout.fulfilled, (state) => {
        state.token = null
        state.user = null
      })
  },
})

export const { forceLogout, setAccessToken } = authSlice.actions
export default authSlice.reducer
