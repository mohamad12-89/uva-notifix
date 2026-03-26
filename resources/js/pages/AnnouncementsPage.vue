<template>
  <section class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-3xl font-bold text-uva-orange">Announcements</h2>
    </div>

    <div v-if="loading" class="text-white">Loading announcements...</div>
    <div v-else-if="announcements.length === 0" class="text-white">
      No announcements have been posted yet.
    </div>

    <div v-else class="space-y-6">
      <div
        v-for="announcement in announcements"
        :key="announcement.id"
        class="card p-6"
      >
        <!-- Announcement Header -->
        <div
          class="mb-2 flex items-center justify-between border-b border-white/10 pb-2"
        >
          <h3 class="text-xl font-semibold text-white">
            {{ announcement.title }}
          </h3>
          <span class="text-sm text-slate-400">{{
            formatDate(announcement.created_at)
          }}</span>
        </div>

        <!-- Author & Body -->
        <p class="mb-4 text-sm text-slate-400">
          Posted by:
          <span class="font-medium text-slate-200">{{
            announcement.author_name || "Instructor"
          }}</span>
        </p>
        <p class="whitespace-pre-wrap text-slate-200">
          {{ announcement.body }}
        </p>

        <!-- Linked Office Hour Session -->
        <div
          v-if="announcement.office_hour"
          class="mt-6 rounded-lg border border-uva-orange/30 bg-slate-800/50 p-4"
        >
          <h4
            class="mb-3 text-sm font-semibold uppercase tracking-wider text-uva-orange"
          >
            Updated Office Hour Session
          </h4>
          <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              <p class="text-lg font-medium text-white">
                {{ announcement.office_hour.ta_name }}
              </p>
              <p class="text-sm text-slate-300">
                {{ formatDate(announcement.office_hour.date) }} •
                {{ announcement.office_hour.time }} -
                {{ announcement.office_hour.end_time }}
              </p>
              <p class="text-sm text-slate-300">
                Location: {{ announcement.office_hour.location }}
              </p>
            </div>

            <!-- Join/Unjoin Controls -->
            <div class="flex items-center gap-3">
              <span class="text-sm font-medium text-slate-300">
                {{ announcement.office_hour.attendance_count }} attending
              </span>
              <button
                @click="toggleJoin(announcement.office_hour)"
                :class="[
                  'rounded-md px-4 py-2 text-sm font-medium transition-colors',
                  isJoined(announcement.office_hour.id)
                    ? 'bg-red-500/20 text-red-400 hover:bg-red-500/30'
                    : 'bg-uva-orange text-white hover:bg-uva-orange/90',
                ]"
              >
                {{ isJoined(announcement.office_hour.id) ? "Unjoin" : "Join" }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { api } from "../lib/api";
import {
  joinedSessions,
  pushJoinedSession,
  removeJoinedSession,
} from "../composables/useOfficeHours";

const announcements = ref([]);
const loading = ref(true);

const fetchAnnouncements = async () => {
  try {
    const response = await api.get("/announcements");

    // Prevent iterating over an HTML 404 error string if the API fails
    announcements.value = Array.isArray(response.data) ? response.data : [];
  } catch (error) {
    console.error("Failed to fetch announcements:", error);
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return "";
  const date = new Date(dateStr);
  return date.toLocaleDateString(undefined, {
    weekday: "short",
    month: "short",
    day: "numeric",
    year: "numeric",
  });
};

const isJoined = (id) => joinedSessions.value.includes(id);

const toggleJoin = async (officeHour) => {
  try {
    if (isJoined(officeHour.id)) {
      await api.delete(`/office-hours/${officeHour.id}/join`);
      removeJoinedSession(officeHour.id);
      if (officeHour.attendance_count > 0) officeHour.attendance_count--;
    } else {
      await api.post(`/office-hours/${officeHour.id}/join`);
      pushJoinedSession(officeHour.id);
      officeHour.attendance_count++;
    }
  } catch (error) {
    console.error("Failed to toggle join status:", error);
  }
};

onMounted(() => {
  fetchAnnouncements();
});
</script>
