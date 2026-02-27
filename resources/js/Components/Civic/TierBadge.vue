<script setup>
defineProps({ tier: { type: String, default: 'Citizen' } });

const styles = {
    Citizen:       'bg-gray-100   text-gray-700',
    Contributor:   'bg-blue-100   text-blue-700',
    Trusted:       'bg-green-100  text-green-700',
    Steward:       'bg-purple-100 text-purple-700',
    Institutional: 'bg-amber-100  text-amber-700',
};

const icons = {
    Citizen:       '●',
    Contributor:   '◆',
    Trusted:       '★',
    Steward:       '◉',
    Institutional: '⬡',
};
</script>

<template>
    <span :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold', styles[tier] ?? styles.Citizen]">
        <span class="text-[10px]">{{ icons[tier] ?? '●' }}</span>
        {{ tier }}
    </span>
</template>
