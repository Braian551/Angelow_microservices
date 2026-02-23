import axios from 'axios';

/**
 * Axios instance configured for the Auth API.
 * Base URL comes from environment variable.
 */
const apiClient = axios.create({
    baseURL: import.meta.env.VITE_AUTH_API_URL || 'http://localhost:8001/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    timeout: 10000,
});

// Request interceptor: attach token if available
apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Response interceptor: handle common errors
apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token');
        }
        return Promise.reject(error);
    }
);

export default apiClient;
