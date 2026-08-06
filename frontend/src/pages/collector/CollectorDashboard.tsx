import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { MapPin, CheckCircle, Clock, AlertTriangle } from 'lucide-react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { DashboardWidget } from '@/components/common/AnimatedCounter'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { collectorApi } from '@/lib/api'
import type { CollectionSchedule } from '@/types'

export default function CollectorDashboard() {
  const [data, setData] = useState<{ today_schedule: CollectionSchedule[]; routes: unknown[]; stats: { completed_today: number } } | null>(null)

  useEffect(() => { collectorApi.dashboard().then(r => setData(r.data.data)).catch(() => {}) }, [])

  if (!data) return <DashboardLayout><div className="animate-pulse space-y-4">{[1,2,3].map(i => <div key={i} className="h-32 bg-slate-200 dark:bg-slate-800 rounded-2xl" />)}</div></DashboardLayout>

  const completed = data.today_schedule.filter(s => s.pickup_status === 'completed').length
  const pending = data.today_schedule.filter(s => s.pickup_status === 'pending').length

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Collector Dashboard</h1>
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <DashboardWidget title="Today's Pickups" value={data.today_schedule.length} icon={MapPin} color="bg-blue-600" />
          <DashboardWidget title="Completed" value={completed} icon={CheckCircle} color="bg-emerald-600" delay={0.1} />
          <DashboardWidget title="Pending" value={pending} icon={Clock} color="bg-amber-600" delay={0.2} />
          <DashboardWidget title="Routes" value={data.routes.length} icon={MapPin} color="bg-purple-600" delay={0.3} />
        </div>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Today's Schedule</CardTitle>
            <Link to="/dashboard/collector/routes"><Button size="sm" variant="outline">View Routes</Button></Link>
          </CardHeader>
          <CardContent className="space-y-3">
            {data.today_schedule.length === 0 ? <p className="text-slate-500 text-sm">No pickups scheduled for today</p> : data.today_schedule.map(s => (
              <div key={s.id} className="flex items-center justify-between p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                <div>
                  <p className="font-medium">{s.first_name} {s.last_name}</p>
                  <p className="text-xs text-slate-500">{s.address} · {s.bin_code}</p>
                  <p className="text-xs text-slate-400">{s.preferred_time || 'Any time'}</p>
                </div>
                <StatusBadge status={s.pickup_status} />
              </div>
            ))}
          </CardContent>
        </Card>

        <div className="grid grid-cols-2 gap-4">
          <Link to="/dashboard/collector/scan"><Button className="w-full h-16" variant="outline"><MapPin className="w-5 h-5 mr-2" /> Scan Bin QR</Button></Link>
          <Link to="/dashboard/collector/reports"><Button className="w-full h-16" variant="outline"><AlertTriangle className="w-5 h-5 mr-2" /> Submit Report</Button></Link>
        </div>
      </div>
    </DashboardLayout>
  )
}
