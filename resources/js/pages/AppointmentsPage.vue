<template>
  <section class="space-y-6">
    <h2 class="text-3xl font-bold text-uva-orange">Appointments</h2>

    <!-- Student: submit / manage own requests -->
    <template v-if="isStudent">
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
        <div class="space-y-1">
          <label for="appt-pref-date" class="block text-xs font-medium text-slate-300">
            Preferred date
          </label>
          <input
            id="appt-pref-date"
            v-model="form.preferred_date"
            type="date"
            required
            class="input w-full"
          />
        </div>
        <div class="space-y-1">
          <label for="appt-pref-time" class="block text-xs font-medium text-slate-300">
            Preferred time
          </label>
          <input
            id="appt-pref-time"
            v-model="form.preferred_time"
            type="time"
            required
            class="input w-full"
          />
        </div>
        <textarea v-model="form.comments" class="input md:col-span-2" placeholder="Additional comments" />
        <button class="button-primary md:col-span-2" type="submit">
          {{ editingId ? "Update Appointment Request" : "Submit Appointment Request" }}
        </button>
      </form>

      <p v-if="confirmation" class="rounded-lg bg-green-100 p-3 text-green-700">{{ confirmation }}</p>

      <div class="card p-6">
        <h3 class="mb-3 text-xl font-semibold text-uva-orange">Your requests</h3>
        <div v-if="appointments.length" class="space-y-3">
          <div
            v-for="appointment in appointments"
            :key="appointment.id"
            class="rounded-xl border border-white/20 bg-white/5 p-3"
          >
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
              <p class="font-semibold text-white">
                {{ appointment.student_name }} — {{ appointment.class }}
              </p>
              <span
                class="rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="statusBadgeClass(appointment.status)"
              >
                {{ formatStatus(appointment.status) }}
              </span>
            </div>
            <p class="text-sm text-slate-200">{{ appointment.reason }} | TA: {{ appointment.ta_selected }}</p>
            <p
              v-if="formatPreferredDisplay(appointment.preferred_date, appointment.preferred_time)"
              class="text-sm font-medium text-uva-orange"
            >
              Preferred:
              {{ formatPreferredDisplay(appointment.preferred_date, appointment.preferred_time) }}
            </p>
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
    </template>

    <!-- TA / Professor: review queue (no new student submissions from this role) -->
    <template v-else>
      <div class="card p-6">
        <p class="text-sm text-slate-300">
          As a TA or instructor, you facilitate sessions — you do not submit student-style appointment requests here.
        </p>
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
          <div class="rounded-xl border border-uva-orange/30 bg-uva-orange/10 p-4 text-center">
            <p class="text-2xl font-bold text-uva-orange">{{ pendingCount }}</p>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-300">Pending</p>
          </div>
          <div class="rounded-xl border border-white/20 bg-white/5 p-4 text-center">
            <p class="text-2xl font-bold text-slate-100">{{ acceptedCount }}</p>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Accepted</p>
          </div>
          <div class="rounded-xl border border-white/20 bg-white/5 p-4 text-center">
            <p class="text-2xl font-bold text-slate-100">{{ declinedCount }}</p>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Declined</p>
          </div>
        </div>
      </div>

      <div class="card p-6">
        <h3 class="mb-3 text-xl font-semibold text-uva-orange">Incoming requests</h3>
        <div v-if="appointments.length" class="space-y-4">
          <div
            v-for="appointment in appointments"
            :key="appointment.id"
            class="rounded-xl border border-white/20 bg-white/5 p-4"
          >
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
              <p class="font-semibold text-white">{{ appointment.student_name }} — {{ appointment.class }}</p>
              <span
                class="rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="statusBadgeClass(appointment.status)"
              >
                {{ formatStatus(appointment.status) }}
              </span>
            </div>
            <p class="text-sm text-slate-200">{{ appointment.reason }}</p>
            <p class="text-sm text-slate-300">TA selected: {{ appointment.ta_selected }}</p>
            <p
              v-if="formatPreferredDisplay(appointment.preferred_date, appointment.preferred_time)"
              class="mt-1 text-sm font-medium text-uva-orange"
            >
              Student preferred slot:
              {{ formatPreferredDisplay(appointment.preferred_date, appointment.preferred_time) }}
            </p>
            <p class="mt-1 text-sm text-slate-300">{{ appointment.help_needed }}</p>
            <p v-if="appointment.comments" class="mt-1 text-sm text-slate-400">{{ appointment.comments }}</p>
            <div v-if="appointment.status === 'pending'" class="mt-4 flex flex-wrap gap-2">
              <button
                type="button"
                class="rounded-lg bg-uva-orange px-4 py-2 text-sm font-medium text-white hover:bg-uva-orange/90"
                @click="setAppointmentStatus(appointment, 'accepted')"
              >
                Accept
              </button>
              <button
                type="button"
                class="rounded-lg border border-white/25 bg-white/10 px-4 py-2 text-sm font-medium text-slate-100 hover:bg-white/15"
                @click="setAppointmentStatus(appointment, 'declined')"
              >
                Decline
              </button>
            </div>
          </div>
        </div>
        <p v-else class="text-slate-300">No appointment requests yet.</p>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { api } from "../lib/api";
import { useAuthProfile } from "../composables/useAuthProfile";

const { isStudent, isTaProfessor } = useAuthProfile();

const appointments = ref([]);
const confirmation = ref("");
const editingId = ref(null);
const form = reactive({
  student_name: "",
  reason: "",
  help_needed: "",
  class: "",
  ta_selected: "",
  preferred_date: "",
  preferred_time: "",
  comments: "",
});

const load = async () => {
  const response = await api.get("/appointments");
  appointments.value = response.data;
};

const pendingCount = computed(() =>
  appointments.value.filter((a) => (a.status || "pending") === "pending").length,
);
const acceptedCount = computed(() =>
  appointments.value.filter((a) => a.status === "accepted").length,
);
const declinedCount = computed(() =>
  appointments.value.filter((a) => a.status === "declined").length,
);

const formatStatus = (s) => {
  const v = s || "pending";
  return v.charAt(0).toUpperCase() + v.slice(1);
};

const statusBadgeClass = (s) => {
  const v = s || "pending";
  if (v === "accepted") return "bg-green-500/25 text-green-300";
  if (v === "declined") return "bg-red-500/25 text-red-300";
  return "bg-amber-500/20 text-amber-200";
};

/** Normalize API date to YYYY-MM-DD for <input type="date"> */
function normalizeDateForInput(value) {
  if (value == null || value === "") return "";
  const s = String(value);
  if (s.length >= 10 && /^\d{4}-\d{2}-\d{2}/.test(s)) return s.slice(0, 10);
  return s;
}

/** Normalize API time to HH:mm for <input type="time"> */
function normalizeTimeForInput(value) {
  if (value == null || value === "") return "";
  const s = String(value);
  const m = s.match(/^(\d{1,2}):(\d{2})/);
  if (m) {
    const h = m[1].padStart(2, "0");
    return `${h}:${m[2]}`;
  }
  return s.slice(0, 5);
}

function formatPreferredDisplay(dateVal, timeVal) {
  const dateStr = normalizeDateForInput(dateVal);
  const timeStr = normalizeTimeForInput(timeVal);
  if (!dateStr) return "";
  const parts = dateStr.split("-").map(Number);
  if (parts.length !== 3 || parts.some(Number.isNaN)) {
    return timeStr ? `${dateStr} at ${formatTimeLabel(timeStr)}` : dateStr;
  }
  const [y, mo, d] = parts;
  const dt = new Date(y, mo - 1, d);
  const datePart = dt.toLocaleDateString(undefined, {
    weekday: "short",
    month: "short",
    day: "numeric",
    year: "numeric",
  });
  if (!timeStr) return datePart;
  return `${datePart} at ${formatTimeLabel(timeStr)}`;
}

function formatTimeLabel(hhmm) {
  const [h, m] = hhmm.split(":").map((x) => parseInt(x, 10));
  if (Number.isNaN(h) || Number.isNaN(m)) return hhmm;
  const suffix = h < 12 ? "AM" : "PM";
  let h12 = h % 12;
  if (h12 === 0) h12 = 12;
  return `${h12}:${String(m).padStart(2, "0")} ${suffix}`;
}

const resetForm = () => {
  editingId.value = null;
  form.student_name = "";
  form.reason = "";
  form.help_needed = "";
  form.class = "";
  form.ta_selected = "";
  form.preferred_date = "";
  form.preferred_time = "";
  form.comments = "";
};

const submit = async () => {
  if (!isStudent.value) return;
  if (editingId.value) {
    await api.put(`/appointments/${editingId.value}`, { ...form });
    confirmation.value = "Appointment request updated successfully.";
  } else {
    await api.post("/appointments", { ...form });
    confirmation.value = "Appointment request submitted successfully.";
  }
  resetForm();
  await load();
};

const startEdit = (appointment) => {
  if (!isStudent.value) return;
  editingId.value = appointment.id;
  form.student_name = appointment.student_name;
  form.reason = appointment.reason;
  form.help_needed = appointment.help_needed;
  form.class = appointment.class;
  form.ta_selected = appointment.ta_selected;
  form.preferred_date = normalizeDateForInput(appointment.preferred_date);
  form.preferred_time = normalizeTimeForInput(appointment.preferred_time);
  form.comments = appointment.comments ?? "";
};

const remove = async (id) => {
  if (!isStudent.value) return;
  await api.delete(`/appointments/${id}`);
  confirmation.value = "Appointment request deleted.";
  if (editingId.value === id) {
    resetForm();
  }
  await load();
};

const setAppointmentStatus = async (appointment, status) => {
  if (!isTaProfessor.value) return;
  try {
    await api.put(`/appointments/${appointment.id}`, { status });
    appointment.status = status;
    await load();
  } catch (e) {
    console.error("Failed to update status:", e);
  }
};

onMounted(load);
</script>
