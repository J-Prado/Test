<script setup>
import { ref, onMounted } from 'vue';
import { client, auth } from '../api';
import { statusLabel } from '../format';

const subscription = ref(null);
const appointments = ref([]);
const loading = ref(true);

onMounted(load);

async function load() {
    loading.value = true;
    try {
        subscription.value = await client
            .get('/subscriptions/' + auth.user.id)
            .then((r) => r.data.subscription)
            .catch(() => null);
        appointments.value = await client.get('/appointments').then((r) => r.data.appointments);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="wrap">
        <h1 class="page-title">Hola, {{ auth.user?.name || auth.user?.email }} 👋</h1>
        <p class="page-sub">Tu espacio de bienestar.</p>

        <div class="grid cols-3">
            <div class="card">
                <div class="pad">
                    <h3>Suscripción</h3>
                    <p style="margin-bottom: 12px">Estado de tu plan.</p>
                    <span class="badge" :class="subscription?.status || 'none'">
                        {{ subscription ? statusLabel(subscription.status) : 'Sin suscripción' }}
                    </span>
                    <div style="margin-top: 14px">
                        <router-link class="btn sm secondary" to="/subscribe">Gestionar</router-link>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="pad">
                    <h3>Próximas citas</h3>
                    <p style="margin-bottom: 12px">Tienes {{ appointments.length }} cita(s) agendada(s).</p>
                    <router-link class="btn sm secondary" to="/appointments">Ver mis citas</router-link>
                </div>
            </div>

            <div class="card">
                <div class="pad">
                    <h3>Agendar sesión</h3>
                    <p style="margin-bottom: 12px">Explora especialistas disponibles.</p>
                    <router-link class="btn sm" to="/specialists">Buscar especialista</router-link>
                </div>
            </div>
        </div>
    </div>
</template>
