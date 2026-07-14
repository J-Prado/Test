<script setup>
import { ref, onMounted } from 'vue';
import { client, auth } from '../api';
import { statusLabel } from '../format';

const card = ref('4242 4242 4242 4242');
const loading = ref(false);
const message = ref(null);
const subscription = ref(null);

onMounted(refresh);

async function refresh() {
    subscription.value = await client
        .get('/subscriptions/' + auth.user.id)
        .then((r) => r.data.subscription)
        .catch(() => null);
}

async function subscribe() {
    loading.value = true;
    message.value = null;
    const paymentMethod = card.value.replace(/\s+/g, '');
    try {
        await client.post('/payments/subscribe', { payment_method: paymentMethod, plan: 'monthly' });
        message.value = { type: 'ok', text: 'Pago aceptado. Confirmando suscripción…' };
        await pollUntilActive();
    } catch (e) {
        if (e.response?.status === 402) {
            message.value = { type: 'err', text: 'Tarjeta declinada. Prueba con otra.' };
        } else {
            message.value = { type: 'err', text: 'No se pudo procesar el pago.' };
        }
        await refresh();
    } finally {
        loading.value = false;
    }
}

// Poll a few times while Stripe confirms (webhook / auto-confirm).
async function pollUntilActive() {
    for (let i = 0; i < 6; i++) {
        await refresh();
        if (subscription.value?.status === 'active') {
            message.value = { type: 'ok', text: '¡Suscripción activa! Ya puedes agendar sesiones.' };
            return;
        }
        await new Promise((r) => setTimeout(r, 700));
    }
}
</script>

<template>
    <div class="wrap">
        <h1 class="page-title">Suscripción</h1>
        <p class="page-sub">Plan mensual de acceso a especialistas.</p>

        <div class="card checkout">
            <div class="pad">
                <div>
                    Estado actual:
                    <span class="badge" :class="subscription?.status || 'none'">
                        {{ subscription ? statusLabel(subscription.status) : 'Sin suscripción' }}
                    </span>
                </div>

                <div class="plan">
                    <span class="price">$19.99</span><span class="per">/ mes</span>
                </div>

                <div v-if="message" class="alert" :class="message.type">{{ message.text }}</div>

                <div class="field">
                    <label>Número de tarjeta</label>
                    <input v-model="card" inputmode="numeric" placeholder="4242 4242 4242 4242" />
                </div>

                <button class="btn block" :disabled="loading" @click="subscribe">
                    {{ loading ? 'Procesando…' : 'Suscribirme' }}
                </button>

                <div class="testcards">
                    Tarjetas de prueba (Stripe sandbox):<br />
                    ✅ Éxito: <code>4242 4242 4242 4242</code><br />
                    ⛔ Declinada: <code>4000 0000 0000 0002</code>
                </div>
            </div>
        </div>
    </div>
</template>
