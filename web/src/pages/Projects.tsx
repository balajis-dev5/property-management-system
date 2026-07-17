import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { MapContainer, Marker, Popup, TileLayer } from 'react-leaflet'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'
import { get } from '../lib/api'
import type { Project } from '../lib/types'
import { money } from '../lib/utils'
import { Card, Spinner } from '../components/ui'

// Vite bundles the images; Leaflet's default icon paths don't survive that.
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
})

export default function Projects() {
  const [projects, setProjects] = useState<Project[] | null>(null)

  useEffect(() => {
    get<{ data: Project[] }>('/projects').then((res) => setProjects(res.data))
  }, [])

  if (!projects) return <Spinner />

  const center: [number, number] = projects.length
    ? [
        projects.reduce((s, p) => s + p.lat, 0) / projects.length,
        projects.reduce((s, p) => s + p.lng, 0) / projects.length,
      ]
    : [13.05, 80.25]

  return (
    <div className="space-y-4">
      <h1 className="text-lg font-semibold" style={{ color: 'var(--text-primary)' }}>
        Projects
      </h1>

      <Card className="overflow-hidden p-0">
        <MapContainer center={center} zoom={11} style={{ height: '340px', width: '100%' }} scrollWheelZoom={false}>
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            url="https://tile.openstreetmap.org/{z}/{x}/{y}.png"
          />
          {projects.map((p) => (
            <Marker key={p.id} position={[p.lat, p.lng]}>
              <Popup>
                <strong>{p.name}</strong>
                <br />
                {p.available_count} of {p.units_count} units available
                <br />
                <Link to={`/projects/${p.id}`}>Open availability grid →</Link>
              </Popup>
            </Marker>
          ))}
        </MapContainer>
      </Card>

      <div className="grid gap-4 md:grid-cols-3">
        {projects.map((p) => {
          const soldPct = p.units_count ? Math.round(((p.sold_count ?? 0) / p.units_count) * 100) : 0
          return (
            <Link key={p.id} to={`/projects/${p.id}`}>
              <Card className="h-full transition-transform hover:-translate-y-0.5">
                <div className="flex items-baseline justify-between">
                  <h2 className="font-semibold" style={{ color: 'var(--text-primary)' }}>
                    {p.name}
                  </h2>
                  <span className="text-xs" style={{ color: 'var(--text-muted)' }}>
                    {p.city}
                  </span>
                </div>
                <p className="mt-1 line-clamp-2 text-xs" style={{ color: 'var(--text-secondary)' }}>
                  {p.description}
                </p>

                <div className="tnum mt-3 flex gap-4 text-xs" style={{ color: 'var(--text-secondary)' }}>
                  <span>
                    <strong style={{ color: 'var(--good-ink)' }}>{p.available_count}</strong> available
                  </span>
                  <span>
                    <strong style={{ color: 'var(--chart-1)' }}>{p.booked_count}</strong> booked
                  </span>
                  <span>
                    <strong style={{ color: 'var(--critical-ink)' }}>{p.sold_count}</strong> sold
                  </span>
                </div>

                <div className="mt-2 h-1.5 overflow-hidden rounded-full" style={{ background: 'var(--surface-page)' }}>
                  <div className="h-full rounded-full" style={{ width: `${soldPct}%`, background: 'var(--accent)' }} />
                </div>
                <p className="tnum mt-1 text-xs" style={{ color: 'var(--text-muted)' }}>
                  {soldPct}% sold · {p.blocks?.length} blocks · {p.units_count} units
                </p>
              </Card>
            </Link>
          )
        })}
      </div>
      <p className="text-xs" style={{ color: 'var(--text-muted)' }}>
        Map: OpenStreetMap tiles — no API key needed. Prices from {money(3_575_000)}.
      </p>
    </div>
  )
}
