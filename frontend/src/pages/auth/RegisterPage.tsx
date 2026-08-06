import { useState, useEffect } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import { Recycle, ArrowLeft, ArrowRight, CheckCircle } from 'lucide-react'
import { authApi, residentApi } from '@/lib/api'
import { BIN_SIZES, BIN_COLORS, formatCurrency } from '@/lib/utils'
import { GarbageBin } from '@/components/landing/GarbageBin'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { PasswordField, ConfirmPasswordField, allPasswordRequirementsMet } from '@/components/ui/password-field'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import type { PricingPolicy, Zone } from '@/types'

const colors = ['green', 'blue', 'black', 'yellow', 'red']
const plans = [
  { id: 1, name: 'Weekly', frequency: 'weekly' },
  { id: 2, name: 'Bi-weekly', frequency: 'biweekly' },
  { id: 3, name: 'Monthly', frequency: 'monthly' },
]

export default function RegisterPage() {
  const navigate = useNavigate()
  const [step, setStep] = useState(1)
  const [pricing, setPricing] = useState<PricingPolicy[]>([])
  const [zones, setZones] = useState<Zone[]>([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const [confirmation, setConfirmation] = useState<Record<string, unknown> | null>(null)
  const [userId, setUserId] = useState<number | null>(null)

  const [form, setForm] = useState({
    first_name: '', last_name: '', email: '', phone: '', password: '', password_confirm: '',
    address: '', city: 'Accra', zone_id: '',
    bin_size: 'medium' as string, bin_color: 'green' as string, payment_plan_id: 3,
  })

  const passwordsMatch = form.password === form.password_confirm && form.password_confirm.length > 0
  const passwordValid = allPasswordRequirementsMet(form.password)
  const canProceedStep1 = form.first_name && form.email && form.password && form.address && passwordValid && passwordsMatch

  useEffect(() => {
    residentApi.pricing().then(r => setPricing(r.data.data)).catch(() => {})
    residentApi.zones().then(r => setZones(r.data.data)).catch(() => {})
  }, [])

  const getPrice = (size: string, planId: number) => {
    const p = pricing.find(p => p.bin_size === size && p.payment_plan_id === planId)
    return p?.price || 0
  }

  const currentPrice = getPrice(form.bin_size, form.payment_plan_id)

  const handleRegister = async () => {
    setError('')
    setLoading(true)
    try {
      const res = await authApi.register({ ...form, zone_id: form.zone_id ? parseInt(form.zone_id) : null })
      setUserId(res.data.user_id)
      setConfirmation(res.data.confirmation)
      setStep(4)
    } catch (err: unknown) {
      setError((err as { message?: string })?.message || 'Registration failed')
    } finally {
      setLoading(false)
    }
  }

  const handleConfirm = async () => {
    if (!userId) return
    setLoading(true)
    try {
      await authApi.confirmRegistration(userId)
      navigate('/login')
    } catch (err: unknown) {
      setError((err as { message?: string })?.message || 'Confirmation failed')
    } finally {
      setLoading(false)
    }
  }

  const update = (key: string, value: string | number) => setForm(f => ({ ...f, [key]: value }))

  return (
    <div className="min-h-screen gradient-bg py-8 px-4">
      <div className="max-w-3xl mx-auto">
        <div className="text-center mb-8">
          <Link to="/" className="inline-flex items-center gap-2">
            <div className="p-2 bg-emerald-600 rounded-xl"><Recycle className="w-6 h-6 text-white" /></div>
            <span className="font-bold text-xl">Smart<span className="text-emerald-600">Waste</span></span>
          </Link>
        </div>

        <div className="flex items-center justify-center gap-2 mb-8">
          {[1, 2, 3, 4].map(s => (
            <div key={s} className={`h-2 rounded-full transition-all ${s <= step ? 'bg-emerald-600 w-12' : 'bg-slate-200 dark:bg-slate-700 w-8'}`} />
          ))}
        </div>

        <motion.div key={step} initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }}>
          <Card>
            {step === 1 && (
              <>
                <CardHeader><CardTitle>Personal Information</CardTitle></CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <Input label="First Name" value={form.first_name} onChange={e => update('first_name', e.target.value)} required />
                    <Input label="Last Name" value={form.last_name} onChange={e => update('last_name', e.target.value)} required />
                  </div>
                  <Input label="Email" type="email" value={form.email} onChange={e => update('email', e.target.value)} required />
                  <Input label="Phone" value={form.phone} onChange={e => update('phone', e.target.value)} placeholder="+233..." />
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <PasswordField
                      label="Password"
                      id="register-password"
                      value={form.password}
                      onChange={e => update('password', e.target.value)}
                      showStrength
                      required
                      autoComplete="new-password"
                    />
                    <ConfirmPasswordField
                      label="Confirm Password"
                      id="register-password-confirm"
                      password={form.password}
                      value={form.password_confirm}
                      onChange={e => update('password_confirm', e.target.value)}
                      required
                      autoComplete="new-password"
                    />
                  </div>
                  <Input label="Address" value={form.address} onChange={e => update('address', e.target.value)} required />
                  <div className="grid grid-cols-2 gap-4">
                    <Input label="City" value={form.city} onChange={e => update('city', e.target.value)} />
                    <div className="space-y-1.5">
                      <label className="text-sm font-medium">Zone</label>
                      <select className="flex h-11 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 text-sm" value={form.zone_id} onChange={e => update('zone_id', e.target.value)}>
                        <option value="">Select zone</option>
                        {zones.map(z => <option key={z.id} value={z.id}>{z.name}</option>)}
                      </select>
                    </div>
                  </div>
                  <Button className="w-full" onClick={() => setStep(2)} disabled={!canProceedStep1}>
                    Next <ArrowRight className="w-4 h-4" />
                  </Button>
                </CardContent>
              </>
            )}

            {step === 2 && (
              <>
                <CardHeader><CardTitle>Choose Your Bin</CardTitle></CardHeader>
                <CardContent className="space-y-6">
                  <div className="grid grid-cols-3 gap-4">
                    {Object.entries(BIN_SIZES).map(([key, val]) => (
                      <button key={key} onClick={() => update('bin_size', key)}
                        className={`p-4 rounded-xl border-2 transition-all text-center ${form.bin_size === key ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-emerald-300'}`}>
                        <GarbageBin size={key as 'small' | 'medium' | 'large'} color={form.bin_color} showLabel />
                        <p className="font-semibold mt-2">{val.label}</p>
                        <p className="text-xs text-slate-500">{val.capacity}</p>
                      </button>
                    ))}
                  </div>
                  <div>
                    <p className="text-sm font-medium mb-3">Select Color</p>
                    <div className="flex gap-3 justify-center">
                      {colors.map(c => (
                        <button key={c} onClick={() => update('bin_color', c)}
                          className={`w-10 h-10 rounded-full border-2 transition-transform hover:scale-110 ${form.bin_color === c ? 'border-emerald-600 scale-110 ring-2 ring-emerald-300' : 'border-transparent'}`}
                          style={{ backgroundColor: BIN_COLORS[c] }} title={c} />
                      ))}
                    </div>
                  </div>
                  <div className="flex gap-3">
                    <Button variant="outline" onClick={() => setStep(1)}><ArrowLeft className="w-4 h-4" /> Back</Button>
                    <Button className="flex-1" onClick={() => setStep(3)}>Next <ArrowRight className="w-4 h-4" /></Button>
                  </div>
                </CardContent>
              </>
            )}

            {step === 3 && (
              <>
                <CardHeader><CardTitle>Select Payment Plan</CardTitle></CardHeader>
                <CardContent className="space-y-4">
                  {plans.map(plan => (
                    <button key={plan.id} onClick={() => update('payment_plan_id', plan.id)}
                      className={`w-full p-4 rounded-xl border-2 flex justify-between items-center transition-all ${form.payment_plan_id === plan.id ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/20' : 'border-slate-200 dark:border-slate-700'}`}>
                      <div className="text-left">
                        <p className="font-semibold">{plan.name}</p>
                        <p className="text-sm text-slate-500 capitalize">{plan.frequency} billing</p>
                      </div>
                      <p className="text-xl font-bold text-emerald-600">{formatCurrency(getPrice(form.bin_size, plan.id))}</p>
                    </button>
                  ))}
                  <div className="glass p-4 rounded-xl text-center">
                    <p className="text-sm text-slate-500">Total Service Charge</p>
                    <p className="text-3xl font-bold text-emerald-600">{formatCurrency(currentPrice)}</p>
                  </div>
                  {error && <div className="p-3 bg-red-50 text-red-600 text-sm rounded-xl">{error}</div>}
                  <div className="flex gap-3">
                    <Button variant="outline" onClick={() => setStep(2)}><ArrowLeft className="w-4 h-4" /> Back</Button>
                    <Button className="flex-1" onClick={handleRegister} disabled={loading}>{loading ? 'Processing...' : 'Review & Confirm'}</Button>
                  </div>
                </CardContent>
              </>
            )}

            {step === 4 && confirmation && (
              <>
                <CardHeader><CardTitle className="flex items-center gap-2"><CheckCircle className="w-6 h-6 text-emerald-600" /> Confirm Registration</CardTitle></CardHeader>
                <CardContent className="space-y-6">
                  <div className="flex justify-center"><GarbageBin size={confirmation.bin_size as 'small' | 'medium' | 'large'} color={confirmation.bin_color as string} showLabel /></div>
                  <div className="space-y-3 text-sm">
                    <div className="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800"><span className="text-slate-500">Bin Size</span><span className="font-medium capitalize">{String(confirmation.bin_size)}</span></div>
                    <div className="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800"><span className="text-slate-500">Color</span><span className="font-medium capitalize">{String(confirmation.bin_color)}</span></div>
                    <div className="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800"><span className="text-slate-500">Service Fee</span><span className="font-medium">{formatCurrency(confirmation.service_fee as number)}</span></div>
                    <div className="flex justify-between py-3"><span className="font-semibold">Total Payable</span><span className="text-xl font-bold text-emerald-600">{formatCurrency(confirmation.total_payable as number)}</span></div>
                  </div>
                  {error && <div className="p-3 bg-red-50 text-red-600 text-sm rounded-xl">{error}</div>}
                  <Button className="w-full" onClick={handleConfirm} disabled={loading}>{loading ? 'Activating...' : 'Confirm & Activate Account'}</Button>
                </CardContent>
              </>
            )}
          </Card>
        </motion.div>
        <p className="mt-6 text-center text-sm text-slate-500">Already have an account? <Link to="/login" className="text-emerald-600 font-medium hover:underline">Sign In</Link></p>
      </div>
    </div>
  )
}
