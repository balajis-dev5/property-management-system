import { useEffect, useState } from 'react'
import { get } from '../lib/api'
import type { Funnel, InventoryRow } from '../lib/types'
import { money } from '../lib/utils'
import { Card, Spinner, StatCard } from '../components/ui'

const STATUS_KEYS = ['available', 'held', 'booked', 'sold'] as const
const STATUS_COLORS: Record<(typeof STATUS_KEYS)[number], string> = {
  available: 'var(--chart-2)',
  held: 'var(--chart-3)',
  booked: 'var(--chart-1)',
  sold: 'var(--chart-6)',
}

export default function Dashboard() {
  const [inventory, setInventory] = useState<InventoryRow[] | null>(null)
  const [funnel, setFunnel] = useState<Funnel | null>(null)

  useEffect(() => {
    get<{ inventory: InventoryRow[]; funnel: Funnel }>('/analytics/summary').then((res) => {
      setInventory(res.inventory)
      setFunnel(res.funnel)
    })
  }, [])

  if (!inventory || !funnel) return <Spinner />

  const totals = inventory.reduce(
    (acc, row) => ({
      units: acc.units + Number(row.total),
      available: acc.available + Number(row.available),
      sold: acc.sold + Number(row.sold),
      soldValue: acc.soldValue + Number(row.sold_value),
    }),
    { units: 0, available: 0, sold: 0, soldValue: 0 },
  )

  return (
    <div className="space-y-5">
      <h1 className="text-lg font-semibold" style={{ color: 'var(--text-primary)' }}>
        Dashboard
      </h1>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatCard label="Total units" value={String(totals.units)} hint={`${inventory.length} projects`} />
        <StatCard label="Available" value={String(totals.available)} tone="good" />
        <StatCard label="Sold" value={String(totals.sold)} hint={money(totals.soldValue)} />
        <StatCard label="Active holds" value={String(funnel.holds - funnel.confirmed - funnel.cancelled)} hint="auto-expire in 48h" />
      </div>

      <Card>
        <h2 className="mb-3 text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>
          Inventory by project
        </h2>
        <div className="space-y-3">
          {inventory.map((row) => {
            const total = Number(row.total) || 1
            return (
              <div key={row.id}>
                <div className="mb-1 flex items-baseline justify-between text-xs">
                  <span className="font-medium" style={{ color: 'var(--text-primary)' }}>
                    {row.name}
                  </span>
                  <span className="tnum" style={{ color: 'var(--text-muted)' }}>
                    {row.total} units · {money(Number(row.sold_value))} sold
                  </span>
                </div>
                <div className="flex h-5 overflow-hidden rounded" role="img" aria-label={`${row.name} inventory split`}>
                  {STATUS_KEYS.map((key) => {
                    const value = Number(row[key])
                    if (!value) return null
                    return (
                      <div
                        key={key}
                        title={`${key}: ${value}`}
                        style={{ width: `${(value / total) * 100}%`, background: STATUS_COLORS[key] }}
                      />
                    )
                  })}
                </div>
              </div>
            )
          })}
        </div>
        <div className="mt-3 flex flex-wrap gap-3 text-xs" style={{ color: 'var(--text-secondary)' }}>
          {STATUS_KEYS.map((key) => (
            <span key={key} className="flex items-center gap-1.5 capitalize">
              <span className="h-2.5 w-2.5 rounded-sm" style={{ background: STATUS_COLORS[key] }} />
              {key}
            </span>
          ))}
        </div>
      </Card>

      <Card>
        <h2 className="mb-3 text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>
          Booking funnel
        </h2>
        <div className="space-y-2">
          {(
            [
              ['Holds placed', funnel.holds, 'var(--chart-1)'],
              ['Confirmed', funnel.confirmed, 'var(--chart-2)'],
              ['Sold', funnel.sold, 'var(--chart-4)'],
              ['Cancelled', funnel.cancelled, 'var(--chart-6)'],
            ] as const
          ).map(([label, value, color]) => {
            const max = Math.max(funnel.holds, 1)
            return (
              <div key={label} className="flex items-center gap-3">
                <span className="w-24 text-xs" style={{ color: 'var(--text-secondary)' }}>
                  {label}
                </span>
                <div className="h-5 flex-1 rounded" style={{ background: 'var(--surface-page)' }}>
                  <div className="flex h-5 min-w-7 items-center rounded px-2" style={{ width: `${Math.max((value / max) * 100, 5)}%`, background: color }}>
                    <span className="tnum text-xs font-medium text-white">{value}</span>
                  </div>
                </div>
              </div>
            )
          })}
        </div>
      </Card>
    </div>
  )
}
