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
      <div class="flex flex-wrap gap-2 text-sm">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          :class="route.path === item.to ? activeLinkClass : inactiveLinkClass"
        >
          {{ item.label }}
        </RouterLink>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { useRoute } from "vue-router";

const route = useRoute();

const navItems = [
  { to: "/announcements", label: "Announcements" },
  { to: "/office-hours", label: "Office Hours" },
  { to: "/appointments", label: "Appointments" },
  { to: "/ta-bios", label: "TA Bios" },
  { to: "/instructor-dashboard", label: "Instructor Dashboard" },
  { to: "/account", label: "Account" },
];

const activeLinkClass =
  "rounded-lg border border-uva-orange/65 bg-uva-orange/15 px-3 py-1.5 text-uva-orange shadow-[0_0_18px_rgba(248,76,30,0.25)] transition-all duration-300 backdrop-blur-md";
const inactiveLinkClass =
  "rounded-lg border border-transparent px-3 py-1.5 text-white/90 transition-all duration-300 hover:border-uva-orange/45 hover:bg-uva-orange/15";
</script>
