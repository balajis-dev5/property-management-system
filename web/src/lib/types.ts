export const UNIT_TYPES = ['1BHK', '2BHK', '3BHK'] as const
export type UnitType = (typeof UNIT_TYPES)[number]

export const FACINGS = ['north', 'south', 'east', 'west'] as const
export type Facing = (typeof FACINGS)[number]

export const UNIT_STATUSES = ['available', 'held', 'booked', 'sold'] as const
export type UnitStatus = (typeof UNIT_STATUSES)[number]

export const BOOKING_STAGES = ['hold', 'booked', 'sold', 'cancelled'] as const
export type BookingStage = (typeof BOOKING_STAGES)[number]

export interface User {
  id: number
  name: string
  email: string
}

export interface BlockInfo {
  id: number
  name: string
  floors: number
}

export interface Project {
  id: number
  name: string
  city: string
  lat: number
  lng: number
  description: string | null
  blocks?: BlockInfo[]
  units_count?: number
  available_count?: number
  held_count?: number
  booked_count?: number
  sold_count?: number
}

export interface Unit {
  id: number
  unit_no: string
  floor: number
  type: UnitType
  facing: Facing
  area_sqft: number
  price: number
  status: UnitStatus
  block?: BlockInfo
}

export interface BookingEvent {
  from_stage: BookingStage | null
  to_stage: BookingStage
  note: string | null
  created_at: string
}

export interface Booking {
  id: number
  customer_name: string
  customer_phone: string
  stage: BookingStage
  price_snapshot: number
  hold_expires_at: string | null
  unit?: Unit & { block?: BlockInfo & { project?: { id: number; name: string } | null } }
  events?: BookingEvent[]
  created_at: string
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

export interface InventoryRow {
  id: number
  name: string
  total: number
  available: number
  held: number
  booked: number
  sold: number
  sold_value: number
}

export interface Funnel {
  holds: number
  confirmed: number
  sold: number
  cancelled: number
}
