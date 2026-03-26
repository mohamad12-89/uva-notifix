<template>
  <section class="space-y-6">
    <div
      class="grid w-full grid-cols-1 items-center gap-3 md:grid-cols-[auto_1fr_auto]"
    >
      <h2 class="text-3xl font-bold text-uva-orange">
        Office Hours Calendar
      </h2>

      <div class="relative flex justify-center">
        <input
          v-model="searchQuery"
          ref="searchInputEl"
          class="input w-full max-w-md"
          placeholder="Search office hours (TA, location, time...)"
          @focus="onSearchFocus"
          @blur="onSearchBlur"
          @keydown.down.prevent="moveSelection(1)"
          @keydown.up.prevent="moveSelection(-1)"
          @keydown.enter.prevent="selectHighlighted()"
          @keydown.esc.prevent="closeSearch()"
        />

        <div
          v-if="showSuggestions"
          class="absolute top-full z-20 mt-2 w-full max-w-md overflow-hidden rounded-2xl border border-white/20 bg-slate-900/85 shadow-2xl backdrop-blur-md"
        >
          <div
            v-for="(s, idx) in suggestions"
            :key="s.id"
            class="cursor-pointer px-4 py-3 text-sm transition"
            :class="
              idx === highlightedIndex
                ? 'bg-uva-orange/15 text-slate-50'
                : 'text-slate-200 hover:bg-white/5'
            "
            @mousedown.prevent="goToOfficeHour(s)"
          >
            <div class="flex items-center justify-between gap-3">
              <p class="font-semibold text-slate-50">{{ s.ta_name }}</p>
              <p class="text-xs text-slate-300">
                {{ s.timeRange }}
              </p>
            </div>
            <p class="mt-0.5 text-xs text-slate-300">
              {{ s.location }} · {{ s.dateLabel }} · Attendees: {{ s.attendance_count }}
            </p>
          </div>

          <div v-if="!suggestions.length" class="px-4 py-3 text-xs text-slate-400">
            No matches
          </div>
        </div>
      </div>

      <button class="button-secondary" @click="showForm = !showForm">
        Add Office Hours
      </button>
    </div>

    <form
      v-if="showForm"
      class="card space-y-4 p-5"
      @submit.prevent="submitForm"
    >
      <div class="grid gap-3 md:grid-cols-2">
        <div class="space-y-1 w-full">
          <label for="office-hour-ta-name" class="text-sm font-medium text-slate-200">
            TA Name
          </label>
          <input
            id="office-hour-ta-name"
            v-model="form.ta_name"
            required
            class="input w-full min-w-0"
            placeholder="TA name"
          />
        </div>
        <div class="space-y-1 w-full">
          <label for="office-hour-location" class="text-sm font-medium text-slate-200">
            Location
          </label>
          <input
            id="office-hour-location"
            v-model="form.location"
            required
            class="input w-full min-w-0"
            placeholder="Location"
          />
        </div>
      </div>

      <div class="grid gap-3 md:grid-cols-3">
        <div class="space-y-1 w-full">
          <label for="office-hour-date" class="text-sm font-medium text-slate-200">
            Date
          </label>
          <input
            id="office-hour-date"
            v-model="form.date"
            required
            class="input w-full min-w-0"
            type="date"
          />
        </div>
        <div class="space-y-1 w-full">
          <label for="office-hour-start-time" class="text-sm font-medium text-slate-200">
            Start Time
          </label>
          <input
            id="office-hour-start-time"
            v-model="form.time"
            required
            class="input w-full min-w-0"
            type="time"
          />
        </div>
        <div class="space-y-1 w-full">
          <label for="office-hour-end-time" class="text-sm font-medium text-slate-200">
            End Time
          </label>
          <input
            id="office-hour-end-time"
            v-model="form.end_time"
            required
            class="input w-full min-w-0"
            type="time"
          />
        </div>
      </div>

      <button class="button-primary w-full" type="submit">
        Save Office Hour
      </button>
    </form>

    <div class="space-y-3">
      <div v-for="week in calendarWeeks" :key="week.weekNumber">
        <p
          class="mb-2 text-sm font-semibold uppercase tracking-wider text-uva-orange"
        >
          Week {{ week.weekNumber }}
        </p>

        <!-- Fixed 7-column layout keeps every day box identical size -->
        <div class="grid grid-cols-1 gap-2 md:grid-cols-7">
          <div
            v-for="day in week.days"
            :key="day.key"
            :id="day.inMonth ? `day-${day.key}` : undefined"
            class="min-h-36 rounded-xl border border-white/20 bg-white/10 p-3 shadow-sm transition"
            :class="day.inMonth ? 'opacity-100' : 'pointer-events-none opacity-0'"
          >
            <p
              v-if="day.inMonth"
              class="mb-2 text-sm font-semibold text-slate-100"
            >
              {{ day.label }}
            </p>

            <div v-if="day.inMonth" class="space-y-2">
              <div
                v-for="slot in day.slots"
                :key="slot.id"
                class="rounded-lg border border-white/20 bg-white/5 p-2 text-xs text-slate-100"
              >
                <p class="font-semibold">{{ slot.ta_name }}</p>
                <p>{{ formatTimeRangeFromSlot(slot) }} · {{ slot.location }}</p>
                <p class="text-uva-orange">
                  Attendees: {{ slot.attendance_count }}
                </p>
                <div class="mt-2 flex flex-wrap gap-1">
                  <button
                    class="flex-auto rounded px-1 py-1 text-center text-white transition-all duration-200"
                    :class="
                      joinedSessions.includes(slot.id)
                        ? 'bg-slate-600 hover:bg-slate-500'
                        : 'bg-uva-orange'
                    "
                    @click="toggleJoin(slot.id)"
                  >
                    {{
                      joinedSessions.includes(slot.id) ? "Unjoin" : "Join"
                    }}
                  </button>
                  <button
                    class="flex-auto rounded bg-slate-600 px-1 py-1 text-center text-white"
                    @click="startEdit(slot)"
                  >
                    Edit
                  </button>
                  <button
                    class="flex-auto rounded bg-red-600 px-2 py-1 text-center text-white"
                    @click="remove(slot.id)"
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
    </div>
  </section>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from "vue";
import { api } from "../lib/api";
import {
  officeHours,
  joinedSessions,
  fetchOfficeHours,
  removeOfficeHourFromStore,
  pushJoinedSession,
  removeJoinedSession,
} from "../composables/useOfficeHours";

const showForm = ref(false);
const editingId = ref(null);
const searchQuery = ref("");
const searchInputEl = ref(null);
const searchFocused = ref(false);
const highlightedIndex = ref(0);
const highlightedDate = ref(null);
const form = reactive({ ta_name: "", location: "", date: "", time: "", end_time: "" });
let searchCloseTimer = null;

const resetForm = () => {
  form.ta_name = "";
  form.location = "";
  form.date = "";
  form.time = "";
  form.end_time = "";
  editingId.value = null;
};

const submitForm = async () => {
  const payload = { ...form };
  if (editingId.value) {
    await api.put(`/office-hours/${editingId.value}`, payload);
  } else {
    await api.post("/office-hours", payload);
  }
  resetForm();
  showForm.value = false;
  await fetchOfficeHours();
};

const startEdit = (slot) => {
  showForm.value = true;
  editingId.value = slot.id;
  form.ta_name = slot.ta_name;
  form.location = slot.location;
  form.date = slot.date;
  form.time = slot.time.slice(0, 5);
  if (slot.end_time) {
    form.end_time = slot.end_time.slice(0, 5);
  } else {
    // backward-compatible default if older rows exist
    form.end_time = guessEndTime(slot.time, slot.duration_minutes ?? 60);
  }
};

const remove = async (id) => {
  await api.delete(`/office-hours/${id}`);
  removeOfficeHourFromStore(id);
  await fetchOfficeHours();
};

const toggleJoin = async (id) => {
  const isJoined = joinedSessions.value.includes(id);

  try {
    if (isJoined) {
      await api.delete(`/office-hours/${id}/join`);
      removeJoinedSession(id);
    } else {
      await api.post(`/office-hours/${id}/join`);
      pushJoinedSession(id);
    }
    await fetchOfficeHours();
  } catch (error) {
    console.error("An error occurred while updating attendance:", error);
    // If the API call failed, resync counts/state from server.
    await fetchOfficeHours();
  }
};

const formatTimeRange = (startTime, endTime) => {
  const [hh, mm] = startTime.slice(0, 5).split(":").map(Number);
  const start = new Date(2000, 0, 1, hh, mm, 0, 0);
  const [eh, em] = endTime.slice(0, 5).split(":").map(Number);
  const end = new Date(2000, 0, 1, eh, em, 0, 0);

  const fmt = new Intl.DateTimeFormat(undefined, { hour: "numeric", minute: "2-digit" });
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
  const end = slot.end_time ?? guessEndTime(slot.time, slot.duration_minutes ?? 60);
  return formatTimeRange(slot.time, end);
};

const dateLabel = (yyyyMmDd) => new Date(`${yyyyMmDd}T00:00:00`).toLocaleDateString(undefined, { month: "short", day: "numeric" });

const normalizedQuery = computed(() => searchQuery.value.trim().toLowerCase());

const suggestions = computed(() => {
  if (!normalizedQuery.value) return [];

  const q = normalizedQuery.value;
  return officeHours.value
    .filter((slot) => {
      const hay = `${slot.ta_name} ${slot.location} ${slot.date} ${slot.time} ${slot.end_time ?? ""}`.toLowerCase();
      return hay.includes(q);
    })
    .slice(0, 8)
    .map((slot) => ({
      ...slot,
      dateLabel: dateLabel(slot.date),
      timeRange: formatTimeRangeFromSlot(slot),
    }));
});

const showSuggestions = computed(() => searchFocused.value && normalizedQuery.value.length > 0);

const closeSearch = () => {
  searchFocused.value = false;
  highlightedIndex.value = 0;
};

const onSearchFocus = () => {
  if (searchCloseTimer) {
    clearTimeout(searchCloseTimer);
    searchCloseTimer = null;
  }
  searchFocused.value = true;
};

const onSearchBlur = () => {
  // allow click selection before closing
  searchCloseTimer = setTimeout(() => closeSearch(), 160);
};

const moveSelection = (delta) => {
  if (!suggestions.value.length) return;
  const next = highlightedIndex.value + delta;
  highlightedIndex.value = (next + suggestions.value.length) % suggestions.value.length;
};

const selectHighlighted = () => {
  const s = suggestions.value[highlightedIndex.value];
  if (s) goToOfficeHour(s);
};

const goToOfficeHour = async (slot) => {
  // Keep the search usable after selection
  searchQuery.value = "";
  highlightedDate.value = slot.date;
  highlightedIndex.value = 0;

  await nextTick();
  const el = document.getElementById(`day-${slot.date}`);
  if (el) {
    el.scrollIntoView({ behavior: "smooth", block: "center" });
    el.classList.add("ring-2", "ring-uva-orange/70");
    setTimeout(() => el.classList.remove("ring-2", "ring-uva-orange/70"), 1800);
  }

  await nextTick();
  if (searchInputEl.value) {
    searchInputEl.value.focus();
    searchFocused.value = true;
  }
};

watch(normalizedQuery, () => {
  highlightedIndex.value = 0;
});

const toLocalYmd = (d) => {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
};

const calendarWeeks = computed(() => {
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();
  const lastDayDate = new Date(year, month + 1, 0);

  // week starts on Sunday (0) to match JS getDay()
  const firstDayWeekday = new Date(year, month, 1).getDay();
  const firstWeekStart = new Date(year, month, 1 - firstDayWeekday);

  const weeks = [];
  let weekStart = new Date(firstWeekStart);
  let weekNumber = 1;

  while (weekStart <= lastDayDate) {
    // Always build a full 7-cell week so widths never change.
    const weekSlots = Array.from({ length: 7 }).map((_, idx) => {
      const d = new Date(
        weekStart.getFullYear(),
        weekStart.getMonth(),
        weekStart.getDate() + idx,
      );

      const inMonth = d.getMonth() === month;
      if (!inMonth) {
        return {
          key: `empty-${weekNumber}-${idx}`,
          inMonth: false,
          label: "",
          slots: [],
        };
      }

      const dateStr = toLocalYmd(d);
      const label = d.toLocaleDateString(undefined, {
        month: "short",
        day: "numeric",
      });

      return {
        key: dateStr,
        inMonth: true,
        label,
        slots: officeHours.value.filter(
          (slot) => slot.date === dateStr,
        ),
      };
    });

    const visibleCount = weekSlots.filter((d) => d.inMonth).length;

    // If only 3 or fewer days exist in this week (end of month),
    // center them within the 7-day row.
    if (visibleCount > 0 && visibleCount <= 3) {
      const visibleDays = weekSlots.filter((d) => d.inMonth);
      const startCol = Math.floor((7 - visibleCount) / 2);

      const centered = Array.from({ length: 7 }).map((_, idx) => ({
        key: `empty-${weekNumber}-${idx}`,
        inMonth: false,
        label: "",
        slots: [],
      }));

      for (let i = 0; i < visibleDays.length; i++) {
        centered[startCol + i] = visibleDays[i];
      }

      weeks.push({ weekNumber, days: centered });
    } else {
      weeks.push({ weekNumber, days: weekSlots });
    }

    weekNumber += 1;
    weekStart = new Date(
      weekStart.getFullYear(),
      weekStart.getMonth(),
      weekStart.getDate() + 7,
    );
  }

  return weeks;
});

onMounted(fetchOfficeHours);
</script>
