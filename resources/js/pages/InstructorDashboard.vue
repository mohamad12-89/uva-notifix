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

      <div class="mt-8 space-y-4">
        <h4 class="font-medium text-slate-200">
          Weekly Busiest Join Times (Student Join Actions)
        </h4>
        <p class="text-sm text-slate-400">
          Suggested office-hour windows based on when students actually join sessions.
        </p>
        <div
          class="rounded-lg border border-white/10 bg-slate-900/50 p-4"
        >
          <div v-if="weeklyJoinHeatmap.length" class="space-y-4">
            <div class="flex flex-wrap gap-2">
              <button
                v-for="day in weekdayLabels"
                :key="day.num"
                type="button"
                class="rounded border px-3 py-1 text-xs font-semibold transition"
                :class="
                  selectedJoinDay === day.num
                    ? 'border-uva-orange bg-uva-orange/20 text-uva-orange'
                    : 'border-white/20 bg-slate-800/50 text-slate-300 hover:border-uva-orange/50'
                "
                @click="selectedJoinDay = day.num"
              >
                {{ day.short }}
              </button>
            </div>

            <div class="overflow-x-auto">
              <div class="min-w-[760px]">
                <div class="mb-2 flex gap-2">
                  <div class="h-48 w-10 shrink-0 pb-3 pt-5">
                    <div class="relative h-full">
                      <div
                        v-for="tick in chartScaleTicks"
                        :key="`tick-${tick}`"
                        class="absolute right-0 -translate-y-1/2 text-[10px] leading-none text-slate-400"
                        :style="{
                          top: `calc(${100 - (tick / CHART_SCALE_MAX) * 100}% + ${chartVerticalOffsetPx}px)`,
                        }"
                      >
                        {{ tick }}
                      </div>
                    </div>
                  </div>
                  <div class="relative h-48 flex-1 rounded border border-white/10 bg-slate-950/50 p-3">
                    <div class="pointer-events-none absolute bottom-3 left-3 right-3 top-5">
                      <div
                        v-for="tick in chartScaleTicks"
                        :key="`grid-${tick}`"
                        class="absolute left-0 right-0 border-t border-white/10"
                        :style="{
                          top: `calc(${100 - (tick / CHART_SCALE_MAX) * 100}% + ${chartVerticalOffsetPx}px)`,
                        }"
                      ></div>
                    </div>
                    <div class="absolute bottom-3 left-3 right-3 top-5 z-10 flex items-stretch gap-2">
                      <div
                        v-for="bar in selectedDayBarsWithHeight"
                        :key="bar.hour"
                        class="flex h-full flex-1 flex-col items-center justify-end"
                      >
                        <div
                          class="w-full rounded-t bg-gradient-to-t from-uva-blue to-uva-orange"
                          :style="{
                            height: `${bar.heightPct}%`,
                            minHeight: bar.joinCount > 0 ? '6px' : '0px',
                          }"
                          :title="`${selectedJoinDayLabel} ${bar.label}: ${bar.joinCount} joins`"
                        ></div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="flex gap-2">
                  <div class="w-10 shrink-0"></div>
                  <div
                    v-for="bar in selectedDayBarsWithHeight"
                    :key="`${bar.hour}-label`"
                    class="flex-1 text-center text-[10px] text-slate-400"
                  >
                    {{ bar.shortLabel }}
                  </div>
                </div>
              </div>
            </div>

            <p class="text-sm text-slate-300">
              <span class="font-semibold text-white">Suggestion:</span>
              <span v-if="busiestSelectedDayBar">
                {{ selectedJoinDayLabel }} {{ busiestSelectedDayBar.label }} is the busiest slot with
                <span class="font-semibold text-uva-orange">
                  {{ busiestSelectedDayBar.joinCount }} joins
                </span>
              </span>
              <span v-else>
                No join data for {{ selectedJoinDayLabel }} yet.
              </span>
            </p>

            <div v-if="weeklyJoinPeaks.length">
              <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                Top Suggested Slots
              </p>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="slot in weeklyJoinPeaks.slice(0, 6)"
                  :key="slot.label"
                  class="rounded border border-uva-orange/40 bg-uva-orange/15 px-2 py-1 text-xs text-uva-orange"
                >
                  {{ slot.label }} ({{ slot.join_count }})
                </span>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-slate-400">
            Not enough join data yet.
          </p>
        </div>
      </div>

      <div class="mt-8 space-y-4">
        <h4 class="font-medium text-slate-200">
          Student Signups vs Checked-In Attendance
        </h4>
        <div class="max-w-md">
          <input
            v-model.trim="studentSearch"
            type="text"
            class="input w-full"
            placeholder="Search student name or email..."
          />
        </div>
        <div
          class="max-h-80 overflow-auto rounded-lg border border-white/10 bg-slate-900/50"
        >
          <table class="w-full text-left text-xs text-slate-300">
            <thead
              class="border-b border-white/10 bg-slate-800/50 text-xs uppercase text-slate-400"
            >
              <tr>
                <th scope="col" class="px-4 py-3">Student</th>
                <th scope="col" class="px-4 py-3">Email</th>
                <th scope="col" class="px-4 py-3 text-right">Signed Up</th>
                <th scope="col" class="px-4 py-3 text-right">Checked In</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="student in filteredRecentStudentStats"
                :key="student.student_email"
                class="border-b border-white/5 hover:bg-white/5"
              >
                <td class="px-4 py-3 font-medium text-white">
                  {{ student.student_name }}
                </td>
                <td class="px-4 py-3">{{ student.student_email }}</td>
                <td class="px-4 py-3 text-right">{{ student.signed_up_count }}</td>
                <td class="px-4 py-3 text-right">{{ student.attended_count }}</td>
              </tr>
              <tr v-if="!filteredRecentStudentStats.length">
                <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                  No recent student entries found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-xs text-slate-500">
          Showing the most recent {{ RECENT_STUDENT_LIMIT }} student entries.
        </p>
      </div>

      <div class="mt-8 space-y-8">
        <div class="space-y-4">
          <h4 class="font-medium text-slate-200">TA access emails</h4>
          <p class="text-sm text-slate-400">
            People listed here get the TA view when they sign in. Built-in TA emails
            cannot be removed.
          </p>
          <form
            class="flex flex-col gap-2 sm:flex-row sm:items-end"
            @submit.prevent="submitTaEmail"
          >
            <input
              v-model.trim="taInput"
              type="email"
              class="input flex-1"
              placeholder="Add TA @virginia.edu"
            />
            <button type="submit" class="button-primary shrink-0">Add TA email</button>
          </form>
          <p v-if="taFormError" class="text-sm text-red-400">{{ taFormError }}</p>
          <p v-if="taFormOk" class="text-sm text-green-400">{{ taFormOk }}</p>
          <div
            class="overflow-hidden rounded-lg border border-white/10 bg-slate-900/50"
          >
            <table class="w-full text-left text-sm text-slate-300">
              <thead
                class="border-b border-white/10 bg-slate-800/50 text-xs uppercase text-slate-400"
              >
                <tr>
                  <th class="px-4 py-3">Email</th>
                  <th class="px-4 py-3">Notes</th>
                  <th class="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in taRows"
                  :key="row.email"
                  class="border-b border-white/5 hover:bg-white/5"
                >
                  <td class="px-4 py-3 font-medium text-white">{{ row.email }}</td>
                  <td class="px-4 py-3 text-slate-400">
                    {{ row.isDefault ? "Built-in TA" : "Added by professor" }}
                  </td>
                  <td class="px-4 py-3 text-right">
                    <button
                      v-if="!row.isDefault"
                      type="button"
                      class="rounded bg-red-600/90 px-3 py-1 text-xs text-white"
                      @click="removeTa(row.email)"
                    >
                      Remove
                    </button>
                    <span v-else class="text-xs text-slate-500">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="space-y-4">
          <h4 class="font-medium text-slate-200">Professor access emails</h4>
          <p class="text-sm text-slate-400">
            People listed here get the full professor view, including this dashboard.
            Built-in professor emails cannot be removed.
          </p>
          <form
            class="flex flex-col gap-2 sm:flex-row sm:items-end"
            @submit.prevent="submitProfessorEmail"
          >
            <input
              v-model.trim="professorInput"
              type="email"
              class="input flex-1"
              placeholder="Add professor @virginia.edu"
            />
            <button type="submit" class="button-primary shrink-0">
              Add professor email
            </button>
          </form>
          <p v-if="professorFormError" class="text-sm text-red-400">
            {{ professorFormError }}
          </p>
          <p v-if="professorFormOk" class="text-sm text-green-400">
            {{ professorFormOk }}
          </p>
          <div
            class="overflow-hidden rounded-lg border border-white/10 bg-slate-900/50"
          >
            <table class="w-full text-left text-sm text-slate-300">
              <thead
                class="border-b border-white/10 bg-slate-800/50 text-xs uppercase text-slate-400"
              >
                <tr>
                  <th class="px-4 py-3">Email</th>
                  <th class="px-4 py-3">Notes</th>
                  <th class="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in professorRows"
                  :key="row.email"
                  class="border-b border-white/5 hover:bg-white/5"
                >
                  <td class="px-4 py-3 font-medium text-white">{{ row.email }}</td>
                  <td class="px-4 py-3 text-slate-400">
                    {{ row.isDefault ? "Built-in professor" : "Added by professor" }}
                  </td>
                  <td class="px-4 py-3 text-right">
                    <button
                      v-if="!row.isDefault"
                      type="button"
                      class="rounded bg-red-600/90 px-3 py-1 text-xs text-white"
                      @click="removeProfessor(row.email)"
                    >
                      Remove
                    </button>
                    <span v-else class="text-xs text-slate-500">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { api } from "../lib/api";
import { syncProfessorRoleRegistryToApi } from "../lib/roleRegistrySync";
import {
  listTaEmailsForDisplay,
  listProfessorEmailsForDisplay,
  addExtraTaEmail,
  removeExtraTaEmail,
  addExtraProfessorEmail,
  removeExtraProfessorEmail,
  notifyRoleRegistryUpdated,
} from "../composables/useAuthProfile";

const announcementForm = reactive({ title: "", body: "" });
const isPosting = ref(false);
const postSuccess = ref(false);

const analyticsData = ref([]);
const activeSessions = ref(0);
const studentStats = ref([]);
const weeklyJoinHeatmap = ref([]);
const weeklyJoinPeaks = ref([]);
const studentSearch = ref("");
const RECENT_STUDENT_LIMIT = 20;
const CHART_SCALE_MAX = 30;
const chartVerticalOffsetPx = 0;
const selectedJoinDay = ref(1);
const weekdayLabels = [
  { num: 1, short: "Mon", full: "Monday" },
  { num: 2, short: "Tue", full: "Tuesday" },
  { num: 3, short: "Wed", full: "Wednesday" },
  { num: 4, short: "Thu", full: "Thursday" },
  { num: 5, short: "Fri", full: "Friday" },
  { num: 6, short: "Sat", full: "Saturday" },
  { num: 7, short: "Sun", full: "Sunday" },
];

const taInput = ref("");
const professorInput = ref("");
const taFormError = ref("");
const taFormOk = ref("");
const professorFormError = ref("");
const professorFormOk = ref("");

const taRows = ref(listTaEmailsForDisplay());
const professorRows = ref(listProfessorEmailsForDisplay());

function refreshRoleRows() {
  taRows.value = listTaEmailsForDisplay();
  professorRows.value = listProfessorEmailsForDisplay();
  notifyRoleRegistryUpdated();
  void syncProfessorRoleRegistryToApi();
}

function submitTaEmail() {
  taFormError.value = "";
  taFormOk.value = "";
  const r = addExtraTaEmail(taInput.value);
  if (!r.ok) {
    taFormError.value = r.message || "Could not add email.";
    return;
  }
  taInput.value = "";
  taFormOk.value = "TA email added.";
  refreshRoleRows();
}

function removeTa(email) {
  taFormError.value = "";
  taFormOk.value = "";
  const r = removeExtraTaEmail(email);
  if (!r.ok) {
    taFormError.value = r.message || "Could not remove.";
    return;
  }
  taFormOk.value = "TA email removed.";
  refreshRoleRows();
}

function submitProfessorEmail() {
  professorFormError.value = "";
  professorFormOk.value = "";
  const r = addExtraProfessorEmail(professorInput.value);
  if (!r.ok) {
    professorFormError.value = r.message || "Could not add email.";
    return;
  }
  professorInput.value = "";
  professorFormOk.value = "Professor email added.";
  refreshRoleRows();
}

function removeProfessor(email) {
  professorFormError.value = "";
  professorFormOk.value = "";
  const r = removeExtraProfessorEmail(email);
  if (!r.ok) {
    professorFormError.value = r.message || "Could not remove.";
    return;
  }
  professorFormOk.value = "Professor email removed.";
  refreshRoleRows();
}

const fetchAnalytics = async () => {
  try {
    const response = await api.get("/analytics/office-hours");
    analyticsData.value = response.data.analytics || response.data;
    if (response.data.activeSessions !== undefined) {
      activeSessions.value = response.data.activeSessions;
    }
    studentStats.value = response.data.studentStats || [];
    weeklyJoinHeatmap.value = response.data.weeklyJoinHeatmap || [];
    weeklyJoinPeaks.value = response.data.weeklyJoinPeaks || [];
  } catch (error) {
    console.error("Failed to fetch analytics", error);
  }
};

const heatmapCounts = computed(() => {
  const map = new Map();
  for (const entry of weeklyJoinHeatmap.value) {
    map.set(`${entry.weekday_num}-${entry.hour_24}`, Number(entry.join_count) || 0);
  }
  return map;
});

const hourAxis = computed(() => {
  // Keep a consistent, fuller axis so professors can scan a realistic daily window.
  const startHour = 6; // 6 AM
  const endHour = 23; // 11 PM
  return Array.from({ length: endHour - startHour + 1 }, (_, idx) => startHour + idx);
});

const formatHour = (hour24) => {
  const suffix = hour24 >= 12 ? "PM" : "AM";
  const h = hour24 % 12 || 12;
  return `${h} ${suffix}`;
};

const selectedJoinDayLabel = computed(
  () => weekdayLabels.find((d) => d.num === selectedJoinDay.value)?.full || "Selected day",
);

const selectedDayBars = computed(() => {
  return hourAxis.value.map((hour) => {
    const joinCount = heatmapCounts.value.get(`${selectedJoinDay.value}-${hour}`) || 0;
    return {
    hour,
      label: formatHour(hour),
      shortLabel: formatHour(hour).replace(" ", ""),
      joinCount,
      heightPct: 0,
    };
  });
});

const chartScaleTicks = [5, 10, 15, 20, 25, 30];

const selectedDayBarsWithHeight = computed(() => {
  return selectedDayBars.value.map((bar) => ({
    ...bar,
    heightPct: bar.joinCount
      ? Math.round((Math.min(bar.joinCount, CHART_SCALE_MAX) / CHART_SCALE_MAX) * 100)
      : 0,
  }));
});

const busiestSelectedDayBar = computed(() =>
  selectedDayBars.value.reduce(
    (max, item) => (item.joinCount > (max?.joinCount || 0) ? item : max),
    null,
  ),
);

const filteredRecentStudentStats = computed(() => {
  const q = studentSearch.value.toLowerCase();
  const list = studentStats.value || [];
  const filtered = q
    ? list.filter((s) => {
        const hay = `${s.student_name || ""} ${s.student_email || ""}`.toLowerCase();
        return hay.includes(q);
      })
    : list;
  return filtered.slice(0, RECENT_STUDENT_LIMIT);
});

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
  void syncProfessorRoleRegistryToApi();
});
</script>
