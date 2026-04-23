<template>
  <section class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-3xl font-bold text-uva-orange">TA Dashboard</h2>
    </div>

    <div class="card p-6">
      <h4 class="font-medium text-slate-200">
        Weekly Busiest Join Times (Student Join Actions)
      </h4>
      <p class="mt-1 text-sm text-slate-400">
        Suggested office-hour windows based on when students actually join sessions.
      </p>

      <div class="mt-4 rounded-lg border border-white/10 bg-slate-900/50 p-4">
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
                      :style="{ top: `${100 - (tick / CHART_SCALE_MAX) * 100}%` }"
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
                      :style="{ top: `${100 - (tick / CHART_SCALE_MAX) * 100}%` }"
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
        </div>
        <p v-else class="text-sm text-slate-400">Not enough join data yet.</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { api } from "../lib/api";

const weeklyJoinHeatmap = ref([]);
const selectedJoinDay = ref(1);
const CHART_SCALE_MAX = 30;
const chartScaleTicks = [5, 10, 15, 20, 25, 30];
const weekdayLabels = [
  { num: 1, short: "Mon", full: "Monday" },
  { num: 2, short: "Tue", full: "Tuesday" },
  { num: 3, short: "Wed", full: "Wednesday" },
  { num: 4, short: "Thu", full: "Thursday" },
  { num: 5, short: "Fri", full: "Friday" },
  { num: 6, short: "Sat", full: "Saturday" },
  { num: 7, short: "Sun", full: "Sunday" },
];

const fetchJoinTimes = async () => {
  try {
    const response = await api.get("/analytics/join-times");
    weeklyJoinHeatmap.value = response.data.weeklyJoinHeatmap || [];
  } catch (error) {
    console.error("Failed to fetch TA join-times analytics", error);
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
  const startHour = 6;
  const endHour = 23;
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

onMounted(fetchJoinTimes);
</script>
