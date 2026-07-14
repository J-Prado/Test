<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { register, login } from '../api';

const name = ref('');
const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);
const router = useRouter();

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        await register({ name: name.value, email: email.value, password: password.value });
        await login(email.value, password.value);
        router.push('/');
    } catch (e) {
        const errors = e.response?.data?.errors;
        error.value = errors
            ? Object.values(errors).flat().join(' ')
            : 'No se pudo crear la cuenta';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="auth">
        <div class="hero">
            <h1>Empieza a cuidarte<br />hoy mismo</h1>
            <p>Crea tu cuenta y accede a especialistas de bienestar por videollamada.</p>
            <ul>
                <li>Registro en segundos</li>
                <li>Cancela cuando quieras</li>
                <li>Datos protegidos</li>
            </ul>
        </div>
        <div class="panel">
            <form class="form" @submit.prevent="submit">
                <h2>Crear cuenta</h2>
                <p class="muted" style="margin: 0 0 18px">Es gratis registrarse.</p>
                <div v-if="error" class="alert err">{{ error }}</div>
                <div class="field">
                    <label>Nombre</label>
                    <input v-model="name" type="text" autocomplete="name" />
                </div>
                <div class="field">
                    <label>Email</label>
                    <input v-model="email" type="email" required autocomplete="username" />
                </div>
                <div class="field">
                    <label>Contraseña (mín. 8)</label>
                    <input v-model="password" type="password" required minlength="8" autocomplete="new-password" />
                </div>
                <button class="btn block" :disabled="loading">
                    {{ loading ? 'Creando…' : 'Crear cuenta' }}
                </button>
                <div class="switch">¿Ya tienes cuenta? <router-link to="/login">Inicia sesión</router-link></div>
            </form>
        </div>
    </div>
</template>
