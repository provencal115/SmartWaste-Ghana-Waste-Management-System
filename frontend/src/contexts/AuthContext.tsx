import { createContext, useContext, useState, useEffect, useCallback, type ReactNode } from 'react'
import { authApi, setCsrfToken } from '@/lib/api'
import { ROLE_DASHBOARDS } from '@/lib/utils'
import type { User } from '@/types'

interface AuthContextType {
  user: User | null
  loading: boolean
  login: (email: string, password: string) => Promise<string>
  logout: () => Promise<void>
  refreshUser: () => Promise<void>
}

const AuthContext = createContext<AuthContextType | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(true)

  const refreshUser = useCallback(async () => {
    try {
      const res = await authApi.me()
      const u = res.data.user
      setUser({ ...u, role: u.role_name || u.role })
      if (res.data.csrf_token) setCsrfToken(res.data.csrf_token)
    } catch {
      setUser(null)
    }
  }, [])

  useEffect(() => {
    refreshUser().finally(() => setLoading(false))
  }, [refreshUser])

  const login = async (email: string, password: string) => {
    const res = await authApi.login(email, password)
    const u = res.data.user
    setUser(u)
    if (res.data.csrf_token) setCsrfToken(res.data.csrf_token)
    return ROLE_DASHBOARDS[u.role] || '/'
  }

  const logout = async () => {
    await authApi.logout()
    setUser(null)
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, refreshUser }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
