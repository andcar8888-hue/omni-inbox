import InboxLayout from './components/inbox/InboxLayout'
import LoginScreen from './components/auth/LoginScreen'
import { useAuth } from './auth/authContext'

function App() {
  const { isAuthenticated } = useAuth()

  return (
    <div className="h-screen bg-white">
      {isAuthenticated ? <InboxLayout /> : <LoginScreen />}
    </div>
  )
}

export default App
