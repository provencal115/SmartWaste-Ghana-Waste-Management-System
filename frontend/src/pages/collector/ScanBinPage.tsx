import { useState } from 'react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { StatusBadge } from '@/components/ui/badge'
import { collectorApi } from '@/lib/api'
import { GarbageBin } from '@/components/landing/GarbageBin'

export default function ScanBinPage() {
  const [code, setCode] = useState('')
  const [bin, setBin] = useState<Record<string, unknown> | null>(null)
  const [loading, setLoading] = useState(false)

  const handleScan = async () => {
    if (!code) return
    setLoading(true)
    try {
      const res = await collectorApi.scanBin(code)
      setBin(res.data.data)
    } catch { setBin(null) }
    finally { setLoading(false) }
  }

  const updateStatus = async (status: string) => {
    if (!bin) return
    await collectorApi.updatePickup({ schedule_id: bin.schedule_id, pickup_status: status })
  }

  return (
    <DashboardLayout>
      <div className="max-w-xl mx-auto space-y-6">
        <h1 className="text-2xl font-bold">Scan Bin</h1>
        <Card>
          <CardHeader><CardTitle>QR / Barcode Scanner</CardTitle></CardHeader>
          <CardContent className="space-y-4">
            <div className="p-8 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl text-center">
              <p className="text-slate-500 text-sm mb-4">Camera scanner placeholder — enter bin code manually</p>
              <div className="flex gap-2">
                <Input value={code} onChange={e => setCode(e.target.value)} placeholder="Enter bin code (e.g. BIN-M-GN-001)" />
                <Button onClick={handleScan} disabled={loading}>{loading ? '...' : 'Scan'}</Button>
              </div>
            </div>
            {bin && (
              <div className="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 space-y-4">
                <div className="flex items-center gap-4">
                  <GarbageBin size={bin.size as 'small' | 'medium' | 'large'} color={bin.color as string} />
                  <div>
                    <p className="font-bold">{bin.bin_code as string}</p>
                    <p className="text-sm capitalize">{bin.size as string} · {bin.color as string}</p>
                    {bin.first_name != null && <p className="text-sm text-slate-500">{String(bin.first_name)} {String(bin.last_name)}</p>}
                  </div>
                </div>
                <StatusBadge status={bin.status as string} />
                <div className="flex gap-2">
                  <Button size="sm" onClick={() => updateStatus('completed')}>Completed</Button>
                  <Button size="sm" variant="outline" onClick={() => updateStatus('delayed')}>Delayed</Button>
                  <Button size="sm" variant="destructive" onClick={() => updateStatus('missed')}>Missed</Button>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
