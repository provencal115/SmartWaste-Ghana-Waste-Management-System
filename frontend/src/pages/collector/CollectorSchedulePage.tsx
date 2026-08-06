import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { Card, CardContent } from '@/components/ui/card'

export default function CollectorSchedulePage() {
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Full Schedule</h1>
        <Card><CardContent className="pt-6"><p className="text-slate-500">Weekly schedule view with all assigned collections.</p></CardContent></Card>
      </div>
    </DashboardLayout>
  )
}
