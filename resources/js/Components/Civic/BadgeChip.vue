<script setup>
defineProps({
    code:      { type: String, required: true },
    awardedAt: { type: String, default: null },
});

const meta = {
    FIRST_VOTE:      { label: 'First Vote',       icon: '🗳',  color: 'bg-blue-50   text-blue-700   border-blue-200'   },
    ACTIVE_CITIZEN:  { label: 'Active Citizen',   icon: '⭐',  color: 'bg-yellow-50 text-yellow-700 border-yellow-200' },
    PETITION_LEADER: { label: 'Petition Leader',  icon: '✊',  color: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    POLICY_AUTHOR:   { label: 'Policy Author',    icon: '📜',  color: 'bg-violet-50 text-violet-700 border-violet-200' },
    TRUSTED_MEMBER:  { label: 'Trusted Member',   icon: '🛡',  color: 'bg-green-50  text-green-700  border-green-200'  },
};
</script>

<template>
    <div
        :title="awardedAt ? `Awarded ${awardedAt}` : code"
        :class="['inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium', meta[code]?.color ?? 'bg-gray-50 text-gray-700 border-gray-200']"
    >
        <span class="text-base leading-none">{{ meta[code]?.icon ?? '🏅' }}</span>
        {{ meta[code]?.label ?? code }}
    </div>
</template>
