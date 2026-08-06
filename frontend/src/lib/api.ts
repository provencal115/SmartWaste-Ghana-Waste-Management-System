import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: { 'Content-Type': 'application/json' },
})

let csrfToken = ''

api.interceptors.request.use((config) => {
  if (csrfToken && ['post', 'put', 'delete'].includes(config.method || '')) {
    config.headers['X-CSRF-Token'] = csrfToken
  }
  return config
})

api.interceptors.response.use(
  (res) => {
    if (res.data?.csrf_token) csrfToken = res.data.csrf_token
    return res
  },
  (err) => Promise.reject(err.response?.data || err)
)

export const setCsrfToken = (token: string) => { csrfToken = token }

export const authApi = {
  login: (email: string, password: string) => api.post('/auth.php?action=login', { email, password }),
  register: (data: Record<string, unknown>) => api.post('/auth.php?action=register', data),
  confirmRegistration: (userId: number) => api.post('/auth.php?action=confirm-registration', { user_id: userId }),
  logout: () => api.post('/auth.php?action=logout'),
  me: () => api.get('/auth.php?action=me'),
  forgotPassword: (email: string) => api.post('/auth.php?action=forgot-password', { email }),
  resetPassword: (token: string, password: string) => api.post('/auth.php?action=reset-password', { token, password }),
}

export const residentApi = {
  dashboard: () => api.get('/residents.php?action=dashboard'),
  schedulePickup: (data: Record<string, unknown>) => api.post('/residents.php?action=schedule-pickup', data),
  makePayment: (data: Record<string, unknown>) => api.post('/residents.php?action=make-payment', data),
  submitComplaint: (data: Record<string, unknown>) => api.post('/residents.php?action=submit-complaint', data),
  complaints: () => api.get('/residents.php?action=complaints'),
  pricing: () => api.get('/residents.php?action=pricing'),
  zones: () => api.get('/residents.php?action=zones'),
}

export const collectorApi = {
  dashboard: () => api.get('/collectors.php?action=dashboard'),
  updatePickup: (data: Record<string, unknown>) => api.post('/collectors.php?action=update-pickup', data),
  scanBin: (code: string) => api.post('/collectors.php?action=scan-bin', { code }),
  submitReport: (data: Record<string, unknown>) => api.post('/collectors.php?action=submit-report', data),
  syncOffline: (actions: unknown[]) => api.post('/collectors.php?action=sync-offline', { actions }),
}

export const inventoryApi = {
  dashboard: () => api.get('/inventory.php?action=dashboard'),
  bins: (params?: Record<string, string>) => api.get('/inventory.php?action=bins', { params }),
  addBin: (data: Record<string, unknown>) => api.post('/inventory.php?action=bins', data),
  assignBin: (data: Record<string, unknown>) => api.post('/inventory.php?action=assign-bin', data),
  recordRepair: (data: Record<string, unknown>) => api.post('/inventory.php?action=record-repair', data),
  report: () => api.get('/inventory.php?action=report'),
}

export const adminApi = {
  dashboard: () => api.get('/admin.php?action=dashboard'),
  users: (role?: string) => api.get('/admin.php?action=users', { params: role ? { role } : {} }),
  updateUser: (data: Record<string, unknown>) => api.put('/admin.php?action=users', data),
  zones: () => api.get('/admin.php?action=zones'),
  addZone: (data: Record<string, unknown>) => api.post('/admin.php?action=zones', data),
  routes: () => api.get('/admin.php?action=routes'),
  addRoute: (data: Record<string, unknown>) => api.post('/admin.php?action=routes', data),
  trucks: () => api.get('/admin.php?action=trucks'),
  addTruck: (data: Record<string, unknown>) => api.post('/admin.php?action=trucks', data),
  reschedule: (data: Record<string, unknown>) => api.post('/admin.php?action=reschedule', data),
  complaints: () => api.get('/admin.php?action=complaints'),
  updateComplaint: (data: Record<string, unknown>) => api.put('/admin.php?action=complaints', data),
  logs: () => api.get('/admin.php?action=logs'),
  smartSettings: () => api.get('/admin.php?action=smart-settings'),
  updateSmartSettings: (key: string, value: unknown) => api.put('/admin.php?action=smart-settings', { key, value }),
  pricing: () => api.get('/admin.php?action=pricing'),
}

export const financeApi = {
  dashboard: () => api.get('/finance.php?action=dashboard'),
  payments: (status?: string) => api.get('/finance.php?action=payments', { params: status ? { status } : {} }),
  verifyCash: (paymentId: number) => api.post('/finance.php?action=verify-cash', { payment_id: paymentId }),
  refund: (paymentId: number, reason: string) => api.post('/finance.php?action=refund', { payment_id: paymentId, reason }),
  pricing: () => api.get('/finance.php?action=pricing'),
  updatePricing: (data: Record<string, unknown>) => api.put('/finance.php?action=pricing', data),
  reports: (type: string) => api.get('/finance.php?action=reports', { params: { type } }),
}

export const notificationApi = {
  list: () => api.get('/notifications.php?action=list'),
  markRead: (id?: number) => api.post('/notifications.php?action=mark-read', id ? { id } : {}),
}

export const reportsApi = {
  generate: (type: string) => api.get('/reports.php', { params: { type } }),
}

export default api
