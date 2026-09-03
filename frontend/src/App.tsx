import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import AppShell from './components/layout/AppShell'
import Dashboard from './pages/Dashboard'
import Locations from './pages/Locations'
import SharedFiles from './pages/SharedFiles'
import RecentFiles from './pages/RecentFiles'
import Users from './pages/Users'
import Security from './pages/Security'
import Settings from './pages/Settings'
import Login from './pages/Login'
import FileBrowser from './components/files/FileBrowser'


function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<Login />} />

        <Route element={<AppShell />}>
          <Route path="/" element={<Dashboard />} />
          <Route path="/dashboard" element={<Dashboard />} />
          <Route path="/files" element={<FileBrowser />} />
          <Route path="/locations" element={<Locations />} />
          <Route path="/shared" element={<SharedFiles />} />
          <Route path="/recent" element={<RecentFiles />} />
          <Route path="/users" element={<Users />} />
          <Route path="/security" element={<Security />} />
          <Route path="/settings" element={<Settings />} />
        </Route>

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  )
}

export default App
