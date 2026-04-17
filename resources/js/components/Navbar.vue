<template>
  <nav
    class="sticky top-0 z-20 border-b border-white/20 bg-uva-blue/70 text-white shadow-2xl backdrop-blur-md"
  >
    <div
      class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8"
    >
      <RouterLink
        to="/"
        class="brand-link rounded-lg px-3 py-2 text-xl font-bold tracking-wide text-white transition-all duration-300 hover:text-uva-orange"
      >
        Notifix
      </RouterLink>
      <div v-if="visibleNavItems.length" class="flex flex-wrap gap-2 text-sm">
        <RouterLink
          v-for="item in visibleNavItems"
          :key="item.to"
          :to="item.to"
          :class="route.path === item.to ? activeLinkClass : inactiveLinkClass"
        >
          {{ item.label }}
        </RouterLink>
        <button @click="isSupportModalOpen = true" :class="inactiveLinkClass">
          Support
        </button>
      </div>
      <RouterLink
        v-if="authProfile?.verified"
        to="/profile"
        class="ml-3 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-uva-orange/60 bg-uva-orange/15 text-sm font-bold text-uva-orange shadow-[0_0_18px_rgba(248,76,30,0.25)]"
        :class="route.path === '/profile' ? 'ring-2 ring-uva-orange/70' : ''"
        :title="authProfile?.email || 'Open profile settings'"
      >
        {{ initials }}
      </RouterLink>
    </div>
  </nav>

  <div
    v-if="isSupportModalOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
    @click.self="isSupportModalOpen = false"
  >
    <div
      class="w-full max-w-md rounded-xl bg-slate-900 p-6 shadow-2xl border border-white/10"
    >
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold text-uva-orange">Support Info</h2>
        <button
          @click="isSupportModalOpen = false"
          class="text-slate-400 hover:text-white"
        >
          ✕
        </button>
      </div>
      <div class="space-y-3 text-sm text-slate-200">
        <p>
          If you need help, please contact the administrator at
          support@virginia.edu.
        </p>
        <p>Office hours and TA assignments are managed by your professors.</p>
      </div>
      <div class="mt-6 flex justify-end">
        <button
          @click="isSupportModalOpen = false"
          class="rounded border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-slate-100 transition hover:bg-white/20"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { useRoute } from "vue-router";
import { useAuthProfile } from "../composables/useAuthProfile";

const route = useRoute();
const { initials, authProfile, isProfessor } = useAuthProfile();

const isSupportModalOpen = ref(false);

const navItems = [
  { to: "/announcements", label: "Announcements" },
  { to: "/office-hours", label: "Office Hours" },
  { to: "/appointments", label: "Appointments" },
  { to: "/ta-bios", label: "TA Bios" },
  { to: "/instructor-dashboard", label: "Instructor Dashboard" },
  { to: "/account", label: "Account" },
];

const visibleNavItems = computed(() => {
  const isVerified = Boolean(authProfile.value?.verified);
  if (!isVerified) return [];
  return navItems.filter((item) => {
    if (item.to === "/account") return false;
    if (item.to === "/instructor-dashboard" && !isProfessor.value) return false;
    return true;
  });
});

const activeLinkClass =
  "rounded-lg border border-uva-orange/65 bg-uva-orange/15 px-3 py-1.5 text-uva-orange shadow-[0_0_18px_rgba(248,76,30,0.25)] transition-all duration-300 backdrop-blur-md";
const inactiveLinkClass =
  "rounded-lg border border-transparent px-3 py-1.5 text-white/90 transition-all duration-300 hover:border-uva-orange/45 hover:bg-uva-orange/15";
</script>
