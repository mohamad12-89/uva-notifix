<template>
    <section class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-3xl font-bold text-uva-orange">TA Bios</h2>
            <button class="button-secondary" @click="showForm = !showForm">
                Add TA Bio
            </button>
        </div>

        <form v-if="showForm" class="card grid gap-3 p-6 md:grid-cols-2" @submit.prevent="submit">
            <input v-model="form.name" required class="input" placeholder="Name" />
            <input v-model="form.year" required class="input" placeholder="Year" />
            <input v-model="form.major" required class="input" placeholder="Major" />
            <input v-model="form.email" required class="input" placeholder="Email" />
            <textarea v-model="form.notes" class="input md:col-span-2" placeholder="Notes" />
            <button class="button-primary md:col-span-2" type="submit">
                Save TA Bio
            </button>
        </form>

        <div class="grid grid-cols-1 gap-4">
            <div v-for="bio in bios" :key="bio.id" class="card p-5">
                <div class="flex w-full justify-between gap-4">
                    <div>
                        <p class="text-lg font-semibold text-white">{{ bio.name }}</p>
                        <p class="text-sm text-slate-200">{{ bio.year }} | {{ bio.major }}</p>
                    </div>
                    <div class="ml-auto text-right text-sm text-slate-100">
                        <p>{{ bio.email }}</p>
                        <p class="mt-1 text-slate-300">{{ bio.notes }}</p>
                    </div>
                </div>
                <div class="mt-3 flex gap-2">
                    <button class="rounded bg-slate-700 px-3 py-1 text-sm text-white" @click="startEdit(bio)">Edit</button>
                    <button class="rounded bg-red-600 px-3 py-1 text-sm text-white" @click="remove(bio.id)">Delete</button>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { api } from '../lib/api';

const showForm = ref(false);
const editingId = ref(null);
const bios = ref([]);
const form = reactive({ name: '', year: '', major: '', email: '', notes: '' });

const load = async () => {
    const response = await api.get('/ta-bios');
    bios.value = response.data;
};

const resetForm = () => {
    form.name = '';
    form.year = '';
    form.major = '';
    form.email = '';
    form.notes = '';
    editingId.value = null;
};

const submit = async () => {
    const payload = { ...form };
    if (editingId.value) {
        await api.put(`/ta-bios/${editingId.value}`, payload);
    } else {
        await api.post('/ta-bios', payload);
    }
    resetForm();
    showForm.value = false;
    await load();
};

const startEdit = (bio) => {
    showForm.value = true;
    editingId.value = bio.id;
    form.name = bio.name;
    form.year = bio.year;
    form.major = bio.major;
    form.email = bio.email;
    form.notes = bio.notes ?? '';
};

const remove = async (id) => {
    await api.delete(`/ta-bios/${id}`);
    await load();
};

onMounted(load);
</script>
