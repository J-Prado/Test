<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { client } from '../api';
import { formatDateTime } from '../format';

const appointments = ref([]);
const loading = ref(true);
const working = ref(null);
const router = useRouter();

onMounted(load);

async function load() {
    loading.value = true;
    try {
        appointments.value = await client.get('/appointments').then((r) => r.data.appointments);
    } finally {
        loading.value = false;
    }
}

async function cancel(appt) {
    working.value = appt.id;
    try {
        await client.delete('/appointments/' + appt.id);
        await load();
    } finally {
        working.value = null;
    }
}

function join(appt) {
    router.push('/call/' + appt.id);
}
</script>

<template>
    <div class="wrap">
        <h1 class="page-title">Mis citas</h1>
        <p class="page-sub">Únete a la videollamada o cancela para liberar el horario.</p>

        <p v-if="loading" class="muted">Cargando…</p>

        <div v-else-if="!appointments.length" class="card">
            <div class="empty">
                No tienes citas agendadas.<br />
                <router-link class="btn sm" style="margin-top: 12px" to="/specialists">Agendar una sesión</router-link>
            </div>
        </div>

        <div v-else class="card">
            <div v-for="appt in appointments" :key="appt.id" class="appt">
                <div>
                    <div class="when">{{ formatDateTime(appt.slot?.starts_at) }}</div>
                    <div class="meta">{{ appt.slot?.specialist?.name }} · {{ appt.slot?.specialist?.specialty }}</div>
                </div>
                <div class="actions">
                    <button class="btn sm" @click="join(appt)">Unirse</button>
                    <button class="btn sm danger" :disabled="working === appt.id" @click="cancel(appt)">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
