<template>
  <section class="flex min-h-[calc(100vh-9rem)] flex-col gap-8">
    <div class="card p-8 text-center">
      <h2 class="glow-title text-7xl font-extrabold tracking-tight md:text-8xl">
        Notifix
      </h2>
      <p class="mt-3 text-base text-slate-100/90 md:text-lg">
        UVA Engineering Foundations Office Hours Platform
      </p>
    </div>

    <div class="card p-6">
      <div
        class="mb-4 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
      >
        <h3 class="text-2xl font-semibold text-uva-orange">
          Weekly Office Hours
        </h3>
        <button
          @click="isCalendarView = !isCalendarView"
          class="button-secondary"
        >
          {{ isCalendarView ? "Show List View" : "View Calendar View" }}
        </button>
      </div>

      <div v-if="!isCalendarView">
        <div v-if="officeHours.length" class="space-y-3">
          <div
            v-for="slot in officeHours.slice(0, 7)"
            :key="slot.id"
            class="flex flex-col justify-between gap-3 rounded-xl border border-white/20 bg-white/5 p-4 sm:flex-row sm:items-center"
          >
            <div>
              <p class="font-semibold text-white">{{ slot.ta_name }}</p>
              <p class="text-sm text-slate-200">
                {{ formatDate(slot.date) }} at {{ formatTime(slot.time) }}
              </p>
              <p class="text-sm text-slate-200">{{ slot.location }}</p>
              <p class="mt-1 text-sm font-medium text-uva-orange">
                Attending: {{ slot.attendance_count }}
              </p>
            </div>
            <button
              class="button-primary transition-all duration-200"
              :disabled="joinedSessions.includes(slot.id)"
              :class="{
                'opacity-50 cursor-not-allowed': joinedSessions.includes(
                  slot.id,
                ),
              }"
              @click="join(slot.id)"
            >
              {{
                joinedSessions.includes(slot.id)
                  ? "Joined"
                  : "Join Office Hours"
              }}
            </button>
          </div>
        </div>
        <p v-else class="text-slate-300">No office hours posted yet.</p>
      </div>

      <div v-else class="grid grid-cols-1 gap-2 md:grid-cols-7">
        <div
          v-for="day in upcomingDays"
          :key="day.key"
          class="min-h-36 rounded-xl border border-white/20 bg-white/10 p-3 shadow-sm"
        >
          <p class="mb-2 text-sm font-semibold text-slate-100">
            {{ day.label }}
          </p>
          <div class="space-y-2">
            <div
              v-for="slot in day.slots"
              :key="slot.id"
              class="rounded-lg border border-white/20 bg-white/5 p-2 text-xs text-slate-100"
            >
              <p class="font-semibold">{{ slot.ta_name }}</p>
              <p>{{ formatTime(slot.time) }} - {{ slot.location }}</p>
              <p class="text-uva-orange">
                Attendees: {{ slot.attendance_count }}
              </p>
              <button
                class="mt-2 w-full rounded px-2 py-1 text-white transition-all duration-200"
                :class="
                  joinedSessions.includes(slot.id)
                    ? 'bg-uva-orange/50 cursor-not-allowed'
                    : 'bg-uva-orange hover:bg-orange-600'
                "
                :disabled="joinedSessions.includes(slot.id)"
                @click="join(slot.id)"
              >
                {{ joinedSessions.includes(slot.id) ? "Joined" : "Join" }}
              </button>
            </div>
            <p v-if="!day.slots.length" class="text-xs text-slate-400">
              No office hours
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="relative mx-auto mt-auto mb-12 flex w-full justify-center">
      <button
        class="group w-fit rounded-full border border-uva-orange/40 bg-gradient-to-r from-uva-blue/30 to-uva-orange/20 px-5 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-uva-orange shadow-[0_0_0_rgba(248,76,30,0)] backdrop-blur-md transition-all duration-500 hover:-translate-y-0.5 hover:border-uva-orange/70 hover:shadow-[0_0_20px_rgba(248,76,30,0.45)]"
        type="button"
        @click="aboutOpen = !aboutOpen"
      >
        <span class="transition-colors duration-300 group-hover:text-white"
          >About</span
        >
      </button>

      <Transition
        enter-active-class="transition-all duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]"
        enter-from-class="translate-y-[-18px] scale-95 opacity-0 blur-[2px]"
        enter-to-class="translate-y-0 scale-100 opacity-100 blur-0"
        leave-active-class="transition-all duration-350 ease-in"
        leave-from-class="translate-y-0 scale-100 opacity-100"
        leave-to-class="translate-y-[-8px] scale-95 opacity-0"
      >
        <div
          v-if="aboutOpen"
          class="absolute top-full mt-3 w-[min(32rem,92vw)] rounded-2xl border border-uva-orange/30 bg-gradient-to-br from-uva-blue/70 via-slate-900/80 to-uva-orange/25 px-5 py-4 text-center shadow-[0_16px_45px_rgba(7,12,24,0.55)] backdrop-blur-md"
        >
          <p class="text-xs leading-relaxed text-slate-100">
            Notifix is a UVA Engineering Foundations platform where students can
            find and join office hours, request appointments, and learn about
            TAs in one place.
          </p>
        </div>
      </Transition>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref, computed } from "vue";
import { api } from "../lib/api";

const officeHours = ref([]);
const aboutOpen = ref(false);
const isCalendarView = ref(false);
const joinedSessions = ref(
  JSON.parse(localStorage.getItem("joinedSessions") || "[]"),
);

const loadOfficeHours = async () => {
  const response = await api.get("/office-hours");
  officeHours.value = response.data;
};

const upcomingDays = computed(() => {
  const days = [];
  const today = new Date();
  for (let i = 0; i < 7; i++) {
    const d = new Date(today);
    d.setDate(today.getDate() + i);

    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    const dateString = `${year}-${month}-${day}`;
    const label = d.toLocaleDateString(undefined, {
      weekday: "short",
      month: "short",
      day: "numeric",
    });

    days.push({
      key: dateString,
      label,
      slots: officeHours.value.filter((slot) => slot.date === dateString),
    });
  }
  return days;
});

const join = async (id) => {
  if (joinedSessions.value.includes(id)) return;

  try {
    await api.post(`/office-hours/${id}/join`);
    joinedSessions.value.push(id);
    localStorage.setItem(
      "joinedSessions",
      JSON.stringify(joinedSessions.value),
    );
    await loadOfficeHours();
  } catch (error) {
    if (error.response && error.response.status === 409) {
      console.error(error.response.data.message);
      // Sync local state if the server says we've already joined
      if (!joinedSessions.value.includes(id)) {
        joinedSessions.value.push(id);
        localStorage.setItem(
          "joinedSessions",
          JSON.stringify(joinedSessions.value),
        );
      }
    } else {
      console.error("An error occurred while joining:", error);
    }
  }
};

const formatDate = (value) =>
  new Date(`${value}T00:00:00`).toLocaleDateString();
const formatTime = (value) => value.slice(0, 5);

onMounted(loadOfficeHours);
</script>
