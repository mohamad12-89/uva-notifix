<template>
    <section class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-3xl font-bold text-uva-orange">Office Hours Calendar</h2>
            <button class="button-secondary" @click="showForm = !showForm">
                Add Office Hours
            </button>
        </div>

        <form v-if="showForm" class="card grid gap-3 p-5 md:grid-cols-2" @submit.prevent="submitForm">
            <input v-model="form.ta_name" required class="input" placeholder="TA name" />
            <input v-model="form.location" required class="input" placeholder="Location" />
            <input v-model="form.date" required class="input" type="date" />
            <input v-model="form.time" required class="input" type="time" />
            <button class="button-primary md:col-span-2" type="submit">
                Save Office Hour
            </button>
        </form>

        <div class="space-y-3">
            <div
                v-for="week in calendarWeeks"
                :key="week.weekNumber"
                class="rounded-2xl border border-white/20 bg-white/5 p-3 shadow-lg backdrop-blur-md"
            >
                <div class="mb-3">
                    <p class="text-sm font-semibold uppercase tracking-wider text-uva-orange">Week {{ week.weekNumber }}</p>
                </div>

                <div class="grid grid-cols-1 gap-2 md:grid-cols-7">
                    <div
                        v-for="day in week.days"
                        :key="day.key"
                        class="min-h-36 rounded-xl border border-white/20 bg-white/10 p-3 shadow-sm"
                    >
                        <p class="mb-2 text-sm font-semibold text-slate-100">{{ day.label }}</p>
                        <div v-if="day.inMonth" class="space-y-2">
                            <div v-for="slot in day.slots" :key="slot.id" class="rounded-lg border border-white/20 bg-white/5 p-2 text-xs text-slate-100">
                                <p class="font-semibold">{{ slot.ta_name }}</p>
                                <p>{{ formatTime(slot.time) }} - {{ slot.location }}</p>
                                <p class="text-uva-orange">Attendees: {{ slot.attendance_count }}</p>
                                <div class="mt-2 flex gap-1">
                                    <button class="rounded bg-uva-orange px-2 py-1 text-white" @click="join(slot.id)">Join</button>
                                    <button class="rounded bg-slate-600 px-2 py-1 text-white" @click="startEdit(slot)">Edit</button>
                                    <button class="rounded bg-red-600 px-2 py-1 text-white" @click="remove(slot.id)">Delete</button>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-400">-</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { api } from '../lib/api';

const showForm = ref(false);
const editingId = ref(null);
const officeHours = ref([]);
const form = reactive({ ta_name: '', location: '', date: '', time: '' });

const resetForm = () => {
    form.ta_name = '';
    form.location = '';
    form.date = '';
    form.time = '';
    editingId.value = null;
};

const load = async () => {
    const response = await api.get('/office-hours');
    officeHours.value = response.data;
};

const submitForm = async () => {
    const payload = { ...form };
    if (editingId.value) {
        await api.put(`/office-hours/${editingId.value}`, payload);
    } else {
        await api.post('/office-hours', payload);
    }
    resetForm();
    showForm.value = false;
    await load();
};

const startEdit = (slot) => {
    showForm.value = true;
    editingId.value = slot.id;
    form.ta_name = slot.ta_name;
    form.location = slot.location;
    form.date = slot.date;
    form.time = slot.time.slice(0, 5);
};

const remove = async (id) => {
    await api.delete(`/office-hours/${id}`);
    await load();
};

const join = async (id) => {
    await api.post(`/office-hours/${id}/join`);
    await load();
};

const formatTime = (value) => value.slice(0, 5);

const calendarWeeks = computed(() => {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDayWeekday = new Date(year, month, 1).getDay();
    const totalCells = Math.ceil((firstDayWeekday + daysInMonth) / 7) * 7;

    const cells = Array.from({ length: totalCells }).map((_, index) => {
        const dayOfMonth = index - firstDayWeekday + 1;
        const inMonth = dayOfMonth >= 1 && dayOfMonth <= daysInMonth;

        if (!inMonth) {
            return {
                key: `empty-${index}`,
                inMonth: false,
                label: '',
                slots: [],
            };
        }

        const date = new Date(year, month, dayOfMonth).toISOString().slice(0, 10);
        const label = new Date(year, month, dayOfMonth).toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
        });

        return {
            key: date,
            inMonth: true,
            label,
            slots: officeHours.value.filter((slot) => slot.date === date),
        };
    });

    const weeks = [];
    for (let i = 0; i < cells.length; i += 7) {
        weeks.push({
            weekNumber: (i / 7) + 1,
            days: cells.slice(i, i + 7),
        });
    }

    return weeks;
});

onMounted(load);
</script>
