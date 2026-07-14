<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { auth } from '../api';
import NavBar from './NavBar.vue';

const route = useRoute();
const showNav = computed(() => !!auth.user && !route.meta.guest);
</script>

<template>
    <NavBar v-if="showNav" />
    <router-view />
</template>
