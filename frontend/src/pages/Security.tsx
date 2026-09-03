import {
  Activity,
  CheckCircle2,
  Clock3,
  Download,
  Eye,
  FilePenLine,
  LockKeyhole,
  ShieldCheck,
  UserCog,
} from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import { useAuth } from '../hooks/useAuth'
import api from '../services/api'

interface SecurityActivity {
  id: number
  action: string
  entity_type: string
  entity_id: number | null
  file_id: number | null
  folder_id: number | null
  ip_address: string | null
  user_agent: string | null
  created_at: string
}

async function getSecurityActivity(): Promise<SecurityActivity[]> {
  const response = await api.get<{
    success: boolean
    data: SecurityActivity[]
  }>('/security/activity')

  return response.data.data
}

function formatAction(action: string) {
  return action
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function formatEntity(entity: string) {
  return entity
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

export default function Security() {
  const {
    user,
    isLoading: userLoading,
  } = useAuth()

  const {
    data: activity = [],
    isLoading: activityLoading,
  } = useQuery({
    queryKey: ['security-activity'],
    queryFn: getSecurityActivity,
    refetchInterval: 5000,
    refetchOnWindowFocus: true,
    staleTime: 0,
  })

  const roleCode =
    user?.role?.code?.toUpperCase() ?? ''

  const isGlobal = [
    'CEO',
    'MD',
    'COUNTRY_HEAD',
  ].includes(roleCode)

  const accessItems = [
    {
      label: 'Global access',
      value: isGlobal,
      icon: ShieldCheck,
    },
    {
      label: 'View files',
      value: true,
      icon: Eye,
    },
    {
      label: 'Download files',
      value: true,
      icon: Download,
    },
    {
      label: 'Edit files',
      value: !['AUDITOR'].includes(roleCode),
      icon: FilePenLine,
    },
    {
      label: 'Manage files',
      value: !['EMPLOYEE', 'AUDITOR'].includes(roleCode),
      icon: UserCog,
    },
  ]

  return (
    <section className="security-page">
      <div className="security-page-header">
        <div>
          <span className="page-eyebrow">
            MANAGEMENT
          </span>

          <h1>Security</h1>

          <p>
            Review account security, access control and recent activity.
          </p>
        </div>

        <div className="security-status-badge">
          <CheckCircle2 size={15} />
          Authenticated
        </div>
      </div>

      <div className="security-grid">
        <section className="security-panel">
          <div className="security-panel-header">
            <div className="security-panel-icon">
              <LockKeyhole size={18} />
            </div>

            <div>
              <h2>Account security</h2>
              <p>
                Current authenticated account and scope.
              </p>
            </div>
          </div>

          {userLoading ? (
            <div className="security-state">
              Loading account...
            </div>
          ) : (
            <div className="security-account">
              <div className="security-avatar">
                {(user?.name || user?.username || 'U')
                  .split(' ')
                  .filter(Boolean)
                  .slice(0, 2)
                  .map((part) => part[0]?.toUpperCase())
                  .join('')}
              </div>

              <div className="security-account-main">
                <strong>
                  {user?.name || user?.username}
                </strong>

                <span>
                  {user?.email}
                </span>
              </div>

              <span className="security-role">
                {user?.role?.name || 'Unassigned'}
              </span>

              <div className="security-details">
                <div>
                  <small>Username</small>
                  <strong>
                    {user?.username || '—'}
                  </strong>
                </div>

                <div>
                  <small>Location</small>
                  <strong>
                    {user?.location?.name || '—'}
                  </strong>
                </div>

                <div>
                  <small>Department</small>
                  <strong>
                    {user?.department?.name || '—'}
                  </strong>
                </div>

                <div>
                  <small>Access level</small>
                  <strong>
                    {isGlobal
                      ? 'Organization-wide'
                      : user?.role?.name || 'Restricted'}
                  </strong>
                </div>
              </div>
            </div>
          )}
        </section>

        <section className="security-panel">
          <div className="security-panel-header">
            <div className="security-panel-icon">
              <ShieldCheck size={18} />
            </div>

            <div>
              <h2>Access control</h2>
              <p>
                Permissions derived from your current role.
              </p>
            </div>
          </div>

          <div className="security-access-list">
            {accessItems.map((item) => {
              const Icon = item.icon

              return (
                <div
                  className="security-access-item"
                  key={item.label}
                >
                  <div className="security-access-icon">
                    <Icon size={15} />
                  </div>

                  <span>{item.label}</span>

                  <strong
                    className={
                      item.value
                        ? 'allowed'
                        : 'restricted'
                    }
                  >
                    {item.value
                      ? 'Allowed'
                      : 'Restricted'}
                  </strong>
                </div>
              )
            })}
          </div>
        </section>
      </div>

      <section className="security-panel security-activity-panel">
        <div className="security-panel-header">
          <div className="security-panel-icon">
            <Activity size={18} />
          </div>

          <div>
            <h2>Recent activity</h2>
            <p>
              Latest security-relevant actions on your account.
            </p>
          </div>
        </div>

        {activityLoading ? (
          <div className="security-state">
            Loading activity...
          </div>
        ) : activity.length === 0 ? (
          <div className="security-empty">
            <Clock3 size={20} />
            <strong>No recent activity</strong>
            <span>
              Activity will appear here as you use the system.
            </span>
          </div>
        ) : (
          <div className="security-activity-list">
            {activity.map((item) => (
              <div
                className="security-activity-row"
                key={item.id}
              >
                <div className="security-activity-icon">
                  <Activity size={14} />
                </div>

                <div className="security-activity-main">
                  <strong>
                    {formatAction(item.action)}
                  </strong>

                  <span>
                    {formatEntity(item.entity_type)}
                    {item.entity_id
                      ? ` · #${item.entity_id}`
                      : ''}
                  </span>
                </div>

                <div className="security-activity-meta">
                  <span>
                    {item.created_at}
                  </span>

                  {item.ip_address && (
                    <small>
                      {item.ip_address}
                    </small>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </section>

      <div className="security-note">
        <ShieldCheck size={15} />
        <span>
          Security permissions are enforced by the backend authorization layer.
        </span>
      </div>
    </section>
  )
}
