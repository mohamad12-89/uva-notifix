import axios from 'axios';
import { getAccessToken } from "../composables/useAuthProfile";

export const api = axios.create({
    baseURL: '/api',
});

api.interceptors.request.use((config) => {
    const token = getAccessToken();
    if (token) {
        config.headers = config.headers || {};
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});
