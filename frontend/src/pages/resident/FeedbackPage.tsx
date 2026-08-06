import { useState, useEffect } from 'react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { StatusBadge } from '@/components/ui/badge'
import { residentApi } from '@/lib/api'
import type { Complaint } from '@/types'

export default function FeedbackPage() {
  const [complaints, setComplaints] = useState<Complaint[]>([])
  const [form, setForm] = useState({ subject: '', description: '', category: 'service', rating: 5 })
  const [loading, setLoading] = useState(false)
  const [success, setSuccess] = useState(false)

  useEffect(() => { residentApi.complaints().then(r => setComplaints(r.data.data)).catch(() => {}) }, [])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    try {
      await residentApi.submitComplaint(form)
      setSuccess(true)
      setForm({ subject: '', description: '', category: 'service', rating: 5 })
      const r = await residentApi.complaints()
      setComplaints(r.data.data)
    } catch { /* handle */ }
    finally { setLoading(false) }
  }

  return (
    <DashboardLayout>
      <div className="max-w-3xl mx-auto space-y-6">
        <h1 className="text-2xl font-bold">Feedback & Complaints</h1>
        <Card>
          <CardHeader><CardTitle>Submit Feedback</CardTitle></CardHeader>
          <CardContent>
            {success && <div className="mb-4 p-3 bg-emerald-50 text-emerald-700 text-sm rounded-xl">Submitted successfully!</div>}
            <form onSubmit={handleSubmit} className="space-y-4">
              <Input label="Subject" value={form.subject} onChange={e => setForm(f => ({ ...f, subject: e.target.value }))} required />
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Category</label>
                <select className="flex h-11 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 text-sm" value={form.category} onChange={e => setForm(f => ({ ...f, category: e.target.value }))}>
                  <option value="service">Service Quality</option>
                  <option value="billing">Billing</option>
                  <option value="bin_damage">Bin Damage</option>
                  <option value="missed_pickup">Missed Pickup</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Rating</label>
                <div className="flex gap-1">{[1,2,3,4,5].map(s => (
                  <button key={s} type="button" onClick={() => setForm(f => ({ ...f, rating: s }))} className={`text-2xl ${s <= form.rating ? 'text-amber-400' : 'text-slate-300'}`}>★</button>
                ))}</div>
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Description</label>
                <textarea className="flex w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm min-h-[100px]" value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} required />
              </div>
              <Button type="submit" disabled={loading}>{loading ? 'Submitting...' : 'Submit'}</Button>
            </form>
          </CardContent>
        </Card>
        {complaints.length > 0 && (
          <Card>
            <CardHeader><CardTitle>Your Complaints</CardTitle></CardHeader>
            <CardContent className="space-y-3">
              {complaints.map(c => (
                <div key={c.id} className="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 flex justify-between items-start">
                  <div><p className="font-medium">{c.subject}</p><p className="text-xs text-slate-500 mt-1">{c.description.slice(0, 100)}</p></div>
                  <StatusBadge status={c.status} />
                </div>
              ))}
            </CardContent>
          </Card>
        )}
      </div>
    </DashboardLayout>
  )
}
