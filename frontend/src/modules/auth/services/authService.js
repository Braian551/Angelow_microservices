import apiClient from '../../../services/api';

export const authService = {
    async login(credentials) {
        const response = await apiClient.post('/auth/login', credentials);
        return response.data;
    },

    async register(userData) {
        const response = await apiClient.post('/auth/register', userData);
        return response.data;
    },

    async logout() {
        const response = await apiClient.post('/auth/logout');
        localStorage.removeItem('auth_token');
        return response.data;
    },

    async getUser() {
        const response = await apiClient.get('/auth/me');
        return response.data;
    }
};
