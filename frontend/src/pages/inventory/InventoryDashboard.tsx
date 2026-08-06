import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Package, AlertTriangle, Wrench, CheckCircle } from 'lucide-react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { DashboardWidget } from '@/components/common/AnimatedCounter'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { inventoryApi } from '@/lib/api'

export default function InventoryDashboard() {
  const [data, setData] = useState<{ stats: Record<string, number>; low_stock_alerts: unknown[]; recent_movements: unknown[] } | null>(null)

  useEffect(() => { inventoryApi.dashboard().then(r => setData(r.data.data)).catch(() => {}) }, [])

  if (!data) return <DashboardLayout><div className="animate-pulse space-y-4">{[1,2,3].map(i => <div key={i} className="h-32 bg-slate-200 dark:bg-slate-800 rounded-2xl" />)}</div></DashboardLayout>

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Inventory Management</h1>
          <Link to="/dashboard/inventory/bins"><Button>Add Bin</Button></Link>
        </div>
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <DashboardWidget title="Total Bins" value={data.stats.total_bins} icon={Package} color="bg-blue-600" />
          <DashboardWidget title="Available" value={data.stats.available} icon={CheckCircle} color="bg-emerald-600" delay={0.1} />
          <DashboardWidget title="Assigned" value={data.stats.assigned} icon={Package} color="bg-purple-600" delay={0.2} />
          <DashboardWidget title="Under Maintenance" value={data.stats.under_maintenance} icon={Wrench} color="bg-amber-600" delay={0.3} />
        </div>

        {data.low_stock_alerts.length > 0 && (
          <Card className="border-amber-300 dark:border-amber-700">
            <CardHeader><CardTitle className="flex items-center gap-2 text-amber-600"><AlertTriangle className="w-5 h-5" /> Low Stock Alerts</CardTitle></CardHeader>
            <CardContent className="space-y-2">
              {(data.low_stock_alerts as { bin_size: string; bin_color: string; current_stock: number; minimum_quantity: number }[]).map((a, i) => (
                <div key={i} className="flex justify-between p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-sm">
                  <span className="capitalize">{a.bin_size} {a.bin_color}</span>
                  <span>{a.current_stock} / {a.minimum_quantity} min</span>
                </div>
              ))}
            </CardContent>
          </Card>
        )}

        <Card>
          <CardHeader><CardTitle>Recent Movements</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {(data.recent_movements as { bin_code: string; movement_type: string; first_name: string; last_name: string; created_at: string }[]).slice(0, 10).map((m, i) => (
              <div key={i} className="flex justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 text-sm">
                <span>{m.bin_code} — {m.movement_type.replace('_', ' ')}</span>
                <span className="text-slate-400">{m.first_name} {m.last_name}</span>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
