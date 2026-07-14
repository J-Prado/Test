<script setup>
import { useRouter } from 'vue-router';
import { auth, logout } from '../api';

const router = useRouter();

function doLogout() {
    logout();
    router.push('/login');
}
</script>

<template>
    <nav class="nav">
        <router-link to="/" class="brand"><span class="logo"></span>Opción<span>Yo</span></router-link>
        <div class="links">
            <router-link to="/">Inicio</router-link>
            <router-link to="/specialists">Especialistas</router-link>
            <router-link to="/appointments">Mis citas</router-link>
            <router-link to="/subscribe">Suscripción</router-link>
        </div>
        <div class="spacer"></div>
        <span class="who">{{ auth.user?.email }}</span>
        <button class="btn ghost sm" @click="doLogout">Salir</button>
    </nav>
</template>
