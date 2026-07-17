import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { ApiError, get, post } from '../lib/api'
import type { Facing, Project, Unit, UnitType } from '../lib/types'
import { FACINGS, UNIT_TYPES } from '../lib/types'
import { cx, money } from '../lib/utils'
import { Card, EmptyState, Field, Modal, PrimaryButton, Spinner, STATUS_TONES, StatusBadge, inputClass, inputStyle } from '../components/ui'

export default function ProjectDetail() {
  const { id } = useParams()
  const [project, setProject] = useState<Project | null>(null)
  const [units, setUnits] = useState<Unit[] | null>(null)
  const [type, setType] = useState<UnitType | ''>('')
  const [facing, setFacing] = useState<Facing | ''>('')
  const [maxPrice, setMaxPrice] = useState('')
  const [holdTarget, setHoldTarget] = useState<Unit | null>(null)

  const loadGrid = useCallback(() => {
    const params = new URLSearchParams()
    if (type) params.set('type', type)
    if (facing) params.set('facing', facing)
    if (maxPrice) params.set('max_price', String(Number(maxPrice) * 100000))

    get<{ data: Unit[] }>(`/projects/${id}/grid?${params}`).then((res) => setUnits(res.data))
  }, [id, type, facing, maxPrice])

  useEffect(() => {
    get<{ data: Project }>(`/projects/${id}`).then((res) => setProject(res.data))
  }, [id])

  useEffect(loadGrid, [loadGrid])

  /** block name -> floor (desc) -> units */
  const grid = useMemo(() => {
    if (!units) return []
    const byBlock = new Map<string, Map<number, Unit[]>>()

    for (const unit of units) {
      const block = unit.block?.name ?? '?'
      if (!byBlock.has(block)) byBlock.set(block, new Map())
      const floors = byBlock.get(block)!
      if (!floors.has(unit.floor)) floors.set(unit.floor, [])
      floors.get(unit.floor)!.push(unit)
    }

    return [...byBlock.entries()]
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([block, floors]) => ({
        block,
        floors: [...floors.entries()].sort(([a], [b]) => b - a),
      }))
  }, [units])

  if (!project) return <Spinner />

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-3">
        <Link to="/projects" aria-label="Back to projects" className="rounded-lg p-1.5 hover:opacity-70" style={{ color: 'var(--text-secondary)' }}>
          <ArrowLeft size={17} aria-hidden />
        </Link>
        <div>
          <h1 className="text-lg font-semibold" style={{ color: 'var(--text-primary)' }}>
            {project.name}
          </h1>
          <p className="text-xs" style={{ color: 'var(--text-muted)' }}>
            {project.city} · {project.units_count} units · {project.available_count} available
          </p>
        </div>

        <div className="ml-auto flex flex-wrap items-end gap-2">
          <select value={type} onChange={(e) => setType(e.target.value as UnitType | '')} className={inputClass} style={{ ...inputStyle, width: 'auto' }} aria-label="Filter by type">
            <option value="">All types</option>
            {UNIT_TYPES.map((t) => (
              <option key={t}>{t}</option>
            ))}
          </select>
          <select value={facing} onChange={(e) => setFacing(e.target.value as Facing | '')} className={inputClass} style={{ ...inputStyle, width: 'auto' }} aria-label="Filter by facing">
            <option value="">All facings</option>
            {FACINGS.map((f) => (
              <option key={f} value={f}>
                {f}
              </option>
            ))}
          </select>
          <input
            type="number"
            min="0"
            value={maxPrice}
            onChange={(e) => setMaxPrice(e.target.value)}
            placeholder="Max ₹ (lakh)"
            className={cx(inputClass, 'tnum w-28')}
            style={inputStyle}
            aria-label="Max price in lakhs"
          />
        </div>
      </div>

      <div className="flex flex-wrap gap-3 text-xs" style={{ color: 'var(--text-secondary)' }}>
        {(['available', 'held', 'booked', 'sold'] as const).map((s) => (
          <span key={s} className="flex items-center gap-1.5 capitalize">
            <span className="h-3 w-3 rounded" style={{ background: STATUS_TONES[s].bg, border: `1px solid ${STATUS_TONES[s].ink}` }} />
            {s}
          </span>
        ))}
        <span className="ml-auto" style={{ color: 'var(--text-muted)' }}>
          Click an available unit to place a hold
        </span>
      </div>

      {!units ? (
        <Spinner />
      ) : grid.length === 0 ? (
        <EmptyState message="No units match these filters." />
      ) : (
        <div className="space-y-4">
          {grid.map(({ block, floors }) => (
            <Card key={block}>
              <h2 className="mb-2 text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>
                {block}
              </h2>
              <div className="space-y-1.5 overflow-x-auto">
                {floors.map(([floor, floorUnits]) => (
                  <div key={floor} className="flex items-center gap-1.5">
                    <span className="tnum w-8 shrink-0 text-xs" style={{ color: 'var(--text-muted)' }}>
                      F{floor}
                    </span>
                    {floorUnits.map((unit) => {
                      const tone = STATUS_TONES[unit.status]
                      const clickable = unit.status === 'available'
                      return (
                        <button
                          key={unit.id}
                          disabled={!clickable}
                          onClick={() => setHoldTarget(unit)}
                          title={`${unit.unit_no} · ${unit.type} · ${unit.facing} · ${unit.area_sqft} sqft · ${money(unit.price)} · ${unit.status}`}
                          className={cx(
                            'tnum min-w-16 rounded-md border px-2 py-1.5 text-xs font-medium',
                            clickable ? 'cursor-pointer hover:scale-105' : 'cursor-default opacity-80',
                          )}
                          style={{ background: tone.bg, color: tone.ink, borderColor: tone.ink }}
                        >
                          {unit.unit_no}
                          <span className="block text-[10px] font-normal">{unit.type}</span>
                        </button>
                      )
                    })}
                  </div>
                ))}
              </div>
            </Card>
          ))}
        </div>
      )}

      {holdTarget && (
        <HoldModal
          unit={holdTarget}
          onClose={() => setHoldTarget(null)}
          onHeld={() => {
            setHoldTarget(null)
            loadGrid()
          }}
        />
      )}
    </div>
  )
}

function HoldModal({ unit, onClose, onHeld }: { unit: Unit; onClose: () => void; onHeld: () => void }) {
  const [name, setName] = useState('')
  const [phone, setPhone] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  const submit = async (e: React.FormEvent) => {
    e.preventDefault()
    setBusy(true)
    setError('')

    try {
      await post(`/units/${unit.id}/hold`, { customer_name: name, customer_phone: phone })
      onHeld()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Hold failed.')
      setBusy(false)
    }
  }

  return (
    <Modal title={`Hold unit ${unit.unit_no}`} onClose={onClose}>
      <div className="mb-3 flex flex-wrap items-center gap-2 text-sm" style={{ color: 'var(--text-secondary)' }}>
        <StatusBadge status={unit.status} />
        <span>
          {unit.type} · {unit.facing} facing · {unit.area_sqft} sqft
        </span>
        <span className="tnum ml-auto font-semibold" style={{ color: 'var(--text-primary)' }}>
          {money(unit.price)}
        </span>
      </div>

      <form onSubmit={submit} className="space-y-3">
        <Field label="Customer name">
          <input required value={name} onChange={(e) => setName(e.target.value)} className={inputClass} style={inputStyle} />
        </Field>
        <Field label="Phone">
          <input required value={phone} onChange={(e) => setPhone(e.target.value)} className={inputClass} style={inputStyle} placeholder="98xxxxxxxx" />
        </Field>

        {error && (
          <p className="rounded-lg px-3 py-2 text-xs" style={{ background: 'var(--critical-bg)', color: 'var(--critical-ink)' }}>
            {error}
          </p>
        )}

        <p className="text-xs" style={{ color: 'var(--text-muted)' }}>
          The price above is snapshotted into the booking. Hold auto-expires in 48 hours unless confirmed.
        </p>

        <div className="flex justify-end gap-2 pt-1">
          <button type="button" onClick={onClose} className="rounded-lg px-4 py-2 text-sm" style={{ color: 'var(--text-secondary)' }}>
            Cancel
          </button>
          <PrimaryButton type="submit" disabled={busy}>
            {busy ? 'Placing hold…' : 'Place hold'}
          </PrimaryButton>
        </div>
      </form>
    </Modal>
  )
}
