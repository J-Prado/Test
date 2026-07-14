<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { login } from '../api';

const email = ref('paciente@opcionyo.test');
const password = ref('password123');
const error = ref('');
const loading = ref(false);
const router = useRouter();

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        await login(email.value, password.value);
        router.push('/');
    } catch (e) {
        error.value = e.response?.status === 401
            ? 'Credenciales inválidas'
            : 'No se pudo iniciar sesión';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="auth">
        <div class="hero">
            <h1>Tu bienestar,<br />en una videollamada</h1>
            <p>Conectamos pacientes con especialistas de salud y bienestar. Agenda, paga y conéctate en minutos.</p>
            <ul>
                <li>Especialistas verificados</li>
                <li>Sesiones por video seguras</li>
                <li>Suscripción flexible</li>
            </ul>
        </div>
        <div class="panel">
            <form class="form" @submit.prevent="submit">
                <h2>Iniciar sesión</h2>
                <p class="muted" style="margin: 0 0 18px">Bienvenido de vuelta.</p>
                <div v-if="error" class="alert err">{{ error }}</div>
                <div class="field">
                    <label>Email</label>
                    <input v-model="email" type="email" required autocomplete="username" />
                </div>
                <div class="field">
                    <label>Contraseña</label>
                    <input v-model="password" type="password" required autocomplete="current-password" />
                </div>
                <button class="btn block" :disabled="loading">
                    {{ loading ? 'Ingresando…' : 'Ingresar' }}
                </button>
                <div class="switch">¿No tienes cuenta? <router-link to="/register">Regístrate</router-link></div>
            </form>
        </div>
    </div>
</template>
