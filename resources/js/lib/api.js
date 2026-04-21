import axios from 'axios';
import { getIdToken, getAccessToken } from "../composables/useAuthProfile";

export const api = axios.create({
    baseURL: '/api',
});

api.interceptors.request.use((config) => {
    const token = getIdToken() || getAccessToken();
    if (token) {
        config.headers = config.headers || {};
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});
