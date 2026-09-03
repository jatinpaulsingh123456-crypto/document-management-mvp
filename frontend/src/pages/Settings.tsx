import {
  Bell,
  Check,
  CircleUserRound,
  LayoutGrid,
  List,
  LockKeyhole,
  Monitor,
  Moon,
  ShieldCheck,
  Sun,
} from 'lucide-react'
import { useEffect, useState } from 'react'
import { useAuth } from '../hooks/useAuth'

type Theme = 'system' | 'light' | 'dark'
type FileView = 'list' | 'grid'

export default function Settings() {
  const {
    user,
    isLoading,
  } = useAuth()

  const [theme, setTheme] = useState<Theme>(
    () =>
      (localStorage.getItem('app-theme') as Theme) ||
      'system',
  )

  const [fileView, setFileView] =
    useState<FileView>(
      () =>
        (localStorage.getItem(
          'default-file-view',
        ) as FileView) || 'list',
    )

  const [notifications, setNotifications] =
    useState(
      localStorage.getItem(
        'email-notifications',
      ) !== 'false',
    )

  useEffect(() => {
    localStorage.setItem('app-theme', theme)

    const root = document.documentElement

    const systemDark = window.matchMedia(
      '(prefers-color-scheme: dark)',
    ).matches

    const shouldUseDark =
      theme === 'dark' ||
      (theme === 'system' && systemDark)

    root.classList.toggle(
      'dark-theme',
      shouldUseDark,
    )

    root.dataset.theme =
      shouldUseDark ? 'dark' : 'light'
  }, [theme])

  useEffect(() => {
    localStorage.setItem(
      'default-file-view',
      fileView,
    )

    window.dispatchEvent(
      new Event('file-view-preference-changed'),
    )
  }, [fileView])

  useEffect(() => {
    localStorage.setItem(
      'email-notifications',
      String(notifications),
    )
  }, [notifications])

  useEffect(() => {
    if (theme !== 'system') return

    const media = window.matchMedia(
      '(prefers-color-scheme: dark)',
    )

    const updateTheme = () => {
      document.documentElement.classList.toggle(
        'dark-theme',
        media.matches,
      )

      document.documentElement.dataset.theme =
        media.matches ? 'dark' : 'light'
    }

    updateTheme()

    media.addEventListener('change', updateTheme)

    return () => {
      media.removeEventListener(
        'change',
        updateTheme,
      )
    }
  }, [theme])

  const initials = (
    user?.name ||
    user?.username ||
    'U'
  )
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('')

  return (
    <section className="settings-page">
      <div className="settings-page-header">
        <div>
          <span className="page-eyebrow">
            WORKSPACE
          </span>

          <h1>Settings</h1>

          <p>
            Manage your account and application preferences.
          </p>
        </div>
      </div>

      <div className="settings-layout">
        <div className="settings-main">
          <section className="settings-card">
            <div className="settings-card-header">
              <div className="settings-card-icon">
                <CircleUserRound size={18} />
              </div>

              <div>
                <h2>Account</h2>
                <p>
                  Your current account information.
                </p>
              </div>
            </div>

            {isLoading ? (
              <div className="settings-state">
                Loading account...
              </div>
            ) : (
              <div className="settings-account">
                <div className="settings-profile">
                  <div className="settings-avatar">
                    {initials}
                  </div>

                  <div>
                    <strong>
                      {user?.name ||
                        user?.username ||
                        '—'}
                    </strong>

                    <span>
                      {user?.email || '—'}
                    </span>
                  </div>
                </div>

                <div className="settings-fields">
                  <div>
                    <span>Name</span>
                    <strong>
                      {user?.name || '—'}
                    </strong>
                  </div>

                  <div>
                    <span>Username</span>
                    <strong>
                      {user?.username || '—'}
                    </strong>
                  </div>

                  <div>
                    <span>Role</span>
                    <strong>
                      {user?.role?.name ||
                        'Unassigned'}
                    </strong>
                  </div>

                  <div>
                    <span>Location</span>
                    <strong>
                      {user?.location?.name ||
                        '—'}
                    </strong>
                  </div>

                  <div>
                    <span>Department</span>
                    <strong>
                      {user?.department?.name ||
                        '—'}
                    </strong>
                  </div>

                  <div>
                    <span>Manager</span>
                    <strong>
                      {user?.manager?.name ||
                        '—'}
                    </strong>
                  </div>
                </div>
              </div>
            )}
          </section>

          <section className="settings-card">
            <div className="settings-card-header">
              <div className="settings-card-icon">
                <Monitor size={18} />
              </div>

              <div>
                <h2>Appearance</h2>
                <p>
                  Choose how the workspace looks.
                </p>
              </div>
            </div>

            <div className="settings-option">
              <div>
                <strong>Theme</strong>
                <span>
                  Choose your preferred interface theme.
                </span>
              </div>

              <div className="settings-theme-options">
                <button
                  type="button"
                  className={
                    theme === 'system'
                      ? 'selected'
                      : ''
                  }
                  onClick={() =>
                    setTheme('system')
                  }
                >
                  <Monitor size={14} />
                  System
                  {theme === 'system' && (
                    <Check size={13} />
                  )}
                </button>

                <button
                  type="button"
                  className={
                    theme === 'light'
                      ? 'selected'
                      : ''
                  }
                  onClick={() =>
                    setTheme('light')
                  }
                >
                  <Sun size={14} />
                  Light
                  {theme === 'light' && (
                    <Check size={13} />
                  )}
                </button>

                <button
                  type="button"
                  className={
                    theme === 'dark'
                      ? 'selected'
                      : ''
                  }
                  onClick={() =>
                    setTheme('dark')
                  }
                >
                  <Moon size={14} />
                  Dark
                  {theme === 'dark' && (
                    <Check size={13} />
                  )}
                </button>
              </div>
            </div>
          </section>

          <section className="settings-card">
            <div className="settings-card-header">
              <div className="settings-card-icon">
                <LayoutGrid size={18} />
              </div>

              <div>
                <h2>File preferences</h2>
                <p>
                  Set your preferred default file layout.
                </p>
              </div>
            </div>

            <div className="settings-option">
              <div>
                <strong>Default file view</strong>
                <span>
                  This preference is stored for this browser.
                </span>
              </div>

              <div className="settings-view-options">
                <button
                  type="button"
                  className={
                    fileView === 'list'
                      ? 'selected'
                      : ''
                  }
                  onClick={() =>
                    setFileView('list')
                  }
                >
                  <List size={14} />
                  List
                  {fileView === 'list' && (
                    <Check size={13} />
                  )}
                </button>

                <button
                  type="button"
                  className={
                    fileView === 'grid'
                      ? 'selected'
                      : ''
                  }
                  onClick={() =>
                    setFileView('grid')
                  }
                >
                  <LayoutGrid size={14} />
                  Grid
                  {fileView === 'grid' && (
                    <Check size={13} />
                  )}
                </button>
              </div>
            </div>
          </section>

          <section className="settings-card">
            <div className="settings-card-header">
              <div className="settings-card-icon">
                <Bell size={18} />
              </div>

              <div>
                <h2>Notifications</h2>
                <p>
                  Control application notification preferences.
                </p>
              </div>
            </div>

            <div className="settings-option">
              <div>
                <strong>Email notifications</strong>
                <span>
                  Receive email notifications for supported account events.
                </span>
              </div>

              <button
                type="button"
                className={`settings-switch ${
                  notifications ? 'on' : ''
                }`}
                aria-pressed={notifications}
                onClick={() =>
                  setNotifications(
                    (current) => !current,
                  )
                }
              >
                <span />
              </button>
            </div>
          </section>
        </div>

        <aside className="settings-sidebar">
          <section className="settings-side-card">
            <div className="settings-side-icon">
              <ShieldCheck size={19} />
            </div>

            <h3>Account security</h3>

            <p>
              Security permissions are controlled by your assigned role.
            </p>

            <div className="settings-security-row">
              <span>Authentication</span>
              <strong className="settings-ok">
                Active
              </strong>
            </div>

            <div className="settings-security-row">
              <span>Role</span>
              <strong>
                {user?.role?.name ||
                  'Unassigned'}
              </strong>
            </div>

            <div className="settings-security-row">
              <span>Access</span>
              <strong>
                {['CEO', 'MD', 'COUNTRY_HEAD'].includes(
                  user?.role?.code?.toUpperCase() || '',
                )
                  ? 'Organization-wide'
                  : 'Restricted'}
              </strong>
            </div>
          </section>

          <section className="settings-side-card settings-security-help">
            <LockKeyhole size={17} />

            <div>
              <strong>Security settings</strong>

              <p>
                Review detailed permissions and recent account activity from the Security page.
              </p>
            </div>
          </section>
        </aside>
      </div>
    </section>
  )
}
