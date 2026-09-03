import {
  createContext,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react'
import type { ReactNode } from 'react'
import {
  getCurrentUser,
  type CurrentUser,
} from '../services/authService'

export type UserRole =
  | 'CEO'
  | 'MD'
  | 'COUNTRY_HEAD'
  | 'DEPARTMENT_HEAD'
  | 'EMPLOYEE'
  | 'AUDITOR'

interface AuthContextValue {
  user: CurrentUser | null
  role: UserRole | null
  isLoading: boolean
  error: string | null
  isGlobal: boolean
  isAuditor: boolean
  isDepartmentHead: boolean
  isEmployee: boolean
  isReadOnly: boolean
  refreshUser: () => Promise<void>
}

const AuthContext =
  createContext<AuthContextValue | undefined>(undefined)

function normalizeRole(
  user: CurrentUser | null,
): UserRole | null {
  const code = user?.role?.code?.toUpperCase()

  if (
    code === 'CEO' ||
    code === 'MD' ||
    code === 'COUNTRY_HEAD' ||
    code === 'DEPARTMENT_HEAD' ||
    code === 'EMPLOYEE' ||
    code === 'AUDITOR'
  ) {
    return code
  }

  return null
}

export function AuthProvider({
  children,
}: {
  children: ReactNode
}) {
  const [user, setUser] =
    useState<CurrentUser | null>(null)

  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const refreshUser = async () => {
    const token = localStorage.getItem('auth_key')

    if (!token) {
      setUser(null)
      setError(null)
      setIsLoading(false)
      return
    }

    try {
      setIsLoading(true)
      setError(null)

      const currentUser = await getCurrentUser()

      setUser(currentUser)

      // Store only the complete server response.
      localStorage.setItem(
        'current_user',
        JSON.stringify(currentUser),
      )
    } catch (err) {
      console.error('Auth user load failed:', err)

      setUser(null)
      setError('Unable to load authenticated user.')
    } finally {
      setIsLoading(false)
    }
  }

  useEffect(() => {
    void refreshUser()

    const onAuthChanged = () => {
      void refreshUser()
    }

    window.addEventListener(
      'auth-changed',
      onAuthChanged,
    )

    return () => {
      window.removeEventListener(
        'auth-changed',
        onAuthChanged,
      )
    }
  }, [])

  const role = normalizeRole(user)

  const value = useMemo(
    () => ({
      user,
      role,
      isLoading,
      error,

      isGlobal:
        role === 'CEO' ||
        role === 'MD' ||
        role === 'COUNTRY_HEAD',

      isAuditor: role === 'AUDITOR',

      isDepartmentHead:
        role === 'DEPARTMENT_HEAD',

      isEmployee: role === 'EMPLOYEE',

      isReadOnly: role === 'AUDITOR',

      refreshUser,
    }),
    [user, role, isLoading, error],
  )

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error(
      'useAuth must be used inside AuthProvider',
    )
  }

  return context
}
