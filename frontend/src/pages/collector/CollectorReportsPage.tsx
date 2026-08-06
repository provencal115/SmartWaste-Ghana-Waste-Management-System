import { useState } from 'react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { collectorApi } from '@/lib/api'

const reportTypes = [
  { id: 'overflow', label: 'Overflowing Bin' },
  { id: 'damaged_bin', label: 'Damaged Bin' },
  { id: 'blocked_road', label: 'Blocked Road' },
  { id: 'missed_pickup', label: 'Missed Pickup' },
  { id: 'truck_breakdown', label: 'Truck Breakdown' },
  { id: 'emergency', label: 'Emergency' },
]

export default function CollectorReportsPage() {
  const [form, setForm] = useState({ report_type: 'overflow', description: '' })
  const [success, setSuccess] = useState(false)
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    try {
      await collectorApi.submitReport(form)
      setSuccess(true)
      setForm({ report_type: 'overflow', description: '' })
    } catch { /* handle */ }
    finally { setLoading(false) }
  }

  return (
    <DashboardLayout>
      <div className="max-w-xl mx-auto space-y-6">
        <h1 className="text-2xl font-bold">Submit Report</h1>
        <Card>
          <CardHeader><CardTitle>Field Report</CardTitle></CardHeader>
          <CardContent>
            {success && <div className="mb-4 p-3 bg-emerald-50 text-emerald-700 text-sm rounded-xl">Report submitted successfully!</div>}
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-2">
                {reportTypes.map(t => (
                  <button key={t.id} type="button" onClick={() => setForm(f => ({ ...f, report_type: t.id }))}
                    className={`p-3 rounded-xl border-2 text-sm font-medium transition-all ${form.report_type === t.id ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/20' : 'border-slate-200 dark:border-slate-700'}`}>
                    {t.label}
                  </button>
                ))}
              </div>
              <textarea className="flex w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm min-h-[120px]" value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} placeholder="Describe the issue..." required />
              <Button type="submit" className="w-full" disabled={loading}>{loading ? 'Submitting...' : 'Submit Report'}</Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
