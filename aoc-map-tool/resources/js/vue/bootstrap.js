import { createApp } from 'vue'
import { createPinia } from 'pinia'
import client from './core/api/client.js'

const pinia = createPinia()
window.axios = client

const mountFiles = require.context('./modules', true, /mount\.js$/)

mountFiles.keys().forEach((path) => {
    const mounts = mountFiles(path).default

    mounts.forEach(({ selector, component }) => {
        const el = document.querySelector(selector)
        if (!el) return

        const app = createApp(component)
        app.use(pinia)
        app.mount(el)
    })
})
