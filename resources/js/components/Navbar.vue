<template>
    <nav class="sticky top-0 z-20 border-b border-white/20 bg-uva-blue/70 text-white shadow-2xl backdrop-blur-md">
        <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <RouterLink
                to="/"
                class="brand-link rounded-lg px-2 py-1 text-lg font-bold tracking-wide text-white transition-all duration-300 hover:text-uva-orange"
            >
                Notifix
            </RouterLink>
            <div class="flex flex-wrap gap-2 text-sm">
                <RouterLink
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    class="rounded-lg border border-transparent px-3 py-1.5 transition hover:border-uva-orange/50 hover:bg-uva-orange/20"
                >
                    {{ item.label }}
                </RouterLink>
            </div>
        </div>
    </nav>
</template>

<script setup>
const navItems = [
    { to: '/office-hours', label: 'Office Hours' },
    { to: '/appointments', label: 'Appointments' },
    { to: '/ta-bios', label: 'TA Bios' },
    { to: '/account', label: 'Account' },
];
</script>
