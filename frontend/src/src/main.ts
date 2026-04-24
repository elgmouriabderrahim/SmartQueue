import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import './assets/main.css'
import { initEcho } from '@/services/realtime'
import { getStoredToken } from '@/utils/storage'

const app = createApp(App)

const token = getStoredToken()
if (token) {
	initEcho(token)
}

app.use(createPinia())
app.use(router)

app.mount('#app')
