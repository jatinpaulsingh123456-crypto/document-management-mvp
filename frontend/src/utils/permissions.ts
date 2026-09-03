import type { CurrentUser } from '../services/authService'

export type UserRole =
  | 'CEO'
  | 'MD'
  | 'COUNTRY_HEAD'
  | 'DEPARTMENT_HEAD'
  | 'EMPLOYEE'
  | 'AUDITOR'

export function getRole(
  user?: CurrentUser | null,
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

export function isGlobalRole(
  user?: CurrentUser | null,
): boolean {
  const role = getRole(user)

  return (
    role === 'CEO' ||
    role === 'MD' ||
    role === 'COUNTRY_HEAD'
  )
}

export function isAuditor(
  user?: CurrentUser | null,
): boolean {
  return getRole(user) === 'AUDITOR'
}

export function canUpload(
  user?: CurrentUser | null,
): boolean {
  const role = getRole(user)

  return (
    role === 'CEO' ||
    role === 'MD' ||
    role === 'COUNTRY_HEAD' ||
    role === 'DEPARTMENT_HEAD' ||
    role === 'EMPLOYEE'
  )
}

export function canDownload(
  user?: CurrentUser | null,
): boolean {
  return getRole(user) !== null
}

export function canRename(
  user?: CurrentUser | null,
): boolean {
  const role = getRole(user)

  return (
    role === 'CEO' ||
    role === 'MD' ||
    role === 'COUNTRY_HEAD' ||
    role === 'DEPARTMENT_HEAD' ||
    role === 'EMPLOYEE'
  )
}

export function canMove(
  user?: CurrentUser | null,
): boolean {
  const role = getRole(user)

  return (
    role === 'CEO' ||
    role === 'MD' ||
    role === 'COUNTRY_HEAD' ||
    role === 'DEPARTMENT_HEAD' ||
    role === 'EMPLOYEE'
  )
}

export function canDelete(
  user?: CurrentUser | null,
): boolean {
  const role = getRole(user)

  return (
    role === 'CEO' ||
    role === 'MD' ||
    role === 'COUNTRY_HEAD' ||
    role === 'DEPARTMENT_HEAD' ||
    role === 'EMPLOYEE'
  )
}

export function canShare(
  user?: CurrentUser | null,
): boolean {
  const role = getRole(user)

  return (
    role === 'CEO' ||
    role === 'MD' ||
    role === 'COUNTRY_HEAD' ||
    role === 'DEPARTMENT_HEAD' ||
    role === 'EMPLOYEE'
  )
}

export function canManageUsers(
  user?: CurrentUser | null,
): boolean {
  return isGlobalRole(user)
}

export function canManageLocations(
  user?: CurrentUser | null,
): boolean {
  const role = getRole(user)

  return (
    role === 'CEO' ||
    role === 'MD' ||
    role === 'COUNTRY_HEAD' ||
    role === 'DEPARTMENT_HEAD'
  )
}

export function isReadOnly(
  user?: CurrentUser | null,
): boolean {
  return isAuditor(user)
}
