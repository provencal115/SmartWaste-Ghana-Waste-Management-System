import { useEffect, useState } from 'react'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { notificationApi } from '@/lib/api'
import { formatDateTime } from '@/lib/utils'
import type { Notification } from '@/types'

export default function NotificationsPage() {
  const [notifications, setNotifications] = useState<Notification[]>([])
  const [unread, setUnread] = useState(0)

  const load = () => notificationApi.list().then(r => { setNotifications(r.data.data); setUnread(r.data.unread_count) }).catch(() => {})

  useEffect(() => { load() }, [])

  const markAllRead = async () => { await notificationApi.markRead(); load() }

  return (
    <DashboardLayout>
      <div className="max-w-2xl mx-auto space-y-6">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Notifications {unread > 0 && <span className="text-sm bg-emerald-600 text-white px-2 py-0.5 rounded-full ml-2">{unread}</span>}</h1>
          {unread > 0 && <Button variant="outline" size="sm" onClick={markAllRead}>Mark all read</Button>}
        </div>
        <Card>
          <CardContent className="pt-6 space-y-3">
            {notifications.length === 0 ? <p className="text-slate-500 text-center py-8">No notifications</p> : notifications.map(n => (
              <div key={n.id} className={`p-4 rounded-xl ${n.is_read ? 'bg-slate-50 dark:bg-slate-800/50' : 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800'}`}>
                <div className="flex justify-between items-start">
                  <p className="font-medium">{n.title}</p>
                  <span className="text-xs text-slate-400">{formatDateTime(n.sent_at)}</span>
                </div>
                <p className="text-sm text-slate-600 dark:text-slate-400 mt-1">{n.message}</p>
                <span className="text-xs text-slate-400 mt-2 inline-block capitalize">{n.type.replace(/_/g, ' ')} · {n.channel}</span>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}
