import { useState } from 'react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { residentApi } from '@/lib/api'

export default function SchedulePickupPage() {
  const [form, setForm] = useState({ schedule_type: 'one_time', preferred_date: '', preferred_time: '', recurrence_pattern: '', collection_notes: '' })
  const [success, setSuccess] = useState(false)
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    try {
      await residentApi.schedulePickup(form)
      setSuccess(true)
      setForm({ schedule_type: 'one_time', preferred_date: '', preferred_time: '', recurrence_pattern: '', collection_notes: '' })
    } catch { /* handle error */ }
    finally { setLoading(false) }
  }

  return (
    <DashboardLayout>
      <div className="max-w-xl mx-auto space-y-6">
        <h1 className="text-2xl font-bold">Schedule Pickup</h1>
        <Card>
          <CardHeader><CardTitle>Collection Details</CardTitle></CardHeader>
          <CardContent>
            {success && <div className="mb-4 p-3 bg-emerald-50 text-emerald-700 text-sm rounded-xl">Pickup scheduled successfully!</div>}
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Type</label>
                <select className="flex h-11 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 text-sm" value={form.schedule_type} onChange={e => setForm(f => ({ ...f, schedule_type: e.target.value }))}>
                  <option value="one_time">One-time Pickup</option>
                  <option value="recurring">Recurring Pickup</option>
                </select>
              </div>
              <Input label="Preferred Date" type="date" value={form.preferred_date} onChange={e => setForm(f => ({ ...f, preferred_date: e.target.value }))} required />
              <Input label="Preferred Time" type="time" value={form.preferred_time} onChange={e => setForm(f => ({ ...f, preferred_time: e.target.value }))} />
              {form.schedule_type === 'recurring' && (
                <div className="space-y-1.5">
                  <label className="text-sm font-medium">Recurrence</label>
                  <select className="flex h-11 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 text-sm" value={form.recurrence_pattern} onChange={e => setForm(f => ({ ...f, recurrence_pattern: e.target.value }))}>
                    <option value="weekly">Every Week</option>
                    <option value="biweekly">Every Two Weeks</option>
                    <option value="monthly">Every Month</option>
                  </select>
                </div>
              )}
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Notes</label>
                <textarea className="flex w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm min-h-[80px]" value={form.collection_notes} onChange={e => setForm(f => ({ ...f, collection_notes: e.target.value }))} placeholder="Special instructions..." />
              </div>
              <Button type="submit" className="w-full" disabled={loading}>{loading ? 'Scheduling...' : 'Schedule Pickup'}</Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
