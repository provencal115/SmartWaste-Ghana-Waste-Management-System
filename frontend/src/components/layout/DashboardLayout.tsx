import { Link, useLocation, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import {
  Recycle, LayoutDashboard, Calendar, CreditCard, MessageSquare, Bell,
  MapPin, ClipboardList, Package, Users, Settings, Truck, BarChart3,
  DollarSign, FileText, LogOut, Sun, Moon, Menu, X, ScanLine, AlertTriangle
} from 'lucide-react'
import { useAuth } from '@/contexts/AuthContext'
import { useTheme } from '@/contexts/ThemeContext'
import { Button } from '@/components/ui/button'
import { useState } from 'react'

const roleNav: Record<string, { label: string; path: string; icon: React.ElementType }[]> = {
  resident: [
    { label: 'Dashboard', path: '/dashboard/resident', icon: LayoutDashboard },
    { label: 'Schedule Pickup', path: '/dashboard/resident/schedule', icon: Calendar },
    { label: 'Payments', path: '/dashboard/resident/payments', icon: CreditCard },
    { label: 'Feedback', path: '/dashboard/resident/feedback', icon: MessageSquare },
    { label: 'Notifications', path: '/dashboard/resident/notifications', icon: Bell },
  ],
  collector: [
    { label: 'Dashboard', path: '/dashboard/collector', icon: LayoutDashboard },
    { label: 'Today\'s Route', path: '/dashboard/collector/routes', icon: MapPin },
    { label: 'Scan Bin', path: '/dashboard/collector/scan', icon: ScanLine },
    { label: 'Reports', path: '/dashboard/collector/reports', icon: AlertTriangle },
    { label: 'Schedule', path: '/dashboard/collector/schedule', icon: ClipboardList },
  ],
  inventory_manager: [
    { label: 'Dashboard', path: '/dashboard/inventory', icon: LayoutDashboard },
    { label: 'Bins', path: '/dashboard/inventory/bins', icon: Package },
    { label: 'Assignments', path: '/dashboard/inventory/assignments', icon: ClipboardList },
    { label: 'Movements', path: '/dashboard/inventory/movements', icon: FileText },
    { label: 'Reports', path: '/dashboard/inventory/reports', icon: BarChart3 },
  ],
  administrator: [
    { label: 'Dashboard', path: '/dashboard/admin', icon: LayoutDashboard },
    { label: 'Users', path: '/dashboard/admin/users', icon: Users },
    { label: 'Zones & Routes', path: '/dashboard/admin/routes', icon: MapPin },
    { label: 'Trucks', path: '/dashboard/admin/trucks', icon: Truck },
    { label: 'Complaints', path: '/dashboard/admin/complaints', icon: MessageSquare },
    { label: 'Reports', path: '/dashboard/admin/reports', icon: BarChart3 },
    { label: 'Smart Settings', path: '/dashboard/admin/settings', icon: Settings },
    { label: 'Audit Logs', path: '/dashboard/admin/logs', icon: FileText },
  ],
  finance_manager: [
    { label: 'Dashboard', path: '/dashboard/finance', icon: LayoutDashboard },
    { label: 'Payments', path: '/dashboard/finance/payments', icon: CreditCard },
    { label: 'Pricing', path: '/dashboard/finance/pricing', icon: DollarSign },
    { label: 'Reports', path: '/dashboard/finance/reports', icon: BarChart3 },
    { label: 'Invoices', path: '/dashboard/finance/invoices', icon: FileText },
  ],
}

export function DashboardLayout({ children }: { children: React.ReactNode }) {
  const { user, logout } = useAuth()
  const { theme, toggle } = useTheme()
  const location = useLocation()
  const navigate = useNavigate()
  const [sidebarOpen, setSidebarOpen] = useState(false)

  const navItems = roleNav[user?.role || ''] || []

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  return (
    <div className="min-h-screen gradient-bg flex">
      <aside className={`fixed inset-y-0 left-0 z-50 w-64 glass border-r border-white/10 transform transition-transform lg:translate-x-0 ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}`}>
        <div className="flex flex-col h-full">
          <div className="p-6 flex items-center gap-2">
            <div className="p-2 bg-emerald-600 rounded-xl"><Recycle className="w-5 h-5 text-white" /></div>
            <span className="font-bold">Smart<span className="text-emerald-600">Waste</span></span>
          </div>
          <nav className="flex-1 px-4 space-y-1 overflow-y-auto">
            {navItems.map(item => {
              const active = location.pathname === item.path
              return (
                <Link key={item.path} to={item.path} onClick={() => setSidebarOpen(false)}
                  className={`flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all ${active ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'}`}>
                  <item.icon className="w-5 h-5" />{item.label}
                </Link>
              )
            })}
          </nav>
          <div className="p-4 border-t border-slate-200 dark:border-slate-700">
            <div className="flex items-center gap-3 mb-3 px-2">
              <div className="w-9 h-9 rounded-full bg-emerald-600 flex items-center justify-center text-white text-sm font-bold">
                {user?.first_name?.[0]}{user?.last_name?.[0]}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium truncate">{user?.first_name} {user?.last_name}</p>
                <p className="text-xs text-slate-500 capitalize truncate">{user?.role?.replace(/_/g, ' ')}</p>
              </div>
            </div>
            <Button variant="ghost" size="sm" className="w-full justify-start" onClick={handleLogout}>
              <LogOut className="w-4 h-4 mr-2" /> Logout
            </Button>
          </div>
        </div>
      </aside>

      <div className="flex-1 lg:ml-64">
        <header className="sticky top-0 z-40 glass border-b border-white/10 px-4 h-16 flex items-center justify-between">
          <button className="lg:hidden p-2" onClick={() => setSidebarOpen(!sidebarOpen)}>
            {sidebarOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
          </button>
          <div />
          <div className="flex items-center gap-3">
            <button onClick={toggle} className="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
              {theme === 'light' ? <Moon className="w-5 h-5" /> : <Sun className="w-5 h-5" />}
            </button>
            <Link to={`/dashboard/${user?.role?.replace('_manager', '')}/notifications`} className="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 relative">
              <Bell className="w-5 h-5" />
            </Link>
          </div>
        </header>
        <main className="p-4 md:p-6 lg:p-8">
          <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.3 }}>
            {children}
          </motion.div>
        </main>
      </div>
      {sidebarOpen && <div className="fixed inset-0 bg-black/50 z-40 lg:hidden" onClick={() => setSidebarOpen(false)} />}
    </div>
  )
}
