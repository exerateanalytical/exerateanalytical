<script setup>
defineProps({
    title:       { type: String, default: 'Nothing here yet' },
    description: { type: String, default: '' },
    actionLabel: { type: String, default: null },
    actionHref:  { type: String, default: null },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center py-20 text-center text-gray-500">
        <svg class="w-14 h-14 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="font-semibold text-gray-700 text-lg">{{ title }}</p>
        <p v-if="description" class="text-sm mt-1 max-w-xs">{{ description }}</p>
        <a
            v-if="actionLabel && actionHref"
            :href="actionHref"
            class="mt-5 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition"
        >
            {{ actionLabel }}
        </a>
    </div>
</template>
