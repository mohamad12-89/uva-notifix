<template>
  <section class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-3xl font-bold text-uva-orange">Instructor Dashboard</h2>
    </div>

    <!-- Announcements Section -->
    <div class="card p-6">
      <h3 class="mb-4 text-xl font-semibold text-white">
        Program-Wide Announcements
      </h3>
      <form @submit.prevent="postAnnouncement" class="space-y-4">
        <div>
          <label
            for="announcement-title"
            class="mb-1 block text-sm font-medium text-slate-200"
            >Title</label
          >
          <input
            id="announcement-title"
            v-model="announcementForm.title"
            type="text"
            required
            class="input w-full"
            placeholder="Announcement Title"
          />
        </div>
        <div>
          <label
            for="announcement-body"
            class="mb-1 block text-sm font-medium text-slate-200"
            >Message</label
          >
          <textarea
            id="announcement-body"
            v-model="announcementForm.body"
            required
            rows="3"
            class="input w-full resize-none"
            placeholder="Type your message to all first-year engineering students here..."
          ></textarea>
        </div>
        <button type="submit" class="button-primary" :disabled="isPosting">
          {{ isPosting ? "Posting..." : "Post Announcement" }}
        </button>
      </form>
      <div v-if="postSuccess" class="mt-3 text-sm text-green-400">
        Announcement posted successfully!
      </div>
    </div>

    <!-- Analytics & Tracking Section -->
    <div class="card p-6">
      <div
        class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
      >
        <h3 class="text-xl font-semibold text-white">
          Data Analytics & Usage Tracking
        </h3>
        <button
          @click="exportCSV"
          class="button-secondary flex items-center gap-2"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-3 3m0 0l-3-3m3 3V4"
            />
          </svg>
          Export CSV
        </button>
      </div>

      <!-- Quick Stats -->
      <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div
          class="rounded-xl border border-white/10 bg-slate-800/50 p-4 text-center"
        >
          <p class="text-sm text-slate-400">Total Attendance (This Week)</p>
          <p class="text-3xl font-bold text-uva-orange">
            {{ totalAttendance }}
          </p>
        </div>
        <div
          class="rounded-xl border border-white/10 bg-slate-800/50 p-4 text-center"
        >
          <p class="text-sm text-slate-400">Active Office Hours (Real-time)</p>
          <p class="text-3xl font-bold text-uva-orange">{{ activeSessions }}</p>
        </div>
        <div
          class="rounded-xl border border-white/10 bg-slate-800/50 p-4 text-center"
        >
          <p class="text-sm text-slate-400">Most Active TA</p>
          <p class="mt-1 text-lg font-bold text-uva-orange">{{ topTa }}</p>
        </div>
      </div>

      <!-- Weekly TA Attendance -->
      <div class="space-y-4">
        <h4 class="font-medium text-slate-200">Attendance by Week & TA</h4>
        <div
          class="overflow-hidden rounded-lg border border-white/10 bg-slate-900/50"
        >
          <table class="w-full text-left text-sm text-slate-300">
            <thead
              class="border-b border-white/10 bg-slate-800/50 text-xs uppercase text-slate-400"
            >
              <tr>
                <th scope="col" class="px-4 py-3">Week</th>
                <th scope="col" class="px-4 py-3">TA Name</th>
                <th scope="col" class="px-4 py-3 text-right">
                  Attendance Volume
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="stat in analyticsData"
                :key="stat.id"
                class="border-b border-white/5 hover:bg-white/5"
              >
                <td class="px-4 py-3 font-medium text-white">
                  {{ stat.week }}
                </td>
                <td class="px-4 py-3">{{ stat.ta_name }}</td>
                <td class="px-4 py-3 text-right">{{ stat.attendance }}</td>
              </tr>
              <tr v-if="!analyticsData.length">
                <td colspan="3" class="px-4 py-8 text-center text-slate-500">
                  No data available for the selected period.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { api } from "../lib/api";

const announcementForm = reactive({ title: "", body: "" });
const isPosting = ref(false);
const postSuccess = ref(false);

const analyticsData = ref([]);
const activeSessions = ref(0);

const fetchAnalytics = async () => {
  try {
    const response = await api.get("/analytics/office-hours");
    analyticsData.value = response.data.analytics || response.data;
    if (response.data.activeSessions !== undefined) {
      activeSessions.value = response.data.activeSessions;
    }
  } catch (error) {
    console.error("Failed to fetch analytics", error);
  }
};

const totalAttendance = computed(() => {
  return analyticsData.value.reduce((sum, item) => sum + item.attendance, 0);
});

const topTa = computed(() => {
  if (!analyticsData.value.length) return "N/A";
  const sorted = [...analyticsData.value].sort(
    (a, b) => b.attendance - a.attendance,
  );
  return sorted[0].ta_name;
});

const postAnnouncement = async () => {
  isPosting.value = true;
  postSuccess.value = false;
  try {
    await api.post("/announcements", announcementForm);

    postSuccess.value = true;
    announcementForm.title = "";
    announcementForm.body = "";
    setTimeout(() => {
      postSuccess.value = false;
    }, 3000);
  } catch (error) {
    console.error("Failed to post announcement", error);
  } finally {
    isPosting.value = false;
  }
};

const exportCSV = () => {
  // Define CSV headers and generate rows
  const headers = ["Week", "TA Name", "Attendance Volume"];
  const rows = analyticsData.value.map((stat) => [
    `"${stat.week}"`,
    `"${stat.ta_name}"`,
    stat.attendance,
  ]);

  // Combine into a CSV string
  const csvContent = [
    headers.join(","),
    ...rows.map((row) => row.join(",")),
  ].join("\n");

  // Create a Blob and trigger a download via a temporary link
  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
  const link = document.createElement("a");
  const url = URL.createObjectURL(blob);

  link.setAttribute("href", url);
  link.setAttribute(
    "download",
    `office-hours-analytics-${new Date().toISOString().split("T")[0]}.csv`,
  );
  link.style.visibility = "hidden";

  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

onMounted(() => {
  fetchAnalytics();
});
</script>
