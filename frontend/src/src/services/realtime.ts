import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

let echoInstance: Echo<'pusher'> | null = null

function apiOrigin(): string {
  const apiUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'
  return apiUrl.replace(/\/api\/?$/, '')
}

export function initEcho(token: string): Echo<'pusher'> | null {
  const key = import.meta.env.VITE_PUSHER_APP_KEY
  if (!key) {
    return null
  }

  if (echoInstance) {
    return echoInstance
  }

  ;(window as any).Pusher = Pusher

  echoInstance = new Echo({
    broadcaster: 'pusher',
    key,
    wsHost: import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
    wsPort: Number(import.meta.env.VITE_PUSHER_PORT || 6001),
    wssPort: Number(import.meta.env.VITE_PUSHER_PORT || 6001),
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
    authEndpoint: `${apiOrigin()}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  })

  return echoInstance
}

export function getEcho(): Echo<'pusher'> | null {
  return echoInstance
}

export function disconnectEcho(): void {
  if (!echoInstance) {
    return
  }

  echoInstance.disconnect()
  echoInstance = null
}
