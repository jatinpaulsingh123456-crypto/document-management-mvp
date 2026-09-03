import {
  BarChart3,
  Clock3,
  FolderOpen,
  HardDrive,
  Settings,
  Share2,
  ShieldCheck,
  Users,
} from 'lucide-react'
import { NavLink } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { getDashboard } from '../../services/dashboardService'
import { useAuth } from '../../hooks/useAuth'
import {
  canManageLocations,
  canManageUsers,
} from '../../utils/permissions'

const navigation = [
  { label: 'Dashboard', icon: BarChart3, to: '/' },
  { label: 'My Files', icon: FolderOpen, to: '/files' },
  { label: 'Locations', icon: HardDrive, to: '/locations' },
  { label: 'Shared', icon: Share2, to: '/shared' },
  { label: 'Recent', icon: Clock3, to: '/recent' },
]

const management = [
  {
    label: 'Users',
    icon: Users,
    to: '/users',
    permission: canManageUsers,
  },
  {
    label: 'Security',
    icon: ShieldCheck,
    to: '/security',
    permission: () => true,
  },
  {
    label: 'Settings',
    icon: Settings,
    to: '/settings',
    permission: () => true,
  },
]

function formatRole(role?: string | null) {
  if (!role) return 'User'

  return role
    .split('_')
    .map(
      (part) =>
        part.charAt(0) + part.slice(1).toLowerCase(),
    )
    .join(' ')
}

function formatStorage(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`
  }
  if (bytes < 1024 * 1024 * 1024) {
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
  }
  return `${(bytes / (1024 * 1024 * 1024)).toFixed(1)} GB`
}

function getStoragePercent(
  dashboard:
    | Awaited<ReturnType<typeof getDashboard>>
    | undefined,
): number {
  if (!dashboard) return 0

  const quota = dashboard.stats.storageQuotaBytes

  if (quota <= 0) return 0

  return Math.min(
    100,
    (dashboard.stats.storageUsedBytes / quota) * 100,
  )
}

export default function Sidebar() {
  const { user, isLoading } = useAuth()

  const { data: dashboard } = useQuery({
    queryKey: ['dashboard'],
    queryFn: getDashboard,
    staleTime: 30000,
  })

  const visibleManagement = management.filter((item) =>
    item.permission(user),
  )

  const firstName =
    user?.name ||
    user?.username ||
    'User'

  const initials = firstName
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('')

  return (
    <aside className="app-sidebar">
      <div className="brand">
        <div className="brand-mark">D</div>

        <div>
          <div className="brand-name">DocVault</div>
          <div className="brand-subtitle">
            Enterprise Drive
          </div>
        </div>
      </div>

      <div className="sidebar-section">
        <div className="sidebar-label">Workspace</div>

        <nav className="sidebar-nav">
          {navigation.map((item) => {
            const Icon = item.icon

            const hideLocations =
              item.to === '/locations' &&
              !canManageLocations(user)

            if (hideLocations) {
              return null
            }

            return (
              <NavLink
                key={item.to}
                to={item.to}
                end={item.to === '/'}
                className={({ isActive }) =>
                  `sidebar-link ${
                    isActive ? 'active' : ''
                  }`
                }
              >
                <Icon size={18} strokeWidth={1.8} />
                <span>{item.label}</span>
              </NavLink>
            )
          })}
        </nav>
      </div>

      <div className="sidebar-section management-section">
        <div className="sidebar-label">Management</div>

        <nav className="sidebar-nav">
          {visibleManagement.map((item) => {
            const Icon = item.icon

            return (
              <NavLink
                key={item.to}
                to={item.to}
                className={({ isActive }) =>
                  `sidebar-link ${
                    isActive ? 'active' : ''
                  }`
                }
              >
                <Icon size={18} strokeWidth={1.8} />
                <span>{item.label}</span>
              </NavLink>
            )
          })}
        </nav>
      </div>

      <div className="storage-card">
        <div className="storage-icon">
          <FolderOpen size={17} />
        </div>

        <div className="storage-content">
          <div className="storage-title">Storage</div>
          <div className="storage-value">
            {dashboard
              ? `${formatStorage(
                  dashboard.stats.storageUsedBytes,
                )} of ${formatStorage(
                  dashboard.stats.storageQuotaBytes,
                )}`
              : 'Loading...'}
          </div>

          <div className="storage-progress">
            <span
              style={{
                width: `${getStoragePercent(dashboard)}%`,
              }}
            />
          </div>
        </div>
      </div>

      <div className="sidebar-user">
        <div className="avatar">{initials || 'U'}</div>

        <div className="sidebar-user-info">
          <div className="sidebar-user-name">
            {isLoading
              ? 'Loading...'
              : firstName}
          </div>

          <div className="sidebar-user-role">
            {isLoading
              ? ' '
              : formatRole(user?.role?.code)}
          </div>
        </div>
      </div>
    </aside>
  )
}
