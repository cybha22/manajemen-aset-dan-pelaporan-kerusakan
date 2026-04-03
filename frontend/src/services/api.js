import axios from 'axios'

const api = axios.create({
    baseURL: '',
    headers: {
        Accept: 'application/json',
    },
})

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token')
    if (token) {
        config.headers['Authorization'] = 'Bearer ' + token
    }
    return config
})

export async function initCsrf() {
    try {
        await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
    } catch (e) { }
}

export default api
