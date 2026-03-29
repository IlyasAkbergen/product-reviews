import axios from 'axios'

const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8080'

export const client = axios.create({
  baseURL: apiUrl,
  headers: { 'Content-Type': 'application/json' },
})

// Attach JWT from localStorage on every request
client.interceptors.request.use((config) => {
  const token = localStorage.getItem('jwt_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})
