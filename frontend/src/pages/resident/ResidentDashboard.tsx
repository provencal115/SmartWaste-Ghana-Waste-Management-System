import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Package, Calendar, CreditCard, Bell } from 'lucide-react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { DashboardWidget } from '@/components/common/AnimatedCounter'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { GarbageBin } from '@/components/landing/GarbageBin'
import { residentApi } from '@/lib/api'
import { formatCurrency, formatDate } from '@/lib/utils'
import type { Resident, CollectionSchedule, Notification, Payment } from '@/types'

export default function ResidentDashboard() {
  const [data, setData] = useState<{
    resident: Resident
    upcoming_pickups: CollectionSchedule[]
    service_history: CollectionSchedule[]
    notifications: Notification[]
    recent_payments: Payment[]
  } | null>(null)

  useEffect(() => {
    residentApi.dashboard().then(r => setData(r.data.data)).catch(() => {})
  }, [])

  if (!data) return <DashboardLayout><div className="animate-pulse space-y-4">{[1,2,3].map(i => <div key={i} className="h-32 bg-slate-200 dark:bg-slate-800 rounded-2xl" />)}</div></DashboardLayout>

  const { resident, upcoming_pickups, service_history, notifications } = data

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-bold">Welcome back!</h1>
          <p className="text-slate-500">Here's your waste management overview</p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <DashboardWidget title="Assigned Bin" value={resident.bin_code || 'Pending'} subtitle={resident.bin_size ? `${resident.bin_size} · ${resident.bin_color}` : ''} icon={Package} color="bg-emerald-600" delay={0} />
          <DashboardWidget title="Next Pickup" value={upcoming_pickups[0] ? formatDate(upcoming_pickups[0].preferred_date) : 'None scheduled'} icon={Calendar} color="bg-blue-600" delay={0.1} />
          <DashboardWidget title="Payment Status" value={resident.outstanding_balance ? 'Outstanding' : 'Up to date'} subtitle={resident.outstanding_balance ? formatCurrency(resident.outstanding_balance) : ''} icon={CreditCard} color="bg-purple-600" delay={0.2} />
          <DashboardWidget title="Notifications" value={notifications.filter(n => !n.is_read).length} subtitle="unread" icon={Bell} color="bg-amber-600" delay={0.3} />
        </div>

        <div className="grid lg:grid-cols-3 gap-6">
          <Card className="lg:col-span-1">
            <CardHeader><CardTitle>Your Bin</CardTitle></CardHeader>
            <CardContent className="flex flex-col items-center space-y-4">
              {resident.bin_size && resident.bin_color ? (
                <>
                  <GarbageBin size={resident.bin_size as 'small' | 'medium' | 'large'} color={resident.bin_color} showLabel />
                  <div className="text-center space-y-1 text-sm">
                    <p><strong>ID:</strong> {resident.bin_code}</p>
                    <p><strong>Capacity:</strong> {resident.capacity_liters}L</p>
                    <p><strong>Schedule:</strong> {resident.payment_plan_name} ({resident.frequency})</p>
                    <p><strong>Zone:</strong> {resident.zone_name || 'N/A'}</p>
                  </div>
                </>
              ) : (
                <p className="text-slate-500 text-sm">Bin assignment pending</p>
              )}
            </CardContent>
          </Card>

          <Card className="lg:col-span-2">
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle>Upcoming Pickups</CardTitle>
              <Link to="/dashboard/resident/schedule" className="text-sm text-emerald-600 hover:underline">Schedule new</Link>
            </CardHeader>
            <CardContent>
              {upcoming_pickups.length === 0 ? (
                <p className="text-slate-500 text-sm">No upcoming pickups. <Link to="/dashboard/resident/schedule" className="text-emerald-600">Schedule one</Link></p>
              ) : (
                <div className="space-y-3">
                  {upcoming_pickups.map(p => (
                    <div key={p.id} className="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                      <div>
                        <p className="font-medium">{formatDate(p.preferred_date)}</p>
                        <p className="text-xs text-slate-500">{p.preferred_time || 'Any time'} · {p.schedule_type.replace('_', ' ')}</p>
                      </div>
                      <StatusBadge status={p.status} />
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        <div className="grid lg:grid-cols-2 gap-6">
          <Card>
            <CardHeader><CardTitle>Recent Notifications</CardTitle></CardHeader>
            <CardContent className="space-y-3">
              {notifications.slice(0, 5).map(n => (
                <div key={n.id} className={`p-3 rounded-xl ${n.is_read ? 'bg-slate-50 dark:bg-slate-800/50' : 'bg-emerald-50 dark:bg-emerald-900/20'}`}>
                  <p className="font-medium text-sm">{n.title}</p>
                  <p className="text-xs text-slate-500 mt-1">{n.message}</p>
                </div>
              ))}
            </CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle>Service History</CardTitle></CardHeader>
            <CardContent className="space-y-3">
              {service_history.length === 0 ? <p className="text-slate-500 text-sm">No completed collections yet</p> : service_history.slice(0, 5).map(h => (
                <div key={h.id} className="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                  <span className="text-sm">{formatDate(h.preferred_date)}</span>
                  <StatusBadge status={h.pickup_status} />
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      </div>
    </DashboardLayout>
  )
}
