import axios from 'axios'
import { createSlice, createAsyncThunk } from '@reduxjs/toolkit'
import type { PayloadAction } from '@reduxjs/toolkit'
import { loginApi, registerApi } from '../api/auth'
import type { AuthState } from '../types'

// Decode a JWT payload without a library
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
    // LexikJWT always sets `username`; our listener also adds `email`
    email: String(payload.email ?? payload.username ?? ''),
    name: String(payload.name ?? ''),
  }
}

const storedToken = localStorage.getItem('jwt_token')
const initialState: AuthState = {
  token: storedToken,
  user: storedToken ? userFromToken(storedToken) : null,
}

function extractErrorMessage(err: unknown, fallback: string): string {
  if (axios.isAxiosError<{ error?: string; detail?: string }>(err)) {
    const msg = err.response?.data?.error ?? err.response?.data?.detail
    if (typeof msg === 'string' && msg.length > 0) return msg
  }
  return fallback
}

export const login = createAsyncThunk(
  'auth/login',
  async ({ email, password }: { email: string; password: string }, { rejectWithValue }) => {
    try {
      const { data } = await loginApi(email, password)
      return data.token
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
      // Auto-login after register
      const { data } = await loginApi(email, password)
      return data.token
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Registration failed.'))
    }
  },
)

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    logout(state) {
      state.token = null
      state.user = null
      localStorage.removeItem('jwt_token')
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(login.fulfilled, (state, action: PayloadAction<string>) => {
        state.token = action.payload
        state.user = userFromToken(action.payload)
        localStorage.setItem('jwt_token', action.payload)
      })
      .addCase(register.fulfilled, (state, action: PayloadAction<string>) => {
        state.token = action.payload
        state.user = userFromToken(action.payload)
        localStorage.setItem('jwt_token', action.payload)
      })
  },
})

export const { logout } = authSlice.actions
export default authSlice.reducer
