function buildUrl(url, params = {}) {
    const target = new URL(url, window.location.origin)
    Object.entries(params || {}).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            target.searchParams.set(key, value)
        }
    })
    return target.pathname + target.search
}

async function parseResponse(response) {
    const contentType = response.headers.get('content-type') || ''
    if (contentType.includes('application/json')) {
        return response.json()
    }
    return response.text()
}

function buildHeaders(data, config = {}) {
    const headers = new Headers(config.headers || {})
    headers.set('Accept', headers.get('Accept') || 'application/json')

    const token = localStorage.getItem('token')
    if (token) {
        headers.set('Authorization', 'Bearer ' + token)
    }

    if (data && !(data instanceof FormData) && !headers.has('Content-Type')) {
        headers.set('Content-Type', 'application/json')
    }

    if (data instanceof FormData) {
        headers.delete('Content-Type')
    }

    return headers
}

async function request(method, url, data = null, config = {}) {
    const response = await fetch(buildUrl(url, config.params), {
        method,
        credentials: 'include',
        headers: buildHeaders(data, config),
        body: data instanceof FormData ? data : data ? JSON.stringify(data) : undefined,
    })
    const parsed = await parseResponse(response)

    if (response.status === 401) {
        localStorage.removeItem('token')
        if (window.location.pathname !== '/login') {
            window.location.href = '/login'
        }
    }

    if (!response.ok) {
        throw {
            response: {
                status: response.status,
                data: parsed,
                headers: response.headers,
            },
        }
    }

    return {
        data: parsed,
        status: response.status,
        headers: response.headers,
    }
}

const api = {
    get(url, config = {}) {
        return request('GET', url, null, config)
    },
    post(url, data = null, config = {}) {
        return request('POST', url, data, config)
    },
    put(url, data = null, config = {}) {
        return request('PUT', url, data, config)
    },
    delete(url, config = {}) {
        return request('DELETE', url, null, config)
    },
}

export async function initCsrf() {
    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
    } catch (e) { }
}

export default api
