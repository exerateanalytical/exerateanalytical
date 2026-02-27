<script setup>
import { computed } from 'vue';

const props = defineProps({
    current: { type: Number, required: true },
    goal:    { type: Number, required: true },
    color:   { type: String, default: 'emerald' }, // emerald | blue | indigo
});

const pct = computed(() => Math.min(100, Math.round((props.current / props.goal) * 100)));

const trackColor = {
    emerald: 'bg-emerald-500',
    blue:    'bg-blue-500',
    indigo:  'bg-indigo-500',
};
</script>

<template>
    <div class="w-full">
        <div class="flex justify-between text-xs text-gray-500 mb-1">
            <span>{{ current.toLocaleString() }} signed</span>
            <span>{{ pct }}% of {{ goal.toLocaleString() }}</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
            <div
                :class="['h-2.5 rounded-full transition-all', trackColor[color] ?? trackColor.emerald]"
                :style="{ width: pct + '%' }"
            />
        </div>
    </div>
</template>
