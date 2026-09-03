export type Theme = 'system' | 'light' | 'dark'

const THEME_KEY = 'app-theme'

export function getSavedTheme(): Theme {
  const saved = localStorage.getItem(THEME_KEY)

  if (
    saved === 'system' ||
    saved === 'light' ||
    saved === 'dark'
  ) {
    return saved
  }

  return 'system'
}

export function applyTheme(theme: Theme): void {
  const root = document.documentElement
  const media = window.matchMedia(
    '(prefers-color-scheme: dark)',
  )

  const isDark =
    theme === 'dark' ||
    (theme === 'system' && media.matches)

  root.classList.toggle('dark-theme', isDark)
  root.dataset.theme = isDark ? 'dark' : 'light'
}

export function saveTheme(theme: Theme): void {
  localStorage.setItem(THEME_KEY, theme)
  applyTheme(theme)
}
