<template>
    <section class="space-y-6">
        <h2 class="text-3xl font-bold text-uva-orange">Appointments</h2>

        <form class="card grid gap-3 p-6 md:grid-cols-2" @submit.prevent="submit">
            <input v-model="form.student_name" required class="input" placeholder="Student name" />
            <input v-model="form.reason" required class="input" placeholder="Reason for appointment" />
            <textarea v-model="form.help_needed" required class="input md:col-span-2" placeholder="What help is needed?" />
            <select v-model="form.class" required class="input">
                <option disabled value="">Select class</option>
                <option>ENGR 1010</option>
                <option>ENGR 1020</option>
                <option>ENGR 1624</option>
                <option>ENGR 2595</option>
            </select>
            <select v-model="form.ta_selected" required class="input">
                <option disabled value="">Select TA</option>
                <option>FEDE</option>
                <option>William</option>
                <option>Avery Smith</option>
            </select>
            <textarea v-model="form.comments" class="input md:col-span-2" placeholder="Additional comments" />
            <button class="button-primary md:col-span-2" type="submit">
                {{ editingId ? 'Update Appointment Request' : 'Submit Appointment Request' }}
            </button>
        </form>

        <p v-if="confirmation" class="rounded-lg bg-green-100 p-3 text-green-700">{{ confirmation }}</p>

        <div class="card p-6">
            <h3 class="mb-3 text-xl font-semibold text-uva-orange">Submitted Requests</h3>
            <div v-if="appointments.length" class="space-y-3">
                <div v-for="appointment in appointments" :key="appointment.id" class="rounded-xl border border-white/20 bg-white/5 p-3">
                    <p class="font-semibold text-white">{{ appointment.student_name }} - {{ appointment.class }}</p>
                    <p class="text-sm text-slate-200">{{ appointment.reason }} | TA: {{ appointment.ta_selected }}</p>
                    <p class="text-sm text-slate-300">{{ appointment.help_needed }}</p>
                    <p v-if="appointment.comments" class="mt-1 text-sm text-slate-400">{{ appointment.comments }}</p>
                    <div class="mt-3 flex gap-2">
                        <button class="rounded bg-slate-700 px-3 py-1 text-sm text-white" @click="startEdit(appointment)">
                            Edit
                        </button>
                        <button class="rounded bg-red-600 px-3 py-1 text-sm text-white" @click="remove(appointment.id)">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
            <p v-else class="text-slate-300">No appointments yet.</p>
        </div>
    </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { api } from '../lib/api';

const appointments = ref([]);
const confirmation = ref('');
const editingId = ref(null);
const form = reactive({
    student_name: '',
    reason: '',
    help_needed: '',
    class: '',
    ta_selected: '',
    comments: '',
});

const load = async () => {
    const response = await api.get('/appointments');
    appointments.value = response.data;
};

const resetForm = () => {
    editingId.value = null;
    form.student_name = '';
    form.reason = '';
    form.help_needed = '';
    form.class = '';
    form.ta_selected = '';
    form.comments = '';
};

const submit = async () => {
    if (editingId.value) {
        await api.put(`/appointments/${editingId.value}`, { ...form });
        confirmation.value = 'Appointment request updated successfully.';
    } else {
        await api.post('/appointments', { ...form });
        confirmation.value = 'Appointment request submitted successfully.';
    }
    resetForm();
    await load();
};

const startEdit = (appointment) => {
    editingId.value = appointment.id;
    form.student_name = appointment.student_name;
    form.reason = appointment.reason;
    form.help_needed = appointment.help_needed;
    form.class = appointment.class;
    form.ta_selected = appointment.ta_selected;
    form.comments = appointment.comments ?? '';
};

const remove = async (id) => {
    await api.delete(`/appointments/${id}`);
    confirmation.value = 'Appointment request deleted.';
    if (editingId.value === id) {
        resetForm();
    }
    await load();
};

onMounted(load);
</script>
