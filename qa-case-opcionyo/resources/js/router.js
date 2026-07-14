import { createRouter, createWebHistory } from 'vue-router';
import { auth } from './api';

import Login from './components/Login.vue';
import Register from './components/Register.vue';
import Dashboard from './components/Dashboard.vue';
import Specialists from './components/Specialists.vue';
import Appointments from './components/Appointments.vue';
import Subscribe from './components/Subscribe.vue';
import Call from './components/Call.vue';

const routes = [
    { path: '/login', component: Login, meta: { guest: true } },
    { path: '/register', component: Register, meta: { guest: true } },
    { path: '/', component: Dashboard },
    { path: '/specialists', component: Specialists },
    { path: '/appointments', component: Appointments },
    { path: '/subscribe', component: Subscribe },
    { path: '/call/:id', component: Call, props: true },
];

const router = createRouter({
    history: createWebHistory(),
    linkExactActiveClass: 'active',
    routes,
});

// Route guard: protect app routes, keep guests out of auth pages.
router.beforeEach((to) => {
    if (!to.meta.guest && !auth.token) return '/login';
    if (to.meta.guest && auth.token) return '/';
    return true;
});

export default router;
