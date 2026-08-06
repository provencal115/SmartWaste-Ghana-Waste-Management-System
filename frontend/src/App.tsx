import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider } from '@/contexts/AuthContext'
import { ThemeProvider } from '@/contexts/ThemeContext'
import { ProtectedRoute } from '@/components/auth/ProtectedRoute'

import LandingPage from '@/pages/LandingPage'
import LoginPage from '@/pages/auth/LoginPage'
import RegisterPage from '@/pages/auth/RegisterPage'
import ForgotPasswordPage from '@/pages/auth/ForgotPasswordPage'

import ResidentDashboard from '@/pages/resident/ResidentDashboard'
import SchedulePickupPage from '@/pages/resident/SchedulePickupPage'
import PaymentsPage from '@/pages/resident/PaymentsPage'
import FeedbackPage from '@/pages/resident/FeedbackPage'
import NotificationsPage from '@/pages/resident/NotificationsPage'

import CollectorDashboard from '@/pages/collector/CollectorDashboard'
import ScanBinPage from '@/pages/collector/ScanBinPage'
import CollectorReportsPage from '@/pages/collector/CollectorReportsPage'
import CollectorRoutesPage from '@/pages/collector/CollectorRoutesPage'
import CollectorSchedulePage from '@/pages/collector/CollectorSchedulePage'

import InventoryDashboard from '@/pages/inventory/InventoryDashboard'
import BinsPage from '@/pages/inventory/BinsPage'
import { InventoryAssignmentsPage, InventoryMovementsPage, InventoryReportsPage } from '@/pages/inventory/InventorySubPages'

import AdminDashboard from '@/pages/admin/AdminDashboard'
import { AdminUsersPage, AdminRoutesPage, AdminTrucksPage, AdminComplaintsPage, AdminReportsPage, AdminSettingsPage, AdminLogsPage } from '@/pages/admin/AdminSubPages'

import { FinanceDashboard, FinancePaymentsPage, FinancePricingPage, FinanceReportsPage, FinanceInvoicesPage } from '@/pages/finance/FinancePages'

export default function App() {
  return (
    <ThemeProvider>
      <AuthProvider>
        <BrowserRouter>
          <Routes>
            <Route path="/" element={<LandingPage />} />
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            <Route path="/forgot-password" element={<ForgotPasswordPage />} />

            {/* Resident */}
            <Route path="/dashboard/resident" element={<ProtectedRoute roles={['resident']}><ResidentDashboard /></ProtectedRoute>} />
            <Route path="/dashboard/resident/schedule" element={<ProtectedRoute roles={['resident']}><SchedulePickupPage /></ProtectedRoute>} />
            <Route path="/dashboard/resident/payments" element={<ProtectedRoute roles={['resident']}><PaymentsPage /></ProtectedRoute>} />
            <Route path="/dashboard/resident/feedback" element={<ProtectedRoute roles={['resident']}><FeedbackPage /></ProtectedRoute>} />
            <Route path="/dashboard/resident/notifications" element={<ProtectedRoute roles={['resident']}><NotificationsPage /></ProtectedRoute>} />

            {/* Collector */}
            <Route path="/dashboard/collector" element={<ProtectedRoute roles={['collector']}><CollectorDashboard /></ProtectedRoute>} />
            <Route path="/dashboard/collector/routes" element={<ProtectedRoute roles={['collector']}><CollectorRoutesPage /></ProtectedRoute>} />
            <Route path="/dashboard/collector/scan" element={<ProtectedRoute roles={['collector']}><ScanBinPage /></ProtectedRoute>} />
            <Route path="/dashboard/collector/reports" element={<ProtectedRoute roles={['collector']}><CollectorReportsPage /></ProtectedRoute>} />
            <Route path="/dashboard/collector/schedule" element={<ProtectedRoute roles={['collector']}><CollectorSchedulePage /></ProtectedRoute>} />

            {/* Inventory */}
            <Route path="/dashboard/inventory" element={<ProtectedRoute roles={['inventory_manager']}><InventoryDashboard /></ProtectedRoute>} />
            <Route path="/dashboard/inventory/bins" element={<ProtectedRoute roles={['inventory_manager']}><BinsPage /></ProtectedRoute>} />
            <Route path="/dashboard/inventory/assignments" element={<ProtectedRoute roles={['inventory_manager']}><InventoryAssignmentsPage /></ProtectedRoute>} />
            <Route path="/dashboard/inventory/movements" element={<ProtectedRoute roles={['inventory_manager']}><InventoryMovementsPage /></ProtectedRoute>} />
            <Route path="/dashboard/inventory/reports" element={<ProtectedRoute roles={['inventory_manager']}><InventoryReportsPage /></ProtectedRoute>} />

            {/* Admin */}
            <Route path="/dashboard/admin" element={<ProtectedRoute roles={['administrator']}><AdminDashboard /></ProtectedRoute>} />
            <Route path="/dashboard/admin/users" element={<ProtectedRoute roles={['administrator']}><AdminUsersPage /></ProtectedRoute>} />
            <Route path="/dashboard/admin/routes" element={<ProtectedRoute roles={['administrator']}><AdminRoutesPage /></ProtectedRoute>} />
            <Route path="/dashboard/admin/trucks" element={<ProtectedRoute roles={['administrator']}><AdminTrucksPage /></ProtectedRoute>} />
            <Route path="/dashboard/admin/complaints" element={<ProtectedRoute roles={['administrator']}><AdminComplaintsPage /></ProtectedRoute>} />
            <Route path="/dashboard/admin/reports" element={<ProtectedRoute roles={['administrator']}><AdminReportsPage /></ProtectedRoute>} />
            <Route path="/dashboard/admin/settings" element={<ProtectedRoute roles={['administrator']}><AdminSettingsPage /></ProtectedRoute>} />
            <Route path="/dashboard/admin/logs" element={<ProtectedRoute roles={['administrator']}><AdminLogsPage /></ProtectedRoute>} />

            {/* Finance */}
            <Route path="/dashboard/finance" element={<ProtectedRoute roles={['finance_manager']}><FinanceDashboard /></ProtectedRoute>} />
            <Route path="/dashboard/finance/payments" element={<ProtectedRoute roles={['finance_manager']}><FinancePaymentsPage /></ProtectedRoute>} />
            <Route path="/dashboard/finance/pricing" element={<ProtectedRoute roles={['finance_manager']}><FinancePricingPage /></ProtectedRoute>} />
            <Route path="/dashboard/finance/reports" element={<ProtectedRoute roles={['finance_manager']}><FinanceReportsPage /></ProtectedRoute>} />
            <Route path="/dashboard/finance/invoices" element={<ProtectedRoute roles={['finance_manager']}><FinanceInvoicesPage /></ProtectedRoute>} />

            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </BrowserRouter>
      </AuthProvider>
    </ThemeProvider>
  )
}
