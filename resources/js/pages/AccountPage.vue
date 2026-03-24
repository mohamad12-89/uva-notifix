<template>
    <section class="mx-auto grid w-full max-w-2xl gap-6">
        <div class="card border-white/25 bg-gradient-to-br from-uva-blue/45 via-slate-900/65 to-uva-orange/15 p-7 shadow-[0_12px_30px_rgba(7,12,24,0.35)]">
            <h2 class="mb-1 text-center text-3xl font-bold text-uva-orange">Login</h2>
            <p class="mb-5 text-center text-sm text-slate-300">Access your Notifix account</p>
            <form class="space-y-3" @submit.prevent="login">
                <input v-model="loginForm.email" required class="input w-full" placeholder="Email" />
                <input v-model="loginForm.password" required class="input w-full" placeholder="Password" type="password" />
                <button class="button-secondary mt-2 w-full" type="submit">
                    Login
                </button>
            </form>
        </div>

        <p v-if="message" class="rounded-lg bg-green-100 p-3 text-center text-green-700">{{ message }}</p>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import { api } from '../lib/api';

const message = ref('');
const loginForm = ref({ email: '', password: '' });

const login = async () => {
    const response = await api.post('/auth/login', { ...loginForm.value });
    message.value = `Welcome back, ${response.data.name}.`;
    loginForm.value = { email: '', password: '' };
};
</script>
