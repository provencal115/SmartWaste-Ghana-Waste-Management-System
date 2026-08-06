import { useEffect, useState } from 'react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { StatusBadge } from '@/components/ui/badge'
import { adminApi } from '@/lib/api'
import { exportReport } from '@/lib/export'

export function AdminUsersPage() {
  const [users, setUsers] = useState<Record<string, unknown>[]>([])
  useEffect(() => { adminApi.users().then(r => setUsers(r.data.data)).catch(() => {}) }, [])
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">User Management</h1>
        <Card><CardContent className="pt-6 overflow-x-auto">
          <table className="w-full text-sm"><thead><tr className="border-b"><th className="text-left py-3 px-2">Name</th><th className="text-left py-3 px-2">Email</th><th className="text-left py-3 px-2">Role</th><th className="text-left py-3 px-2">Status</th></tr></thead>
          <tbody>{users.map((u) => (
            <tr key={u.id as number} className="border-b border-slate-100 dark:border-slate-800">
              <td className="py-3 px-2">{u.first_name as string} {u.last_name as string}</td>
              <td className="py-3 px-2">{u.email as string}</td>
              <td className="py-3 px-2 capitalize">{(u.role as string).replace(/_/g, ' ')}</td>
              <td className="py-3 px-2"><StatusBadge status={u.is_active ? 'active' : 'cancelled'} /></td>
            </tr>
          ))}</tbody></table>
        </CardContent></Card>
      </div>
    </DashboardLayout>
  )
}

export function AdminRoutesPage() {
  const [routes, setRoutes] = useState<Record<string, unknown>[]>([])
  const [zones, setZones] = useState<Record<string, unknown>[]>([])
  useEffect(() => { adminApi.routes().then(r => setRoutes(r.data.data)).catch(() => {}); adminApi.zones().then(r => setZones(r.data.data)).catch(() => {}) }, [])
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Zones & Routes</h1>
        <div className="grid md:grid-cols-2 gap-6">
          <Card><CardHeader><CardTitle>Collection Zones</CardTitle></CardHeader><CardContent className="space-y-2">
            {zones.map(z => <div key={z.id as number} className="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50"><p className="font-medium">{z.name as string}</p><p className="text-xs text-slate-500">{z.region as string}</p></div>)}
          </CardContent></Card>
          <Card><CardHeader><CardTitle>Collection Routes</CardTitle></CardHeader><CardContent className="space-y-2">
            {routes.map(r => <div key={r.id as number} className="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 flex justify-between"><span>{r.name as string}</span><StatusBadge status={r.is_active ? 'active' : 'cancelled'} /></div>)}
          </CardContent></Card>
        </div>
      </div>
    </DashboardLayout>
  )
}

export function AdminTrucksPage() {
  const [trucks, setTrucks] = useState<Record<string, unknown>[]>([])
  useEffect(() => { adminApi.trucks().then(r => setTrucks(r.data.data)).catch(() => {}) }, [])
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Fleet Management</h1>
        <Card><CardContent className="pt-6 overflow-x-auto">
          <table className="w-full text-sm"><thead><tr className="border-b"><th className="text-left py-3 px-2">Plate</th><th className="text-left py-3 px-2">Model</th><th className="text-left py-3 px-2">Capacity</th><th className="text-left py-3 px-2">Zone</th><th className="text-left py-3 px-2">Status</th></tr></thead>
          <tbody>{trucks.map(t => (
            <tr key={t.id as number} className="border-b border-slate-100 dark:border-slate-800">
              <td className="py-3 px-2 font-mono">{t.plate_number as string}</td><td className="py-3 px-2">{t.model as string}</td>
              <td className="py-3 px-2">{t.capacity_kg as number} kg</td><td className="py-3 px-2">{t.zone_name as string}</td>
              <td className="py-3 px-2"><StatusBadge status={t.status as string} /></td>
            </tr>
          ))}</tbody></table>
        </CardContent></Card>
      </div>
    </DashboardLayout>
  )
}

export function AdminComplaintsPage() {
  const [complaints, setComplaints] = useState<Record<string, unknown>[]>([])
  useEffect(() => { adminApi.complaints().then(r => setComplaints(r.data.data)).catch(() => {}) }, [])
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Complaint Management</h1>
        <Card><CardContent className="pt-6 space-y-3">
          {complaints.map(c => (
            <div key={c.id as number} className="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 flex justify-between items-start">
              <div><p className="font-medium">{c.subject as string}</p><p className="text-xs text-slate-500">{c.first_name as string} {c.last_name as string} · {c.category as string}</p></div>
              <StatusBadge status={c.status as string} />
            </div>
          ))}
        </CardContent></Card>
      </div>
    </DashboardLayout>
  )
}

export function AdminReportsPage() {
  const [type, setType] = useState('collections')
  const types = ['collections', 'payments', 'inventory', 'residents', 'trucks', 'complaints', 'revenue', 'staff']
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Reports</h1>
        <div className="flex flex-wrap gap-2">
          {types.map(t => <Button key={t} size="sm" variant={type === t ? 'default' : 'outline'} onClick={() => setType(t)} className="capitalize">{t}</Button>)}
        </div>
        <Card><CardContent className="pt-6 flex gap-3">
          <Button onClick={() => exportReport(type, 'csv')}>Export CSV</Button>
          <Button variant="outline" onClick={() => exportReport(type, 'excel')}>Export Excel</Button>
          <Button variant="outline" onClick={() => exportReport(type, 'pdf')}>Export PDF</Button>
        </CardContent></Card>
      </div>
    </DashboardLayout>
  )
}

export function AdminSettingsPage() {
  const [settings, setSettings] = useState<Record<string, unknown>[]>([])
  useEffect(() => { adminApi.smartSettings().then(r => setSettings(r.data.data)).catch(() => {}) }, [])
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Smart Settings</h1>
        <div className="grid md:grid-cols-2 gap-4">
          {settings.map(s => (
            <Card key={s.id as number}><CardContent className="pt-6">
              <p className="font-medium capitalize">{(s.setting_key as string).replace(/_/g, ' ')}</p>
              <p className="text-xs text-slate-500 mt-1">{s.description as string}</p>
              <pre className="mt-2 text-xs bg-slate-100 dark:bg-slate-800 p-2 rounded-lg overflow-auto">{JSON.stringify(JSON.parse(s.setting_value as string), null, 2)}</pre>
            </CardContent></Card>
          ))}
        </div>
      </div>
    </DashboardLayout>
  )
}

export function AdminLogsPage() {
  const [logs, setLogs] = useState<Record<string, unknown>[]>([])
  useEffect(() => { adminApi.logs().then(r => setLogs(r.data.data)).catch(() => {}) }, [])
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Audit Logs</h1>
        <Card><CardContent className="pt-6 space-y-2 max-h-[600px] overflow-y-auto">
          {logs.map(l => (
            <div key={l.id as number} className="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 text-sm flex justify-between">
              <span><strong>{l.action as string}</strong> · {l.module as string} · {l.first_name as string} {l.last_name as string}</span>
              <span className="text-slate-400 text-xs">{l.created_at as string}</span>
            </div>
          ))}
        </CardContent></Card>
      </div>
    </DashboardLayout>
  )
}
