import axios from 'axios';
import { reactive } from 'vue';

// Shared, reactive auth state (token persisted across reloads).
export const auth = reactive({
    token: localStorage.getItem('oy_token') || null,
    user: null,
});

function setToken(token) {
    auth.token = token || null;
    if (token) localStorage.setItem('oy_token', token);
    else localStorage.removeItem('oy_token');
}

export const client = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json' },
});

client.interceptors.request.use((config) => {
    if (auth.token) config.headers.Authorization = 'Bearer ' + auth.token;
    return config;
});

export async function login(email, password) {
    const { data } = await client.post('/auth/login', { email, password });
    setToken(data.token);
    auth.user = data.user;
    return data;
}

export function register(payload) {
    return client.post('/auth/register', payload);
}

// Restore the session on boot if a token is present.
export async function restore() {
    if (!auth.token) return;
    try {
        const { data } = await client.get('/me');
        auth.user = data.user;
    } catch (e) {
        setToken(null);
        auth.user = null;
    }
}

export function logout() {
    client.post('/auth/logout').catch(() => {});
    setToken(null);
    auth.user = null;
}
