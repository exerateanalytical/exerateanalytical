<script setup>
defineProps({
    label:  { type: String, required: true },
    value:  { type: [String, Number], required: true },
    color:  { type: String, default: 'indigo' }, // indigo | blue | emerald | violet | amber
    href:   { type: String, default: null },
});

const colorMap = {
    indigo: 'bg-indigo-50 text-indigo-700 border-indigo-100',
    blue:   'bg-blue-50   text-blue-700   border-blue-100',
    emerald:'bg-emerald-50 text-emerald-700 border-emerald-100',
    violet: 'bg-violet-50  text-violet-700  border-violet-100',
    amber:  'bg-amber-50   text-amber-700   border-amber-100',
};
</script>

<template>
    <component
        :is="href ? 'a' : 'div'"
        :href="href"
        :class="[
            'rounded-xl border p-5 flex flex-col gap-1 transition',
            colorMap[color] ?? colorMap.indigo,
            href ? 'hover:shadow-md cursor-pointer' : '',
        ]"
    >
        <span class="text-sm font-medium opacity-70">{{ label }}</span>
        <span class="text-3xl font-bold">{{ value }}</span>
    </component>
</template>
