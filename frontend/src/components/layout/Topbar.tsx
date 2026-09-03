import {
  Bell,
  Check,
  ChevronDown,
  File,
  Folder,
  LogOut,
  Search,
  Settings,
  ShieldCheck,
  Users,
} from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import api from '../../services/api'
import { useAuth } from '../../hooks/useAuth'

interface SearchResult {
  type: 'file' | 'folder' | 'user'
  id: number
  title: string
  subtitle: string
  path: string
}

interface TopbarUser {
  name: string
  username: string
  email: string
  role: string
}

export default function Topbar() {
  const navigate = useNavigate()
  const location = useLocation()
  const { user: authUser } = useAuth()

  const [query, setQuery] = useState('')
  const [results, setResults] =
    useState<SearchResult[]>([])
  const [searchOpen, setSearchOpen] =
    useState(false)
  const [searchLoading, setSearchLoading] =
    useState(false)

  const [notificationsOpen, setNotificationsOpen] =
    useState(false)

  const [profileOpen, setProfileOpen] =
    useState(false)

  const user: TopbarUser | null = authUser
    ? {
        name:
          authUser.name ||
          authUser.username,
        username: authUser.username,
        email: authUser.email,
        role:
          authUser.role?.name ||
          authUser.role?.code ||
          'User',
      }
    : null

  const searchRef = useRef<HTMLDivElement>(null)
  const notificationRef =
    useRef<HTMLDivElement>(null)
  const profileRef =
    useRef<HTMLDivElement>(null)
  const inputRef =
    useRef<HTMLInputElement>(null)


  useEffect(() => {
    const handleOutsideClick = (event: MouseEvent) => {
      const target = event.target as Node

      if (
        searchRef.current &&
        !searchRef.current.contains(target)
      ) {
        setSearchOpen(false)
      }

      if (
        notificationRef.current &&
        !notificationRef.current.contains(target)
      ) {
        setNotificationsOpen(false)
      }

      if (
        profileRef.current &&
        !profileRef.current.contains(target)
      ) {
        setProfileOpen(false)
      }
    }

    document.addEventListener(
      'mousedown',
      handleOutsideClick,
    )

    return () => {
      document.removeEventListener(
        'mousedown',
        handleOutsideClick,
      )
    }
  }, [])

  useEffect(() => {
    const handleKeyboard = (event: KeyboardEvent) => {
      if (
        (event.metaKey || event.ctrlKey) &&
        event.key.toLowerCase() === 'k'
      ) {
        event.preventDefault()
        inputRef.current?.focus()
        setSearchOpen(true)
      }

      if (event.key === 'Escape') {
        setSearchOpen(false)
        setNotificationsOpen(false)
        setProfileOpen(false)
      }
    }

    window.addEventListener(
      'keydown',
      handleKeyboard,
    )

    return () =>
      window.removeEventListener(
        'keydown',
        handleKeyboard,
      )
  }, [])

  useEffect(() => {
    const trimmed = query.trim()

    if (trimmed.length < 2) {
      setResults([])
      setSearchLoading(false)
      return
    }

    let cancelled = false

    const timer = window.setTimeout(async () => {
      try {
        setSearchLoading(true)

        const [filesResponse, foldersResponse, usersResponse] =
          await Promise.all([
            api.get<{
              success: boolean
              data: Array<{
                id: number
                original_name: string
                mime_type: string
                extension: string | null
              }>
            }>('/files'),

            api.get<{
              success: boolean
              data: Array<{
                id: number
                name: string
                folder_type: string
              }>
            }>('/folders'),

            api.get<{
              success: boolean
              data: Array<{
                id: number
                name: string
                username: string
                email: string
              }>
            }>('/users'),
          ])

        if (cancelled) return

        const q = trimmed.toLowerCase()

        const fileResults: SearchResult[] =
          filesResponse.data.data
            .filter((file) =>
              [
                file.original_name,
                file.mime_type,
                file.extension || '',
              ].some((value) =>
                value.toLowerCase().includes(q),
              ),
            )
            .slice(0, 6)
            .map((file) => ({
              type: 'file',
              id: file.id,
              title: file.original_name,
              subtitle:
                file.mime_type ||
                file.extension ||
                'File',
              path: '/files',
            }))

        const folderResults: SearchResult[] =
          foldersResponse.data.data
            .filter((folder) =>
              folder.name
                .toLowerCase()
                .includes(q),
            )
            .slice(0, 5)
            .map((folder) => ({
              type: 'folder',
              id: folder.id,
              title: folder.name,
              subtitle:
                folder.folder_type,
              path: '/files',
            }))

        const userResults: SearchResult[] =
          usersResponse.data.data
            .filter((account) =>
              [
                account.name,
                account.username,
                account.email,
              ].some((value) =>
                value
                  .toLowerCase()
                  .includes(q),
              ),
            )
            .slice(0, 5)
            .map((account) => ({
              type: 'user',
              id: account.id,
              title: account.name,
              subtitle: account.email,
              path: '/users',
            }))

        setResults([
          ...fileResults,
          ...folderResults,
          ...userResults,
        ])
      } catch (error) {
        if (!cancelled) {
          console.error(
            'Global search failed:',
            error,
          )
          setResults([])
        }
      } finally {
        if (!cancelled) {
          setSearchLoading(false)
        }
      }
    }, 250)

    return () => {
      cancelled = true
      window.clearTimeout(timer)
    }
  }, [query])

  function submitSearch() {
    const trimmed = query.trim()

    if (!trimmed) return

    localStorage.setItem(
      'global-search',
      trimmed,
    )

    window.dispatchEvent(
      new Event('global-search-changed'),
    )

    setSearchOpen(false)
    navigate('/files')
  }

  function selectResult(result: SearchResult) {
    localStorage.setItem(
      'global-search',
      query.trim(),
    )

    window.dispatchEvent(
      new Event('global-search-changed'),
    )

    setSearchOpen(false)

    if (result.type === 'user') {
      navigate('/users')
      return
    }

    navigate('/files')
  }

  function clearSearch() {
    setQuery('')

    localStorage.removeItem(
      'global-search',
    )

    window.dispatchEvent(
      new Event('global-search-changed'),
    )

    inputRef.current?.focus()
  }

  function handleLogout() {
    localStorage.removeItem('auth_key')
    localStorage.removeItem('global-search')

    setProfileOpen(false)

    navigate('/login', {
      replace: true,
    })
  }

  const displayName =
    user?.name || 'Jatin Paul Singh'

  const displayRole =
    user?.role || 'Employee'

  const initials = displayName
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) =>
      part.charAt(0).toUpperCase(),
    )
    .join('')

  return (
    <header className="topbar">
      <div
        className="search-box global-search-box"
        ref={searchRef}
      >
        <Search size={18} />

        <input
          ref={inputRef}
          type="search"
          value={query}
          placeholder="Search files, folders and people..."
          aria-label="Search files, folders and people"
          onFocus={() => setSearchOpen(true)}
          onChange={(event) => {
            setQuery(event.target.value)
            setSearchOpen(true)
          }}
          onKeyDown={(event) => {
            if (event.key === 'Enter') {
              submitSearch()
            }

            if (
              event.key === 'Escape'
            ) {
              setSearchOpen(false)
            }
          }}
        />

        {query && (
          <button
            className="global-search-clear"
            type="button"
            onClick={clearSearch}
            aria-label="Clear search"
          >
            ×
          </button>
        )}

        <kbd>⌘ K</kbd>

        {searchOpen &&
          query.trim().length >= 2 && (
            <div className="global-search-results">
              {searchLoading ? (
                <div className="global-search-state">
                  Searching...
                </div>
              ) : results.length === 0 ? (
                <div className="global-search-state">
                  <Search size={16} />
                  No results found
                </div>
              ) : (
                <>
                  {results.map((result) => {
                    const Icon =
                      result.type === 'file'
                        ? File
                        : result.type ===
                            'folder'
                          ? Folder
                          : Users

                    return (
                      <button
                        className="global-search-result"
                        key={`${result.type}-${result.id}`}
                        type="button"
                        onClick={() =>
                          selectResult(
                            result,
                          )
                        }
                      >
                        <span className="global-search-result-icon">
                          <Icon size={15} />
                        </span>

                        <span>
                          <strong>
                            {result.title}
                          </strong>

                          <small>
                            {result.subtitle}
                          </small>
                        </span>

                        <span className="global-search-result-type">
                          {result.type}
                        </span>
                      </button>
                    )
                  })}

                  <button
                    className="global-search-all"
                    type="button"
                    onClick={submitSearch}
                  >
                    <Search size={14} />
                    Search all results
                  </button>
                </>
              )}
            </div>
          )}
      </div>

      <div className="topbar-actions">
        <div
          className="topbar-menu-wrap"
          ref={notificationRef}
        >
          <button
            className={`icon-button topbar-action-button ${
              notificationsOpen
                ? 'active'
                : ''
            }`}
            type="button"
            aria-label="Notifications"
            onClick={() => {
              setNotificationsOpen(
                (current) => !current,
              )
              setProfileOpen(false)
            }}
          >
            <span className="notification-dot" />
            <Bell size={19} />
          </button>

          {notificationsOpen && (
            <div className="topbar-dropdown notification-dropdown">
              <div className="topbar-dropdown-header">
                <div>
                  <strong>
                    Notifications
                  </strong>
                  <span>
                    Workspace alerts
                  </span>
                </div>

                <Check size={15} />
              </div>

              <div className="notification-empty">
                <Bell size={21} />
                <strong>
                  No new notifications
                </strong>
                <span>
                  You're all caught up.
                </span>
              </div>
            </div>
          )}
        </div>

        <div className="topbar-divider" />

        <div
          className="topbar-menu-wrap"
          ref={profileRef}
        >
          <button
            className={`profile-button ${
              profileOpen ? 'active' : ''
            }`}
            type="button"
            onClick={() => {
              setProfileOpen(
                (current) => !current,
              )
              setNotificationsOpen(false)
            }}
          >
            <div className="avatar small">
              {initials || 'JP'}
            </div>

            <div className="profile-text">
              <span>
                {displayName}
              </span>

              <small>
                {displayRole}
              </small>
            </div>

            <ChevronDown
              size={16}
              className={
                profileOpen
                  ? 'profile-chevron-open'
                  : ''
              }
            />
          </button>

          {profileOpen && (
            <div className="topbar-dropdown profile-dropdown">
              <div className="profile-dropdown-user">
                <div className="avatar">
                  {initials || 'JP'}
                </div>

                <div>
                  <strong>
                    {displayName}
                  </strong>
                  <span>
                    {user?.email ||
                      'Signed in account'}
                  </span>
                </div>
              </div>

              <button
                type="button"
                onClick={() => {
                  setProfileOpen(false)
                  navigate('/settings')
                }}
              >
                <Settings size={15} />
                Settings
              </button>

              <button
                type="button"
                onClick={() => {
                  setProfileOpen(false)
                  navigate('/security')
                }}
              >
                <ShieldCheck size={15} />
                Security
              </button>

              <div className="topbar-dropdown-divider" />

              <button
                className="profile-logout"
                type="button"
                onClick={handleLogout}
              >
                <LogOut size={15} />
                Sign out
              </button>
            </div>
          )}
        </div>
      </div>

      {location.pathname ===
        '/login' && null}
    </header>
  )
}
