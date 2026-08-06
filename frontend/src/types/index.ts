export interface User {
  id: number
  email: string
  first_name: string
  last_name: string
  role: string
  role_name?: string
  phone?: string
  avatar_url?: string
  is_active?: number
}

export interface Resident {
  id: number
  user_id: number
  address: string
  city: string
  zone_id?: number
  zone_name?: string
  payment_plan_id?: number
  payment_plan_name?: string
  frequency?: string
  selected_bin_size?: string
  selected_bin_color?: string
  bin_code?: string
  bin_size?: string
  bin_color?: string
  capacity_liters?: number
  service_fee?: number
  outstanding_balance?: number
}

export interface Dustbin {
  id: number
  bin_code: string
  size: string
  color: string
  brand: string
  capacity_liters: number
  status: string
  warehouse_location?: string
}

export interface CollectionSchedule {
  id: number
  resident_id: number
  schedule_type: string
  preferred_date: string
  preferred_time?: string
  collection_notes?: string
  status: string
  pickup_status: string
  first_name?: string
  last_name?: string
  address?: string
  bin_code?: string
}

export interface Payment {
  id: number
  resident_id: number
  amount: number
  payment_method: string
  status: string
  receipt_number?: string
  transaction_ref?: string
  paid_at?: string
  created_at: string
}

export interface Notification {
  id: number
  title: string
  message: string
  type: string
  channel: string
  is_read: number
  sent_at: string
}

export interface Complaint {
  id: number
  subject: string
  description: string
  category: string
  status: string
  rating?: number
  created_at: string
}

export interface PricingPolicy {
  id: number
  bin_size: string
  payment_plan_id: number
  plan_name?: string
  frequency?: string
  price: number
  zone_id?: number
}

export interface Zone {
  id: number
  name: string
  description?: string
  region?: string
}

export type UserRole = 'resident' | 'collector' | 'inventory_manager' | 'administrator' | 'finance_manager'
