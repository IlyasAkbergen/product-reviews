import axios from 'axios'
import type { AxiosError, InternalAxiosRequestConfig } from 'axios'

const apiUrl = import.meta.env.VITE_API_URL

export const client = axios.create({
  baseURL: apiUrl,
  headers: { 'Content-Type': 'application/json' },
})

// Attach access token on every request
client.interceptors.request.use((config) => {
  const token = localStorage.getItem('jwt_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// On 401: try silent refresh, retry once, then force-logout
let isRefreshing = false
let pendingQueue: Array<{ resolve: (token: string) => void; reject: (err: unknown) => void }> = []

function processQueue(err: unknown, token: string | null) {
  pendingQueue.forEach((p) => (token ? p.resolve(token) : p.reject(err)))
  pendingQueue = []
}

interface RetryConfig extends InternalAxiosRequestConfig {
  _retry?: boolean
}

client.interceptors.response.use(
  (res) => res,
  async (error: AxiosError) => {
    const originalRequest = error.config as RetryConfig | undefined

    if (error.response?.status !== 401 || originalRequest?._retry || !originalRequest) {
      return Promise.reject(error)
    }

    // Don't retry the refresh endpoint itself
    if (originalRequest.url?.includes('/api/auth/refresh')) {
      return Promise.reject(error)
    }

    if (isRefreshing) {
      return new Promise((resolve, reject) => {
        pendingQueue.push({
          resolve: (token) => {
            originalRequest.headers.Authorization = `Bearer ${token}`
            resolve(client(originalRequest))
          },
          reject,
        })
      })
    }

    originalRequest._retry = true
    isRefreshing = true

    const refreshToken = localStorage.getItem('jwt_refresh_token')
    if (!refreshToken) {
      isRefreshing = false
      // Lazy import avoids circular dependency
      const { forceLogout } = await import('../store/authSlice')
      const { store } = await import('../store')
      store.dispatch(forceLogout())
      return Promise.reject(error)
    }

    try {
      // Call refresh directly (not through `client`) to avoid interceptor loop
      const { data } = await axios.post<{ token: string; refresh_token: string }>(
        `${apiUrl}/api/auth/refresh`,
        { refresh_token: refreshToken },
        { headers: { 'Content-Type': 'application/json' } },
      )

      localStorage.setItem('jwt_token', data.token)
      localStorage.setItem('jwt_refresh_token', data.refresh_token)

      const { setAccessToken } = await import('../store/authSlice')
      const { store } = await import('../store')
      store.dispatch(setAccessToken(data.token))

      processQueue(null, data.token)
      originalRequest.headers.Authorization = `Bearer ${data.token}`
      return client(originalRequest)
    } catch (refreshError) {
      processQueue(refreshError, null)
      const { forceLogout } = await import('../store/authSlice')
      const { store } = await import('../store')
      store.dispatch(forceLogout())
      return Promise.reject(refreshError)
    } finally {
      isRefreshing = false
    }
  },
)
