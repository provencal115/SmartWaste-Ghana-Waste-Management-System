import { useEffect, useState } from 'react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { StatusBadge } from '@/components/ui/badge'
import { collectorApi } from '@/lib/api'
import type { CollectionSchedule } from '@/types'

export default function CollectorRoutesPage() {
  const [schedule, setSchedule] = useState<CollectionSchedule[]>([])

  useEffect(() => { collectorApi.dashboard().then(r => setSchedule(r.data.data.today_schedule)).catch(() => {}) }, [])

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Today's Route</h1>
        <Card>
          <CardHeader><CardTitle>Optimized Collection Route</CardTitle></CardHeader>
          <CardContent>
            <div className="mb-6 p-8 bg-slate-100 dark:bg-slate-800 rounded-xl text-center">
              <p className="text-slate-500">GPS Map Placeholder</p>
              <p className="text-xs text-slate-400 mt-2">Interactive map with route optimization would render here</p>
            </div>
            <div className="space-y-3">
              {schedule.map((s, i) => (
                <div key={s.id} className="flex items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                  <div className="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-sm font-bold">{i + 1}</div>
                  <div className="flex-1">
                    <p className="font-medium">{s.first_name} {s.last_name}</p>
                    <p className="text-xs text-slate-500">{s.address}</p>
                  </div>
                  <StatusBadge status={s.pickup_status} />
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
