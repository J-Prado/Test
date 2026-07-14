<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { client } from '../api';
import { formatDateTime } from '../format';

const specialists = ref([]);
const slots = ref([]);
const loading = ref(true);
const booking = ref(null);
const message = ref(null);
const router = useRouter();

onMounted(load);

async function load() {
    loading.value = true;
    try {
        const [sp, sl] = await Promise.all([
            client.get('/specialists').then((r) => r.data.specialists),
            client.get('/slots').then((r) => r.data.slots),
        ]);
        specialists.value = sp;
        slots.value = sl;
    } finally {
        loading.value = false;
    }
}

// group available/booked slots per specialist
const grouped = computed(() =>
    specialists.value.map((s) => ({
        ...s,
        slots: slots.value.filter((sl) => sl.specialist_id === s.id),
        initials: s.name.replace(/^(Dr|Dra)\.?\s*/i, '').split(' ').map((w) => w[0]).slice(0, 2).join(''),
    }))
);

async function book(slot) {
    booking.value = slot.id;
    message.value = null;
    try {
        await client.post('/appointments', { slot_id: slot.id });
        message.value = { type: 'ok', text: 'Cita agendada. Redirigiendo a tus citas…' };
        setTimeout(() => router.push('/appointments'), 900);
    } catch (e) {
        message.value = {
            type: 'err',
            text: e.response?.status === 409 ? 'Ese horario ya fue ocupado.' : 'No se pudo agendar.',
        };
        await load();
    } finally {
        booking.value = null;
    }
}
</script>

<template>
    <div class="wrap">
        <h1 class="page-title">Especialistas</h1>
        <p class="page-sub">Elige un horario disponible para agendar tu sesión.</p>

        <div v-if="message" class="alert" :class="message.type">{{ message.text }}</div>
        <p v-if="loading" class="muted">Cargando…</p>

        <div v-else class="grid cols-2">
            <div v-for="s in grouped" :key="s.id" class="card spec">
                <div class="top">
                    <div class="avatar">{{ s.initials }}</div>
                    <div>
                        <div class="name">{{ s.name }}</div>
                        <div class="role">{{ s.specialty }}</div>
                    </div>
                </div>
                <div class="slots">
                    <button
                        v-for="slot in s.slots"
                        :key="slot.id"
                        class="chip"
                        :disabled="slot.status !== 'available' || booking === slot.id"
                        @click="book(slot)"
                    >
                        <span class="dot" :class="slot.status"></span>
                        {{ formatDateTime(slot.starts_at) }}
                    </button>
                    <span v-if="!s.slots.length" class="muted">Sin horarios</span>
                </div>
            </div>
        </div>
    </div>
</template>
