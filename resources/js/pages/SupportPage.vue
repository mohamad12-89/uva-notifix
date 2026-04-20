<template>
  <section class="mx-auto w-full max-w-2xl space-y-6">
    <div
      class="card border border-uva-orange/25 bg-gradient-to-br from-uva-blue/45 via-slate-900/65 to-uva-orange/15 p-8 shadow-[0_12px_30px_rgba(7,12,24,0.35)]"
    >
      <h1 class="mb-6 text-center text-2xl font-bold text-uva-orange md:text-3xl">
        Support
      </h1>
      <SupportInformation />
    </div>
  </section>
</template>

<script setup>
import SupportInformation from "../components/SupportInformation.vue";
</script>
