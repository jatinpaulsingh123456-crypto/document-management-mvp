import { FileText, LockKeyhole, Mail } from 'lucide-react'
import { useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../services/api'

interface LoginResponse {
  success: boolean
  user: {
    id: number
    username: string
    email: string
    name: string
    role_id: number | null
    location_id: number | null
    department_id: number | null
  }
  auth_key: string
}

export default function Login() {
  const navigate = useNavigate()

  const [login, setLogin] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    if (!login.trim() || !password) {
      setError('Enter your username/email and password.')
      return
    }

    setError('')
    setLoading(true)

    try {
      const response = await api.post<LoginResponse>('/auth/login', {
        login: login.trim(),
        password,
      })

      const { auth_key } = response.data

      localStorage.setItem('auth_key', auth_key)
      localStorage.removeItem('current_user')
      localStorage.removeItem('user')

      window.dispatchEvent(
        new Event('auth-changed'),
      )

      navigate('/dashboard', { replace: true })
    } catch (err) {
      if (
        typeof err === 'object' &&
        err !== null &&
        'response' in err
      ) {
        const axiosError = err as {
          response?: {
            data?: {
              message?: string
            }
          }
        }

        setError(
          axiosError.response?.data?.message ||
            'Invalid username/email or password.',
        )
      } else {
        setError('Unable to connect to the server.')
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="login-page">
      <div className="login-card">
        <div className="login-brand">
          <div className="brand-mark">
            <FileText size={20} />
          </div>

          <div>
            <strong>DocVault</strong>
            <span>Enterprise Document Management</span>
          </div>
        </div>

        <div className="login-heading">
          <div className="eyebrow">Secure workspace</div>
          <h1>Sign in</h1>
          <p>Access your documents, folders and shared files.</p>
        </div>

        <form onSubmit={handleSubmit} className="login-form">
          <label>
            Work email or username

            <div className="input-wrap">
              <Mail size={17} />

              <input
                type="text"
                value={login}
                onChange={(event) => setLogin(event.target.value)}
                placeholder="you@company.com"
                autoComplete="username"
                disabled={loading}
              />
            </div>
          </label>

          <label>
            Password

            <div className="input-wrap">
              <LockKeyhole size={17} />

              <input
                type="password"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                placeholder="Enter your password"
                autoComplete="current-password"
                disabled={loading}
              />
            </div>
          </label>

          {error && (
            <div className="login-error" role="alert">
              {error}
            </div>
          )}

          <button
            className="primary-button login-button"
            type="submit"
            disabled={loading}
          >
            {loading ? 'Signing in...' : 'Sign in'}
          </button>
        </form>

        <p className="login-footer">
          Internal enterprise document management portal
        </p>
      </div>
    </div>
  )
}
