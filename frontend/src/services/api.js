import axios from 'axios'

const configuredApiBaseUrl = import.meta.env.VITE_API_BASE_URL?.trim()
const apiBaseUrl =
  configuredApiBaseUrl || (import.meta.env.DEV ? '/api' : undefined)

if (!apiBaseUrl) {
  throw new Error(
    'VITE_API_BASE_URL must be set to the deployed backend URL (including /api) in production.',
  )
}

const apiClient = axios.create({
  baseURL: apiBaseUrl,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
})

// ភ្ជាប់ Bearer Token ស្វ័យប្រវត្តពេលសិស្ស Login
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status
    const requestUrl = error.config?.url ?? ''

    if (
      status === 401 &&
      !requestUrl.endsWith('/login') &&
      !requestUrl.endsWith('/register')
    ) {
      localStorage.removeItem('auth_token')
      window.dispatchEvent(new Event('auth:unauthorized'))
    }

    return Promise.reject(error)
  },
)

export default apiClient
