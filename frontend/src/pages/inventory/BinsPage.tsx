import { useEffect, useState } from 'react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { StatusBadge } from '@/components/ui/badge'
import { inventoryApi } from '@/lib/api'
import type { Dustbin } from '@/types'

export default function BinsPage() {
  const [bins, setBins] = useState<Dustbin[]>([])
  const [showAdd, setShowAdd] = useState(false)
  const [form, setForm] = useState({ size: 'medium', color: 'green', brand: 'EcoBin', warehouse_location: 'Warehouse A' })

  const load = () => inventoryApi.bins().then(r => setBins(r.data.data)).catch(() => {})
  useEffect(() => { load() }, [])

  const handleAdd = async () => {
    await inventoryApi.addBin(form)
    setShowAdd(false)
    load()
  }

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Dustbins</h1>
          <Button onClick={() => setShowAdd(!showAdd)}>{showAdd ? 'Cancel' : 'Add Bin'}</Button>
        </div>
        {showAdd && (
          <Card>
            <CardContent className="pt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
              <div><label className="text-sm font-medium">Size</label>
                <select className="flex h-11 w-full rounded-xl border px-4 text-sm mt-1" value={form.size} onChange={e => setForm(f => ({ ...f, size: e.target.value }))}>
                  <option value="small">Small</option><option value="medium">Medium</option><option value="large">Large</option>
                </select></div>
              <div><label className="text-sm font-medium">Color</label>
                <select className="flex h-11 w-full rounded-xl border px-4 text-sm mt-1" value={form.color} onChange={e => setForm(f => ({ ...f, color: e.target.value }))}>
                  {['green','blue','black','yellow','red'].map(c => <option key={c} value={c}>{c}</option>)}
                </select></div>
              <Input label="Brand" value={form.brand} onChange={e => setForm(f => ({ ...f, brand: e.target.value }))} />
              <Input label="Location" value={form.warehouse_location} onChange={e => setForm(f => ({ ...f, warehouse_location: e.target.value }))} />
              <Button onClick={handleAdd} className="col-span-full">Add Bin</Button>
            </CardContent>
          </Card>
        )}
        <Card>
          <CardContent className="pt-6 overflow-x-auto">
            <table className="w-full text-sm">
              <thead><tr className="border-b border-slate-200 dark:border-slate-700">
                <th className="text-left py-3 px-2">Code</th><th className="text-left py-3 px-2">Size</th><th className="text-left py-3 px-2">Color</th><th className="text-left py-3 px-2">Capacity</th><th className="text-left py-3 px-2">Status</th><th className="text-left py-3 px-2">Location</th>
              </tr></thead>
              <tbody>{bins.map(b => (
                <tr key={b.id} className="border-b border-slate-100 dark:border-slate-800">
                  <td className="py-3 px-2 font-mono text-xs">{b.bin_code}</td>
                  <td className="py-3 px-2 capitalize">{b.size}</td>
                  <td className="py-3 px-2 capitalize">{b.color}</td>
                  <td className="py-3 px-2">{b.capacity_liters}L</td>
                  <td className="py-3 px-2"><StatusBadge status={b.status} /></td>
                  <td className="py-3 px-2">{b.warehouse_location}</td>
                </tr>
              ))}</tbody>
            </table>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
