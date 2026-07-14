<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { client } from '../api';

const props = defineProps({ id: { type: [String, Number], required: true } });
const router = useRouter();

const meeting = ref(null);
const error = ref('');
const camError = ref(false);
const micOn = ref(true);
const camOn = ref(true);
const localVideo = ref(null);

let stream = null;

onMounted(async () => {
    await createMeeting();
    await startCamera();
});

onBeforeUnmount(stopCamera);

async function createMeeting() {
    try {
        const { data } = await client.post('/video/meetings', { appointment_id: Number(props.id) });
        meeting.value = data;
    } catch (e) {
        error.value = e.response?.data?.error || 'No se pudo iniciar la videollamada';
    }
}

async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        if (localVideo.value) localVideo.value.srcObject = stream;
    } catch (e) {
        // Chime depends on real peripherals — surface permission/device failures.
        camError.value = true;
    }
}

function stopCamera() {
    if (stream) {
        stream.getTracks().forEach((t) => t.stop());
        stream = null;
    }
}

function toggleMic() {
    micOn.value = !micOn.value;
    stream?.getAudioTracks().forEach((t) => (t.enabled = micOn.value));
}

function toggleCam() {
    camOn.value = !camOn.value;
    stream?.getVideoTracks().forEach((t) => (t.enabled = camOn.value));
}

function leave() {
    stopCamera();
    router.push('/appointments');
}
</script>

<template>
    <div class="call">
        <div class="top">
            <span class="live"></span>
            <span v-if="meeting">En llamada · {{ meeting.Meeting?.MeetingId }}</span>
            <span v-else-if="error">{{ error }}</span>
            <span v-else>Conectando…</span>
        </div>

        <div class="stage">
            <div class="tile">
                <video v-show="camOn && !camError" ref="localVideo" autoplay playsinline muted></video>
                <div v-if="camError || !camOn" class="ph">
                    <div class="big">Tú</div>
                    <div>{{ camError ? 'Cámara no disponible' : 'Cámara apagada' }}</div>
                </div>
                <span class="label">Tú (paciente)</span>
            </div>
            <div class="tile">
                <div class="ph">
                    <div class="big">Dr</div>
                    <div>Esperando al especialista…</div>
                </div>
                <span class="label">Especialista</span>
            </div>
        </div>

        <div class="controls">
            <button class="ctrl" :class="{ off: !micOn }" @click="toggleMic" title="Micrófono">
                {{ micOn ? '🎤' : '🔇' }}
            </button>
            <button class="ctrl" :class="{ off: !camOn }" @click="toggleCam" title="Cámara">
                {{ camOn ? '📹' : '🚫' }}
            </button>
            <button class="ctrl leave" @click="leave" title="Salir">Salir</button>
        </div>
    </div>
</template>
