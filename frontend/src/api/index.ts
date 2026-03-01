import axios from 'axios'
import { useAuthStore } from '../stores/auth'

const api = axios.create({
  baseURL: '',
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor: attach JWT
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor: handle 401
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const auth = useAuthStore()
      auth.logout()
      window.location.hash = '#/login'
    }
    return Promise.reject(error)
  },
)

// Auth
export const authApi = {
  login: (username: string, password: string) =>
    api.post('/api/auth/login', { username, password }),
  logout: () => api.post('/api/auth/logout'),
  changePassword: (old_password: string, new_password: string) =>
    api.post('/api/auth/password', { old_password, new_password }),
}

// Records
export const recordsApi = {
  list: (params: { page?: number; limit?: number; search?: string } = {}) =>
    api.get('/api/records', { params }),
  get: (id: number) => api.get(`/api/records/${id}`),
  delete: (id: number) => api.delete(`/api/records/${id}`),
  clear: () => api.delete('/api/records'),
}

// Scripts
export const scriptsApi = {
  list: (type: string) => api.get('/api/scripts', { params: { type } }),
  get: (id: number) => api.get(`/api/scripts/${id}`),
  create: (data: { name: string; description: string; content: string; type: string }) =>
    api.post('/api/scripts', data),
  update: (id: number, data: { name: string; description: string; content: string }) =>
    api.put(`/api/scripts/${id}`, data),
  delete: (id: number) => api.delete(`/api/scripts/${id}`),
  clear: (type: string) => api.delete('/api/scripts', { params: { type } }),
}

export default api
