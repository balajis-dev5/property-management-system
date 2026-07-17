import { useCallback, useEffect, useState } from 'react'
import { ApiError, get, post } from '../lib/api'
import type { Booking, BookingStage, Paginated } from '../lib/types'
import { cx, dateTime, money } from '../lib/utils'
import { EmptyState, Modal, Spinner, StageBadge } from '../components/ui'

const TABS: { key: BookingStage | ''; label: string }[] = [
  { key: '', label: 'All' },
  { key: 'hold', label: 'Holds' },
  { key: 'booked', label: 'Booked' },
  { key: 'sold', label: 'Sold' },
  { key: 'cancelled', label: 'Cancelled' },
]

const ACTIONS: Record<string, { label: string; endpoint: string; tone: 'accent' | 'good' | 'critical' }[]> = {
  hold: [
    { label: 'Confirm booking', endpoint: 'confirm', tone: 'accent' },
    { label: 'Cancel', endpoint: 'cancel', tone: 'critical' },
  ],
  booked: [
    { label: 'Register sale', endpoint: 'complete', tone: 'good' },
    { label: 'Cancel', endpoint: 'cancel', tone: 'critical' },
  ],
}

export default function Bookings() {
  const [stage, setStage] = useState<BookingStage | ''>('')
  const [bookings, setBookings] = useState<Booking[] | null>(null)
  const [detail, setDetail] = useState<number | null>(null)
  const [error, setError] = useState('')

  const load = useCallback(() => {
    setBookings(null)
    get<Paginated<Booking>>(`/bookings?per_page=50${stage ? `&stage=${stage}` : ''}`).then((res) => setBookings(res.data))
  }, [stage])

  useEffect(load, [load])

  const act = async (booking: Booking, endpoint: string) => {
    setError('')
    try {
      await post(`/bookings/${booking.id}/${endpoint}`)
      load()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Action failed.')
    }
  }

  return (
    <div className="space-y-4">
      <h1 className="text-lg font-semibold" style={{ color: 'var(--text-primary)' }}>
        Bookings
      </h1>

      <div className="flex gap-1 rounded-lg border p-1" style={{ borderColor: 'var(--hairline)', background: 'var(--surface-card)', width: 'fit-content' }}>
        {TABS.map((t) => (
          <button
            key={t.key}
            onClick={() => setStage(t.key)}
            className="rounded-md px-3 py-1.5 text-sm font-medium"
            style={{
              background: stage === t.key ? 'var(--accent)' : 'transparent',
              color: stage === t.key ? 'var(--accent-ink)' : 'var(--text-secondary)',
            }}
          >
            {t.label}
          </button>
        ))}
      </div>

      {error && (
        <p className="rounded-lg px-3 py-2 text-xs" style={{ background: 'var(--critical-bg)', color: 'var(--critical-ink)' }}>
          {error}
        </p>
      )}

      {!bookings ? (
        <Spinner />
      ) : bookings.length === 0 ? (
        <EmptyState message="No bookings in this stage." />
      ) : (
        <div className="overflow-x-auto rounded-xl border" style={{ background: 'var(--surface-card)', borderColor: 'var(--hairline)' }}>
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b text-left text-xs uppercase tracking-wide" style={{ borderColor: 'var(--hairline)', color: 'var(--text-muted)' }}>
                <th className="px-4 py-2.5 font-medium">Customer</th>
                <th className="px-4 py-2.5 font-medium">Unit</th>
                <th className="px-4 py-2.5 font-medium">Project</th>
                <th className="px-4 py-2.5 font-medium">Price</th>
                <th className="px-4 py-2.5 font-medium">Stage</th>
                <th className="px-4 py-2.5 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              {bookings.map((b) => (
                <tr key={b.id} className="border-b last:border-0" style={{ borderColor: 'var(--hairline)' }}>
                  <td className="px-4 py-2.5">
                    <button onClick={() => setDetail(b.id)} className="font-medium underline-offset-2 hover:underline" style={{ color: 'var(--text-primary)' }}>
                      {b.customer_name}
                    </button>
                    <p className="tnum text-xs" style={{ color: 'var(--text-muted)' }}>
                      {b.customer_phone}
                    </p>
                  </td>
                  <td className="tnum px-4 py-2.5" style={{ color: 'var(--text-secondary)' }}>
                    {b.unit?.block?.name} · {b.unit?.unit_no} ({b.unit?.type})
                  </td>
                  <td className="px-4 py-2.5" style={{ color: 'var(--text-secondary)' }}>
                    {b.unit?.block?.project?.name}
                  </td>
                  <td className="tnum px-4 py-2.5 font-medium" style={{ color: 'var(--text-primary)' }}>
                    {money(b.price_snapshot)}
                  </td>
                  <td className="px-4 py-2.5">
                    <StageBadge stage={b.stage} />
                    {b.stage === 'hold' && b.hold_expires_at && (
                      <p className="tnum mt-0.5 text-[10px]" style={{ color: 'var(--text-muted)' }}>
                        expires {dateTime(b.hold_expires_at)}
                      </p>
                    )}
                  </td>
                  <td className="px-4 py-2.5">
                    <div className="flex gap-1.5">
                      {(ACTIONS[b.stage] ?? []).map((a) => (
                        <button
                          key={a.endpoint}
                          onClick={() => act(b, a.endpoint)}
                          className={cx('rounded-md border px-2 py-1 text-xs font-medium hover:opacity-80')}
                          style={{
                            borderColor: 'var(--hairline)',
                            color: a.tone === 'critical' ? 'var(--critical-ink)' : a.tone === 'good' ? 'var(--good-ink)' : 'var(--accent)',
                          }}
                        >
                          {a.label}
                        </button>
                      ))}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {detail !== null && <BookingDetail id={detail} onClose={() => setDetail(null)} />}
    </div>
  )
}

function BookingDetail({ id, onClose }: { id: number; onClose: () => void }) {
  const [booking, setBooking] = useState<Booking | null>(null)

  useEffect(() => {
    get<{ data: Booking }>(`/bookings/${id}`).then((res) => setBooking(res.data))
  }, [id])

  if (!booking) {
    return (
      <Modal title="Booking" onClose={onClose}>
        <Spinner />
      </Modal>
    )
  }

  return (
    <Modal title={`${booking.customer_name} — ${booking.unit?.unit_no}`} onClose={onClose}>
      <div className="space-y-4 text-sm">
        <div className="flex items-center gap-2">
          <StageBadge stage={booking.stage} />
          <span className="tnum ml-auto font-semibold" style={{ color: 'var(--text-primary)' }}>
            {money(booking.price_snapshot)}
          </span>
        </div>

        <div>
          <h3 className="mb-1.5 text-xs font-semibold uppercase tracking-wide" style={{ color: 'var(--text-muted)' }}>
            Audit trail
          </h3>
          <ul className="space-y-1.5">
            {booking.events?.map((e, i) => (
              <li key={i} className="flex items-center gap-2 text-xs" style={{ color: 'var(--text-secondary)' }}>
                <span className="tnum shrink-0" style={{ color: 'var(--text-muted)' }}>
                  {dateTime(e.created_at)}
                </span>
                <span className="capitalize">{e.from_stage ?? 'enquiry'}</span>
                <span aria-hidden>→</span>
                <span className="font-medium capitalize" style={{ color: 'var(--text-primary)' }}>
                  {e.to_stage}
                </span>
                {e.note && <span style={{ color: 'var(--text-muted)' }}>· {e.note}</span>}
              </li>
            ))}
          </ul>
        </div>
      </div>
    </Modal>
  )
}
