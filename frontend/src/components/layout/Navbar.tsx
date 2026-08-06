import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { Recycle, Menu, X, Sun, Moon } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useTheme } from '@/contexts/ThemeContext'
import { useAuth } from '@/contexts/AuthContext'
import { ROLE_DASHBOARDS } from '@/lib/utils'
import { useState } from 'react'

export function Navbar() {
  const { theme, toggle } = useTheme()
  const { user } = useAuth()
  const [mobileOpen, setMobileOpen] = useState(false)

  return (
    <motion.nav
      initial={{ y: -20, opacity: 0 }}
      animate={{ y: 0, opacity: 1 }}
      className="fixed top-0 left-0 right-0 z-50 glass border-b border-white/10"
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">
          <Link to="/" className="flex items-center gap-2">
            <div className="p-2 bg-emerald-600 rounded-xl">
              <Recycle className="w-5 h-5 text-white" />
            </div>
            <span className="font-bold text-lg hidden sm:block">Smart<span className="text-emerald-600">Waste</span></span>
          </Link>

          <div className="hidden md:flex items-center gap-8">
            <a href="#features" className="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-emerald-600 transition-colors">Features</a>
            <a href="#stats" className="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-emerald-600 transition-colors">Impact</a>
            <a href="#pricing" className="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-emerald-600 transition-colors">Pricing</a>
          </div>

          <div className="flex items-center gap-3">
            <button onClick={toggle} className="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
              {theme === 'light' ? <Moon className="w-5 h-5" /> : <Sun className="w-5 h-5" />}
            </button>
            {user ? (
              <Link to={ROLE_DASHBOARDS[user.role] || '/'}>
                <Button size="sm">Dashboard</Button>
              </Link>
            ) : (
              <>
                <Link to="/login"><Button variant="ghost" size="sm">Login</Button></Link>
                <Link to="/register"><Button size="sm">Get Started</Button></Link>
              </>
            )}
            <button className="md:hidden p-2" onClick={() => setMobileOpen(!mobileOpen)}>
              {mobileOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
            </button>
          </div>
        </div>
      </div>
      {mobileOpen && (
        <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="md:hidden border-t border-slate-200 dark:border-slate-700 p-4 space-y-3">
          <a href="#features" className="block text-sm font-medium py-2">Features</a>
          <a href="#stats" className="block text-sm font-medium py-2">Impact</a>
          <a href="#pricing" className="block text-sm font-medium py-2">Pricing</a>
        </motion.div>
      )}
    </motion.nav>
  )
}
