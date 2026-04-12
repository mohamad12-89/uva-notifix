<template>
  <section class="flex min-h-[calc(100vh-9rem)] flex-col gap-8">
    <div class="card p-8 text-center">
      <h2 class="glow-title text-7xl font-extrabold tracking-tight md:text-8xl">
        Notifix
      </h2>
      <p class="mt-3 text-base text-slate-100/90 md:text-lg">
        The Only way To Fix A Notification Nightmare
      </p>
      <p class="mt-3 text-base text-slate-100/90 md:text-lg">
        UVA Engineering Foundations Office Hours Platform
      </p>
    </div>

    <div class="card p-6">
      <div
        class="mb-4 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
      >
        <h3 class="text-2xl font-semibold text-uva-orange">
          This Week’s Office Hours
        </h3>
        <button
          @click="isCalendarView = !isCalendarView"
          class="button-secondary"
        >
          {{
            isCalendarView ? "Switch to List View" : "Switch to Calendar View"
          }}
        </button>
      </div>

      <div v-if="!isCalendarView">
        <div v-if="weekOfficeHours.length" class="space-y-3">
          <div
            v-for="slot in weekOfficeHours"
            :key="slot.id"
            class="flex flex-col justify-between gap-3 rounded-xl border border-white/20 bg-white/5 p-4 sm:flex-row sm:items-center"
            :class="
              isStaff
                ? 'cursor-pointer transition-colors hover:border-uva-orange/45'
                : ''
            "
            @click="isStaff ? openSessionModal(slot) : null"
          >
            <div>
              <p class="font-semibold text-white">{{ slot.ta_name }}</p>
              <p class="text-sm text-slate-200">
                {{ formatDate(slot.date) }} · {{ formatTimeRangeFromSlot(slot) }}
              </p>
              <p class="text-sm text-slate-200">{{ slot.location }}</p>
              <p class="mt-1 text-sm font-medium text-uva-orange">
                Attending: {{ slot.attendance_count }}
              </p>
            </div>
            <div v-if="isStudent" class="flex shrink-0" @click.stop>
              <button
                class="button-primary transition-all duration-200"
                :class="{
                  '!bg-slate-600 hover:!bg-slate-500': joinedSessions.includes(
                    slot.id,
                  ),
                }"
                @click="toggleJoin(slot.id)"
              >
                {{
                  joinedSessions.includes(slot.id)
                    ? "Unjoin"
                    : "Join Office Hours"
                }}
              </button>
            </div>
            <div
              v-else-if="isStaff"
              class="flex shrink-0 flex-wrap gap-2"
              @click.stop
            >
              <button
                type="button"
                class="rounded-lg bg-slate-600 px-4 py-2 text-sm font-medium text-white hover:bg-slate-500"
                @click="goEditOnOfficeHoursPage(slot)"
              >
                Edit
              </button>
              <button
                type="button"
                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500"
                @click="deleteOfficeHour(slot.id)"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
        <p v-else class="text-slate-300">
          No office hours posted for this week.
        </p>
      </div>

      <div v-else class="grid grid-cols-1 gap-2 md:grid-cols-7">
        <div
          v-for="day in weekDays"
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
              :class="
                isStaff ? 'cursor-pointer hover:border-uva-orange/50' : ''
              "
              @click="isStaff ? openSessionModal(slot) : null"
            >
              <p class="font-semibold">{{ slot.ta_name }}</p>
              <p>{{ formatTimeRangeFromSlot(slot) }} · {{ slot.location }}</p>
              <p class="text-uva-orange">
                Attendees: {{ slot.attendance_count }}
              </p>
              <div class="mt-2 flex flex-wrap gap-1">
                <button
                  v-if="isStudent"
                  class="flex-auto rounded px-1 py-1 text-center text-white transition-all duration-200"
                  :class="
                    joinedSessions.includes(slot.id)
                      ? 'bg-slate-600 hover:bg-slate-500'
                      : 'bg-uva-orange hover:bg-orange-600'
                  "
                  @click.stop="toggleJoin(slot.id)"
                >
                  {{ joinedSessions.includes(slot.id) ? "Unjoin" : "Join" }}
                </button>
                <button
                  v-if="isStaff"
                  class="flex-auto rounded bg-slate-600 px-1 py-1 text-center text-white"
                  @click.stop="goEditOnOfficeHoursPage(slot)"
                >
                  Edit
                </button>
                <button
                  v-if="isStaff"
                  class="flex-auto rounded bg-red-600 px-2 py-1 text-center text-white"
                  @click.stop="deleteOfficeHour(slot.id)"
                >
                  Delete
                </button>
              </div>
            </div>
            <p v-if="!day.slots.length" class="text-xs text-slate-400">
              No office hours
            </p>
          </div>
        </div>
      </div>
    </div>

    <div
      class="mx-auto mt-auto flex w-full max-w-2xl flex-col items-center gap-12 pb-10"
    >
      <!-- About: in-flow panel below button so it never overlaps Support -->
      <div class="flex w-full flex-col items-center gap-4">
        <button
          class="group w-fit rounded-full border border-uva-orange/40 bg-gradient-to-r from-uva-blue/30 to-uva-orange/20 px-5 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-uva-orange shadow-[0_0_0_rgba(248,76,30,0)] backdrop-blur-md transition-all duration-500 hover:-translate-y-0.5 hover:border-uva-orange/70 hover:shadow-[0_0_20px_rgba(248,76,30,0.45)]"
          type="button"
          @click="toggleAbout"
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
            class="w-full rounded-2xl border border-uva-orange/30 bg-gradient-to-br from-uva-blue/70 via-slate-900/80 to-uva-orange/25 px-5 py-4 text-center shadow-[0_16px_45px_rgba(7,12,24,0.55)] backdrop-blur-md"
          >
            <p class="text-xs leading-relaxed text-slate-100">
              Notifix is a UVA Engineering Foundations platform where students can
              find and join office hours, request appointments, and learn about
              TAs in one place.
            </p>
          </div>
        </Transition>
      </div>

      <!-- Support: separate block with clear vertical spacing from About -->
      <div class="flex w-full flex-col items-center gap-4 border-t border-white/10 pt-12">
        <button
          class="group w-fit rounded-full border border-uva-orange/40 bg-gradient-to-r from-uva-blue/30 to-uva-orange/20 px-5 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-uva-orange shadow-[0_0_0_rgba(248,76,30,0)] backdrop-blur-md transition-all duration-500 hover:-translate-y-0.5 hover:border-uva-orange/70 hover:shadow-[0_0_20px_rgba(248,76,30,0.45)]"
          type="button"
          @click="toggleSupport"
        >
          <span class="transition-colors duration-300 group-hover:text-white"
            >Support</span
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
            v-if="supportOpen"
            class="w-full rounded-2xl border border-uva-orange/30 bg-gradient-to-br from-uva-blue/70 via-slate-900/80 to-uva-orange/25 px-5 py-4 text-left shadow-[0_16px_45px_rgba(7,12,24,0.55)] backdrop-blur-md"
          >
            <h4 class="mb-3 text-center text-sm font-bold uppercase tracking-wide text-uva-orange">
              Support
            </h4>
            <div class="space-y-4 text-xs leading-relaxed text-slate-100">
              <div>
                <p class="font-semibold text-slate-200">Help</p>
                <p class="mt-1">
                  Contact the professor at
                  <a
                    class="text-uva-orange underline decoration-uva-orange/50 underline-offset-2 hover:text-white"
                    :href="`mailto:${supportProfessorEmail}`"
                    >{{ supportProfessorEmail }}</a
                  >.
                </p>
              </div>
              <div>
                <p class="font-semibold text-slate-200">Contact</p>
                <p class="mt-1 text-slate-300">
                  For inquiries, you can reach out to:
                </p>
                <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-slate-100">
                  <li v-for="(email, idx) in supportContactEmails" :key="idx">
                    <a
                      class="text-uva-orange underline decoration-uva-orange/50 underline-offset-2 hover:text-white"
                      :href="`mailto:${email}`"
                      >{{ email }}</a
                    >
                  </li>
                </ol>
              </div>
            </div>
          </div>
        </Transition>
      </div>

      <p class="text-center text-xs text-slate-400">
        © 2026 Notification Nightmare.
      </p>
    </div>

    <div
      v-if="isStaff && sessionModalOpen && selectedSlot"
      class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeSessionModal"
    >
      <div class="card w-full max-w-2xl p-5">
        <div class="mb-3 flex items-start justify-between gap-3">
          <div>
            <h3 class="text-xl font-semibold text-uva-orange">
              Office Hour Details
            </h3>
            <p class="text-sm text-slate-200">{{ selectedSlot.ta_name }}</p>
            <p class="text-sm text-slate-300">
              {{ selectedSlot.date }} · {{ formatTimeRangeFromSlot(selectedSlot) }}
            </p>
            <p class="text-sm text-slate-300">{{ selectedSlot.location }}</p>
            <p class="mt-1 text-sm font-medium text-uva-orange">
              Attendees: {{ selectedSlot.attendance_count }}
            </p>
          </div>
          <button
            type="button"
            class="rounded border border-white/20 px-2 py-1 text-xs text-slate-200 hover:bg-white/10"
            @click="closeSessionModal"
          >
            Close
          </button>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded bg-slate-600 px-3 py-1.5 text-sm text-white"
            @click="quickEditFromModal"
          >
            Edit
          </button>
          <button
            type="button"
            class="rounded bg-red-600 px-3 py-1.5 text-sm text-white"
            @click="quickDeleteFromModal"
          >
            Delete
          </button>
        </div>

        <div class="rounded-md border border-white/15 bg-slate-900/40 p-3">
          <p
            class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-300"
          >
            Students visiting
          </p>
          <p v-if="loadingSignups" class="text-xs text-slate-400">
            Loading students...
          </p>
          <div v-else-if="modalSignups.length" class="space-y-2">
            <div
              v-for="signup in modalSignups"
              :key="signup.id"
              class="flex items-center justify-between gap-2 rounded border border-white/10 bg-white/5 p-2"
            >
              <div>
                <p class="text-sm font-medium text-slate-100">
                  {{ signup.student_name }}
                </p>
                <p class="text-xs text-slate-400">{{ signup.student_email }}</p>
              </div>
              <button
                v-if="!signup.checked_in_at"
                class="rounded bg-uva-orange px-2 py-1 text-xs font-semibold text-white hover:bg-uva-orange/90"
                @click="checkInStudent(selectedSlot, signup)"
              >
                Check In
              </button>
              <span
                v-else
                class="rounded bg-green-500/25 px-2 py-1 text-xs font-semibold text-green-300"
              >
                Checked In
              </span>
            </div>
          </div>
          <p v-else class="text-xs text-slate-400">No students signed up yet.</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref, computed } from "vue";
import { useRouter } from "vue-router";
import { api } from "../lib/api";
import {
  officeHours,
  joinedSessions,
  fetchOfficeHours,
  pushJoinedSession,
  removeJoinedSession,
  removeOfficeHourFromStore,
} from "../composables/useOfficeHours";
import { useAuthProfile } from "../composables/useAuthProfile";

const router = useRouter();
const { isStudent, isStaff, authProfile } = useAuthProfile();

const sessionModalOpen = ref(false);
const selectedSlot = ref(null);
const modalSignups = ref([]);
const loadingSignups = ref(false);

const aboutOpen = ref(false);
const supportOpen = ref(false);

const supportProfessorEmail = "amm8km@virginia.edu";
const supportContactEmails = [
  "khg5bj@virginia.edu",
  "cdd9sb@virginia.edu",
  "xfw9vp@virginia.edu",
  "uhu5nr@virginia.edu",
];

function toggleAbout() {
  aboutOpen.value = !aboutOpen.value;
  if (aboutOpen.value) supportOpen.value = false;
}

function toggleSupport() {
  supportOpen.value = !supportOpen.value;
  if (supportOpen.value) aboutOpen.value = false;
}

const isCalendarView = ref(false);

const toLocalYmd = (d) => {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
};

const startOfWeekLocal = (d) => {
  // Monday-start week
  const copy = new Date(d);
  copy.setHours(0, 0, 0, 0);
  const day = copy.getDay(); // 0=Sun..6=Sat
  const diffToMonday = (day + 6) % 7;
  copy.setDate(copy.getDate() - diffToMonday);
  return copy;
};

const endOfWeekLocal = (d) => {
  const start = startOfWeekLocal(d);
  const end = new Date(start);
  end.setDate(start.getDate() + 6);
  end.setHours(23, 59, 59, 999);
  return end;
};

const weekRange = computed(() => {
  const now = new Date();
  const start = startOfWeekLocal(now);
  const end = endOfWeekLocal(now);
  return { start, end, startYmd: toLocalYmd(start), endYmd: toLocalYmd(end) };
});

const weekOfficeHours = computed(() => {
  const { startYmd, endYmd } = weekRange.value;
  return officeHours.value
    .filter((slot) => slot.date >= startYmd && slot.date <= endYmd)
    .slice()
    .sort((a, b) => {
      if (a.date !== b.date) return a.date.localeCompare(b.date);
      return (a.time || "").localeCompare(b.time || "");
    });
});

const weekDays = computed(() => {
  const days = [];
  const { start } = weekRange.value;
  for (let i = 0; i < 7; i++) {
    const d = new Date(start);
    d.setDate(start.getDate() + i);
    const dateString = toLocalYmd(d);
    const label = d.toLocaleDateString(undefined, {
      weekday: "short",
      month: "short",
      day: "numeric",
    });
    days.push({
      key: dateString,
      label,
      slots: weekOfficeHours.value.filter((slot) => slot.date === dateString),
    });
  }
  return days;
});

const formatTimeRange = (startTime, endTime) => {
  const [hh, mm] = startTime.slice(0, 5).split(":").map(Number);
  const start = new Date(2000, 0, 1, hh, mm, 0, 0);
  const [eh, em] = endTime.slice(0, 5).split(":").map(Number);
  const end = new Date(2000, 0, 1, eh, em, 0, 0);
  const fmt = new Intl.DateTimeFormat(undefined, {
    hour: "numeric",
    minute: "2-digit",
  });
  return `${fmt.format(start)} – ${fmt.format(end)}`;
};

const guessEndTime = (startTime, durationMinutes = 60) => {
  const [hh, mm] = startTime.slice(0, 5).split(":").map(Number);
  const start = new Date(2000, 0, 1, hh, mm, 0, 0);
  const end = new Date(start.getTime() + durationMinutes * 60 * 1000);
  const h = String(end.getHours()).padStart(2, "0");
  const m = String(end.getMinutes()).padStart(2, "0");
  return `${h}:${m}`;
};

const formatTimeRangeFromSlot = (slot) => {
  const end =
    slot.end_time ?? guessEndTime(slot.time, slot.duration_minutes ?? 60);
  return formatTimeRange(slot.time, end);
};

function goEditOnOfficeHoursPage(slot) {
  router.push({
    path: "/office-hours",
    query: { edit: String(slot.id) },
  });
}

async function deleteOfficeHour(id, { skipConfirm = false } = {}) {
  if (!isStaff.value) return;
  if (
    !skipConfirm &&
    !confirm("Delete this office hour? This cannot be undone.")
  ) {
    return;
  }
  try {
    await api.delete(`/office-hours/${id}`);
    removeOfficeHourFromStore(id);
    if (selectedSlot.value?.id === id) closeSessionModal();
    await fetchOfficeHours();
  } catch (e) {
    console.error("Failed to delete office hour:", e);
    await fetchOfficeHours();
  }
}

const loadSignupsForSlot = async (slotId) => {
  loadingSignups.value = true;
  try {
    const { data } = await api.get(`/office-hours/${slotId}/signups`);
    modalSignups.value = data.signups || [];
  } catch (e) {
    console.error("Failed to load student signups:", e);
    modalSignups.value = [];
  } finally {
    loadingSignups.value = false;
  }
};

const openSessionModal = async (slot) => {
  selectedSlot.value = slot;
  sessionModalOpen.value = true;
  await loadSignupsForSlot(slot.id);
};

const closeSessionModal = () => {
  sessionModalOpen.value = false;
  selectedSlot.value = null;
  modalSignups.value = [];
};

const quickEditFromModal = () => {
  if (!selectedSlot.value) return;
  const slot = selectedSlot.value;
  closeSessionModal();
  goEditOnOfficeHoursPage(slot);
};

const quickDeleteFromModal = async () => {
  if (!selectedSlot.value) return;
  if (!confirm("Delete this office hour?")) return;
  const id = selectedSlot.value.id;
  closeSessionModal();
  await deleteOfficeHour(id, { skipConfirm: true });
};

const checkInStudent = async (slot, signup) => {
  try {
    await api.post(`/office-hours/${slot.id}/signups/${signup.id}/check-in`);
    await loadSignupsForSlot(slot.id);
    if (selectedSlot.value?.id === slot.id) {
      selectedSlot.value = {
        ...selectedSlot.value,
        attendance_count: modalSignups.value.length,
      };
    }
    await fetchOfficeHours();
  } catch (e) {
    console.error("Failed to check in student:", e);
  }
};

const toggleJoin = async (id) => {
  const isJoined = joinedSessions.value.includes(id);
  const profile = authProfile.value;

  try {
    if (isJoined) {
      await api.delete(`/office-hours/${id}/join`, {
        data: { student_email: profile?.email },
      });
      removeJoinedSession(id);
    } else {
      await api.post(`/office-hours/${id}/join`, {
        student_name:
          `${profile?.firstName || ""} ${profile?.lastName || ""}`.trim() ||
          profile?.email,
        student_email: profile?.email,
      });
      pushJoinedSession(id);
    }
    await fetchOfficeHours();
  } catch (error) {
    console.error("An error occurred while updating attendance:", error);
    await fetchOfficeHours();
  }
};

const formatDate = (value) =>
  new Date(`${value}T00:00:00`).toLocaleDateString();

onMounted(fetchOfficeHours);
</script>
