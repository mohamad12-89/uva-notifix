import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import HomePage from './pages/HomePage.vue';
import OfficeHoursPage from './pages/OfficeHoursPage.vue';
import AppointmentsPage from './pages/AppointmentsPage.vue';
import TaBiosPage from './pages/TaBiosPage.vue';
import AccountPage from './pages/AccountPage.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', component: HomePage },
        { path: '/office-hours', component: OfficeHoursPage },
        { path: '/appointments', component: AppointmentsPage },
        { path: '/ta-bios', component: TaBiosPage },
        { path: '/account', component: AccountPage },
    ],
});

createApp(App).use(router).mount('#app');
