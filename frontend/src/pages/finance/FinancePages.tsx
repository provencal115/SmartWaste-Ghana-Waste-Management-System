import { useEffect, useState } from 'react'
import { DollarSign, AlertCircle, CheckCircle, Clock } from 'lucide-react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { DashboardWidget } from '@/components/common/AnimatedCounter'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { financeApi } from '@/lib/api'
import { formatCurrency } from '@/lib/utils'
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts'

export function FinanceDashboard() {
  const [data, setData] = useState<{ stats: Record<string, number>; revenueByMethod: { payment_method: string; total: number; count: number }[]; monthlyTrend: { month: string; revenue: number }[] } | null>(null)

  useEffect(() => { financeApi.dashboard().then(r => setData(r.data.data)).catch(() => {}) }, [])

  if (!data) return <DashboardLayout><div className="animate-pulse space-y-4">{[1,2,3].map(i => <div key={i} className="h-32 bg-slate-200 dark:bg-slate-800 rounded-2xl" />)}</div></DashboardLayout>

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Finance Dashboard</h1>
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <DashboardWidget title="Daily Revenue" value={formatCurrency(data.stats.daily_revenue)} icon={DollarSign} color="bg-emerald-600" />
          <DashboardWidget title="Weekly Revenue" value={formatCurrency(data.stats.weekly_revenue)} icon={DollarSign} color="bg-blue-600" delay={0.1} />
          <DashboardWidget title="Monthly Revenue" value={formatCurrency(data.stats.monthly_revenue)} icon={DollarSign} color="bg-purple-600" delay={0.2} />
          <DashboardWidget title="Outstanding" value={formatCurrency(data.stats.outstanding)} icon={AlertCircle} color="bg-red-600" delay={0.3} />
        </div>
        <div className="grid grid-cols-3 gap-4">
          <DashboardWidget title="Completed" value={data.stats.completed_payments} icon={CheckCircle} color="bg-emerald-600" />
          <DashboardWidget title="Failed" value={data.stats.failed_payments} icon={AlertCircle} color="bg-red-600" delay={0.1} />
          <DashboardWidget title="Pending Cash" value={data.stats.pending_cash} icon={Clock} color="bg-amber-600" delay={0.2} />
        </div>
        <div className="grid lg:grid-cols-2 gap-6">
          <Card>
            <CardHeader><CardTitle>Revenue by Payment Method</CardTitle></CardHeader>
            <CardContent><ResponsiveContainer width="100%" height={250}>
              <BarChart data={data.revenueByMethod}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                <XAxis dataKey="payment_method" tick={{ fontSize: 11 }} /><YAxis tick={{ fontSize: 11 }} />
                <Tooltip /><Bar dataKey="total" fill="#10b981" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer></CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle>Monthly Revenue Trend</CardTitle></CardHeader>
            <CardContent><ResponsiveContainer width="100%" height={250}>
              <BarChart data={data.monthlyTrend}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                <XAxis dataKey="month" tick={{ fontSize: 11 }} /><YAxis tick={{ fontSize: 11 }} />
                <Tooltip /><Bar dataKey="revenue" fill="#3b82f6" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer></CardContent>
          </Card>
        </div>
      </div>
    </DashboardLayout>
  )
}

export function FinancePaymentsPage() {
  const [payments, setPayments] = useState<Record<string, unknown>[]>([])
  useEffect(() => { financeApi.payments().then(r => setPayments(r.data.data)).catch(() => {}) }, [])

  const verify = async (id: number) => { await financeApi.verifyCash(id); financeApi.payments().then(r => setPayments(r.data.data)) }

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Payment Management</h1>
        <Card><CardContent className="pt-6 overflow-x-auto">
          <table className="w-full text-sm"><thead><tr className="border-b"><th className="text-left py-3 px-2">Receipt</th><th className="text-left py-3 px-2">Resident</th><th className="text-left py-3 px-2">Amount</th><th className="text-left py-3 px-2">Method</th><th className="text-left py-3 px-2">Status</th><th className="text-left py-3 px-2">Action</th></tr></thead>
          <tbody>{payments.map(p => (
            <tr key={p.id as number} className="border-b border-slate-100 dark:border-slate-800">
              <td className="py-3 px-2 font-mono text-xs">{p.receipt_number as string}</td>
              <td className="py-3 px-2">{p.first_name as string} {p.last_name as string}</td>
              <td className="py-3 px-2">{formatCurrency(p.amount as number)}</td>
              <td className="py-3 px-2 capitalize">{(p.payment_method as string).replace('_', ' ')}</td>
              <td className="py-3 px-2"><span className="capitalize">{p.status as string}</span></td>
              <td className="py-3 px-2">{p.status === 'pending' && p.payment_method === 'cash' && <button onClick={() => verify(p.id as number)} className="text-emerald-600 text-xs font-medium hover:underline">Verify</button>}</td>
            </tr>
          ))}</tbody></table>
        </CardContent></Card>
      </div>
    </DashboardLayout>
  )
}

export function FinancePricingPage() {
  const [pricing, setPricing] = useState<Record<string, unknown>[]>([])
  useEffect(() => { financeApi.pricing().then(r => setPricing(r.data.data)).catch(() => {}) }, [])
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Pricing Configuration</h1>
        <Card><CardContent className="pt-6 overflow-x-auto">
          <table className="w-full text-sm"><thead><tr className="border-b"><th className="text-left py-3 px-2">Bin Size</th><th className="text-left py-3 px-2">Plan</th><th className="text-left py-3 px-2">Zone</th><th className="text-left py-3 px-2">Price (GHS)</th></tr></thead>
          <tbody>{pricing.map(p => (
            <tr key={p.id as number} className="border-b border-slate-100 dark:border-slate-800">
              <td className="py-3 px-2 capitalize">{p.bin_size as string}</td>
              <td className="py-3 px-2">{p.plan_name as string}</td>
              <td className="py-3 px-2">{p.zone_name as string || 'All zones'}</td>
              <td className="py-3 px-2 font-bold">{formatCurrency(p.price as number)}</td>
            </tr>
          ))}</tbody></table>
        </CardContent></Card>
      </div>
    </DashboardLayout>
  )
}

export function FinanceReportsPage() {
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Financial Reports</h1>
        <Card><CardContent className="pt-6"><p className="text-slate-500">Generate revenue, outstanding payments, and transaction reports. Export to PDF, Excel, or CSV.</p></CardContent></Card>
      </div>
    </DashboardLayout>
  )
}

export function FinanceInvoicesPage() {
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Invoices & Receipts</h1>
        <Card><CardContent className="pt-6"><p className="text-slate-500">Generate and manage digital invoices and payment receipts.</p></CardContent></Card>
      </div>
    </DashboardLayout>
  )
}
