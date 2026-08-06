import { useState } from 'react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { residentApi } from '@/lib/api'
import { formatCurrency } from '@/lib/utils'
import { Smartphone, CreditCard, Banknote } from 'lucide-react'

const methods = [
  { id: 'mobile_money', label: 'Mobile Money', icon: Smartphone, desc: 'MTN, Vodafone, AirtelTigo' },
  { id: 'bank_card', label: 'Bank Card', icon: CreditCard, desc: 'Visa, Mastercard' },
  { id: 'cash', label: 'Cash', icon: Banknote, desc: 'Pay collector directly' },
]

export default function PaymentsPage() {
  const [method, setMethod] = useState('mobile_money')
  const [amount, setAmount] = useState('')
  const [receipt, setReceipt] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  const handlePay = async () => {
    setLoading(true)
    try {
      const res = await residentApi.makePayment({ payment_method: method, amount: parseFloat(amount) || undefined })
      setReceipt(res.data.receipt_number)
    } catch { /* handle */ }
    finally { setLoading(false) }
  }

  return (
    <DashboardLayout>
      <div className="max-w-2xl mx-auto space-y-6">
        <h1 className="text-2xl font-bold">Payments</h1>
        {receipt ? (
          <Card>
            <CardContent className="pt-6 text-center space-y-4">
              <div className="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto"><span className="text-2xl">✓</span></div>
              <h2 className="text-xl font-bold">Payment Successful</h2>
              <p className="text-slate-500">Receipt: <strong>{receipt}</strong></p>
              <Button onClick={() => setReceipt(null)}>Make Another Payment</Button>
            </CardContent>
          </Card>
        ) : (
          <Card>
            <CardHeader><CardTitle>Make a Payment</CardTitle></CardHeader>
            <CardContent className="space-y-6">
              <div className="grid gap-3">
                {methods.map(m => (
                  <button key={m.id} onClick={() => setMethod(m.id)} className={`flex items-center gap-4 p-4 rounded-xl border-2 transition-all ${method === m.id ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/20' : 'border-slate-200 dark:border-slate-700'}`}>
                    <m.icon className="w-6 h-6 text-emerald-600" />
                    <div className="text-left"><p className="font-medium">{m.label}</p><p className="text-xs text-slate-500">{m.desc}</p></div>
                  </button>
                ))}
              </div>
              <Input label="Amount (GHS)" type="number" value={amount} onChange={e => setAmount(e.target.value)} placeholder="Enter amount" />
              <Button className="w-full" onClick={handlePay} disabled={loading}>{loading ? 'Processing...' : `Pay ${amount ? formatCurrency(parseFloat(amount)) : ''}`}</Button>
            </CardContent>
          </Card>
        )}
      </div>
    </DashboardLayout>
  )
}
