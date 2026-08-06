import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import { Recycle } from 'lucide-react'
import { useAuth } from '@/contexts/AuthContext'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

export default function LoginPage() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const dashboard = await login(email, password)
      navigate(dashboard)
    } catch (err: unknown) {
      setError((err as { message?: string })?.message || 'Invalid email or password')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen gradient-bg flex items-center justify-center p-4">
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="w-full max-w-md">
        <div className="text-center mb-8">
          <Link to="/" className="inline-flex items-center gap-2">
            <div className="p-2 bg-emerald-600 rounded-xl"><Recycle className="w-6 h-6 text-white" /></div>
            <span className="font-bold text-xl">Smart<span className="text-emerald-600">Waste</span></span>
          </Link>
        </div>
        <Card>
          <CardHeader><CardTitle className="text-center text-2xl">Welcome Back</CardTitle></CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              {error && <div className="p-3 bg-red-50 dark:bg-red-900/20 text-red-600 text-sm rounded-xl">{error}</div>}
              <Input label="Email" id="email" type="email" value={email} onChange={e => setEmail(e.target.value)} placeholder="you@example.com" required />
              <Input label="Password" id="password" type="password" value={password} onChange={e => setPassword(e.target.value)} placeholder="Enter password" required />
              <div className="flex justify-end">
                <Link to="/forgot-password" className="text-sm text-emerald-600 hover:underline">Forgot password?</Link>
              </div>
              <Button type="submit" className="w-full" disabled={loading}>{loading ? 'Signing in...' : 'Sign In'}</Button>
            </form>
            <p className="mt-6 text-center text-sm text-slate-500">
              Don't have an account? <Link to="/register" className="text-emerald-600 font-medium hover:underline">Register</Link>
            </p>
            <div className="mt-4 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl text-xs text-slate-500 space-y-1">
              <p className="font-medium">Demo accounts:</p>
              <p>Admin: admin@smartwaste.gh / password</p>
              <p>Finance: finance@smartwaste.gh / password</p>
              <p>Collector: collector@smartwaste.gh / password</p>
            </div>
          </CardContent>
        </Card>
      </motion.div>
    </div>
  )
}
