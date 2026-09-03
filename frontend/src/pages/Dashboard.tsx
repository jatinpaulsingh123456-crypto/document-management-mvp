import {
  ArrowRight,
  File,
  FileText,
  Folder,
  Image,
  MoreHorizontal,
  PlaySquare,
  Upload,
} from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { getDashboard } from '../services/dashboardService'

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`
  }
  if (bytes < 1024 * 1024 * 1024) {
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
  }
  return `${(bytes / (1024 * 1024 * 1024)).toFixed(1)} GB`
}

function formatDate(value: string): string {
  const date = new Date(value.replace(' ', 'T'))

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return date.toLocaleString([], {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getFileIcon(mimeType: string) {
  if (mimeType.startsWith('image/')) return Image
  if (mimeType.startsWith('video/')) return PlaySquare
  if (
    mimeType.includes('pdf') ||
    mimeType.includes('word') ||
    mimeType.includes('text') ||
    mimeType.includes('markdown')
  ) {
    return FileText
  }

  return File
}

function getGreeting(): string {
  const hour = new Date().getHours()

  if (hour < 12) return 'Good morning'
  if (hour < 18) return 'Good afternoon'
  return 'Good evening'
}

export default function Dashboard() {
  const navigate = useNavigate()

  const {
    data,
    isLoading,
    isError,
  } = useQuery({
    queryKey: ['dashboard'],
    queryFn: getDashboard,
    refetchInterval: 3000,
    refetchOnWindowFocus: true,
    staleTime: 0,
  })

  if (isLoading) {
    return (
      <div className="dashboard">
        <section className="file-browser-state">
          Loading dashboard...
        </section>
      </div>
    )
  }

  if (isError || !data) {
    return (
      <div className="dashboard">
        <section className="file-browser-state error">
          Unable to load dashboard data.
        </section>
      </div>
    )
  }

  const { user, stats, folders, recentFiles } = data

  const storagePercent =
    stats.storageQuotaBytes > 0
      ? Math.min(
          100,
          (stats.storageUsedBytes / stats.storageQuotaBytes) * 100,
        )
      : 0

  return (
    <div className="dashboard">
      <section className="page-heading">
        <div>
          <div className="eyebrow">Workspace</div>
          <h1>
            {getGreeting()}, {user.name}
          </h1>
          <p>Manage your documents and media from one place.</p>
        </div>

      </section>

      <section className="stat-grid">
        <div className="stat-card">
          <div className="stat-icon blue">
            <File size={19} />
          </div>

          <div>
            <span>Total files</span>
            <strong>{stats.totalFiles}</strong>
          </div>

          <small>Visible to you</small>
        </div>

        <div className="stat-card">
          <div className="stat-icon violet">
            <Folder size={19} />
          </div>

          <div>
            <span>Folders</span>
            <strong>{stats.totalFolders}</strong>
          </div>

          <small>Visible to you</small>
        </div>

        <div className="stat-card">
          <div className="stat-icon green">
            <Upload size={19} />
          </div>

          <div>
            <span>Storage used</span>
            <strong>{formatBytes(stats.storageUsedBytes)}</strong>
          </div>

          <small>
            {storagePercent.toFixed(0)}% of available
          </small>
        </div>

        <div className="stat-card">
          <div className="stat-icon orange">
            <PlaySquare size={19} />
          </div>

          <div>
            <span>Shared with me</span>
            <strong>{stats.sharedWithMe}</strong>
          </div>

          <small>Active shares</small>
        </div>
      </section>

      <section className="content-grid">
        <div className="panel">
          <div className="panel-header">
            <div>
              <h2>Folders</h2>
              <p>Your folders</p>
            </div>

            <button
              className="text-button"
              type="button"
              onClick={() => navigate('/files')}
            >
              View all
              <ArrowRight size={15} />
            </button>
          </div>

          <div className="folder-list">
            {folders.length === 0 ? (
              <div className="file-browser-state">
                No folders available.
              </div>
            ) : (
              folders.map((folder) => (
                <button
                  className="folder-list-item"
                  key={folder.id}
                  type="button"
                  onClick={() => navigate('/files')}
                >
                  <div className="folder-list-icon">
                    <Folder size={18} />
                  </div>

                  <div className="folder-list-info">
                    <strong>{folder.name}</strong>
                    <span>
                      {folder.filesCount}{' '}
                      {folder.filesCount === 1 ? 'file' : 'files'}
                    </span>
                  </div>

                  <MoreHorizontal size={18} />
                </button>
              ))
            )}
          </div>
        </div>

        <div className="panel activity-panel">
          <div className="panel-header">
            <div>
              <h2>Recent activity</h2>
              <p>Your latest files</p>
            </div>

            <button
              className="text-button"
              type="button"
              onClick={() => navigate('/files')}
            >
              View all
              <ArrowRight size={15} />
            </button>
          </div>

          <div className="file-list">
            {recentFiles.length === 0 ? (
              <div className="file-browser-state">
                No recent files.
              </div>
            ) : (
              recentFiles.map((item) => {
                const Icon = getFileIcon(item.mimeType)

                return (
                  <button
                    className="recent-file"
                    key={item.id}
                    type="button"
                    onClick={() => navigate('/files')}
                  >
                    <div className="file-icon">
                      <Icon size={18} />
                    </div>

                    <div className="recent-file-info">
                      <strong>{item.originalName}</strong>
                      <span>
                        {item.mimeType || 'File'} ·{' '}
                        {formatBytes(item.sizeBytes)}
                      </span>
                    </div>

                    <span className="recent-date">
                      {formatDate(item.uploadedAt)}
                    </span>
                  </button>
                )
              })
            )}
          </div>
        </div>
      </section>

      <section
        className="quick-upload"
        role="button"
        tabIndex={0}
        onClick={() => navigate('/files?upload=1')}
        onKeyDown={(event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault()
            navigate('/files')
          }
        }}
      >
        <div className="quick-upload-icon">
          <Upload size={23} />
        </div>

        <div>
          <h2>Upload documents and media</h2>
          <p>
            Upload PDFs, office documents, images and videos to your
            workspace.
          </p>
        </div>

        <button
          className="secondary-button"
          type="button"
          onClick={() => navigate('/files')}
        >
          Choose files
        </button>
      </section>

      <div className="dashboard-storage-sync">
        <div>
          Storage
          <strong>
            {formatBytes(stats.storageUsedBytes)} of{' '}
            {formatBytes(stats.storageQuotaBytes)}
          </strong>
        </div>

        <div className="dashboard-storage-progress">
          <span style={{ width: `${storagePercent}%` }} />
        </div>
      </div>
    </div>
  )
}
