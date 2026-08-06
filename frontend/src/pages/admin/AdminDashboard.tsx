import { useEffect, useState } from 'react'
import { Users, Truck, Calendar, DollarSign, AlertCircle, TrendingUp } from 'lucide-react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { DashboardWidget } from '@/components/common/AnimatedCounter'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { adminApi } from '@/lib/api'
import { formatCurrency } from '@/lib/utils'
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar, PieChart, Pie, Cell } from 'recharts'

const COLORS = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444']

export default function AdminDashboard() {
  const [data, setData] = useState<{
    stats: Record<string, number>
    dailyCollections: { date: string; count: number }[]
    revenueTrends: { date: string; revenue: number }[]
    binAllocation: { status: string; count: number }[]
    paymentStats: { status: string; count: number; total: number }[]
    customerGrowth: { date: string; count: number }[]
  } | null>(null)

  useEffect(() => { adminApi.dashboard().then(r => setData(r.data.data)).catch(() => {}) }, [])

  if (!data) return <DashboardLayout><div className="animate-pulse space-y-4">{[1,2,3,4].map(i => <div key={i} className="h-32 bg-slate-200 dark:bg-slate-800 rounded-2xl" />)}</div></DashboardLayout>

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Admin Dashboard</h1>
        <div className="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
          <DashboardWidget title="Active Users" value={data.stats.active_users} icon={Users} color="bg-blue-600" />
          <DashboardWidget title="Today's Pickups" value={data.stats.today_pickups} icon={Calendar} color="bg-emerald-600" delay={0.05} />
          <DashboardWidget title="Active Collections" value={data.stats.active_collections} icon={Truck} color="bg-purple-600" delay={0.1} />
          <DashboardWidget title="Missed (7d)" value={data.stats.missed_collections} icon={AlertCircle} color="bg-red-600" delay={0.15} />
          <DashboardWidget title="Total Revenue" value={formatCurrency(data.stats.total_revenue)} icon={DollarSign} color="bg-emerald-600" delay={0.2} />
          <DashboardWidget title="Outstanding" value={formatCurrency(data.stats.outstanding)} icon={TrendingUp} color="bg-amber-600" delay={0.25} />
        </div>

        <div className="grid lg:grid-cols-2 gap-6">
          <Card>
            <CardHeader><CardTitle>Daily Collections (30 days)</CardTitle></CardHeader>
            <CardContent><ResponsiveContainer width="100%" height={250}>
              <AreaChart data={data.dailyCollections}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                <XAxis dataKey="date" tick={{ fontSize: 11 }} /><YAxis tick={{ fontSize: 11 }} />
                <Tooltip /><Area type="monotone" dataKey="count" stroke="#10b981" fill="#10b981" fillOpacity={0.2} />
              </AreaChart>
            </ResponsiveContainer></CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle>Revenue Trends</CardTitle></CardHeader>
            <CardContent><ResponsiveContainer width="100%" height={250}>
              <BarChart data={data.revenueTrends}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                <XAxis dataKey="date" tick={{ fontSize: 11 }} /><YAxis tick={{ fontSize: 11 }} />
                <Tooltip /><Bar dataKey="revenue" fill="#3b82f6" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer></CardContent>
          </Card>
        </div>

        <div className="grid lg:grid-cols-3 gap-6">
          <Card>
            <CardHeader><CardTitle>Bin Allocation</CardTitle></CardHeader>
            <CardContent><ResponsiveContainer width="100%" height={200}>
              <PieChart><Pie data={data.binAllocation} dataKey="count" nameKey="status" cx="50%" cy="50%" outerRadius={70} label>
                {data.binAllocation.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
              </Pie><Tooltip /></PieChart>
            </ResponsiveContainer></CardContent>
          </Card>
          <Card className="lg:col-span-2">
            <CardHeader><CardTitle>Customer Growth</CardTitle></CardHeader>
            <CardContent><ResponsiveContainer width="100%" height={200}>
              <AreaChart data={data.customerGrowth}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                <XAxis dataKey="date" tick={{ fontSize: 11 }} /><YAxis tick={{ fontSize: 11 }} />
                <Tooltip /><Area type="monotone" dataKey="count" stroke="#8b5cf6" fill="#8b5cf6" fillOpacity={0.2} />
              </AreaChart>
            </ResponsiveContainer></CardContent>
          </Card>
        </div>
      </div>
    </DashboardLayout>
  )
}
