import axios from 'axios'

const client = axios.create({
    baseURL: '/',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
})

const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content
if (csrfToken) {
    client.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken
}

client.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) window.location.href = '/login'
        if (error.response?.status === 419) window.location.reload()
        return Promise.reject(error)
    }
)

export default client
