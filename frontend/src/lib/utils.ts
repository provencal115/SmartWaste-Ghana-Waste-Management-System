import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('en-GH', { style: 'currency', currency: 'GHS' }).format(amount)
}

export function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('en-GH', { year: 'numeric', month: 'short', day: 'numeric' })
}

export function formatDateTime(date: string): string {
  return new Date(date).toLocaleString('en-GH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

export const BIN_COLORS: Record<string, string> = {
  green: '#22c55e',
  blue: '#3b82f6',
  black: '#1e293b',
  yellow: '#eab308',
  red: '#ef4444',
}

export const BIN_SIZES = {
  small: { label: 'Small Bin', capacity: '120L', height: '60cm' },
  medium: { label: 'Medium Bin', capacity: '240L', height: '90cm' },
  large: { label: 'Large Bin', capacity: '360L', height: '120cm' },
}

export const ROLE_DASHBOARDS: Record<string, string> = {
  resident: '/dashboard/resident',
  collector: '/dashboard/collector',
  inventory_manager: '/dashboard/inventory',
  administrator: '/dashboard/admin',
  finance_manager: '/dashboard/finance',
}
