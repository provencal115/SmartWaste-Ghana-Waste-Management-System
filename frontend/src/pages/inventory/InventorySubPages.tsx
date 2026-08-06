import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { Card, CardContent } from '@/components/ui/card'

export function InventoryAssignmentsPage() {
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Bin Assignments</h1>
        <Card><CardContent className="pt-6"><p className="text-slate-500">Assign available bins to residents. Select a resident and an available bin to create an assignment.</p></CardContent></Card>
      </div>
    </DashboardLayout>
  )
}

export function InventoryMovementsPage() {
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Stock Movements</h1>
        <Card><CardContent className="pt-6"><p className="text-slate-500">Complete history of bin deliveries, assignments, returns, and repairs.</p></CardContent></Card>
      </div>
    </DashboardLayout>
  )
}

export function InventoryReportsPage() {
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Inventory Reports</h1>
        <Card><CardContent className="pt-6"><p className="text-slate-500">Generate and export inventory reports by size, color, status, and location.</p></CardContent></Card>
      </div>
    </DashboardLayout>
  )
}
