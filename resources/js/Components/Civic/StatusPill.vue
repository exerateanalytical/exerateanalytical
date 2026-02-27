<script setup>
defineProps({ status: { type: String, required: true } });

const styles = {
    active:               'bg-green-100  text-green-800',
    closed:               'bg-gray-100   text-gray-600',
    under_review:         'bg-yellow-100 text-yellow-800',
    restricted:           'bg-red-100    text-red-700',
    milestone_reached:    'bg-blue-100   text-blue-800',
    submitted:            'bg-purple-100 text-purple-800',
    draft:                'bg-gray-100   text-gray-600',
    public_consultation:  'bg-blue-100   text-blue-800',
    revision:             'bg-yellow-100 text-yellow-700',
    finalized:            'bg-green-100  text-green-800',
    archived:             'bg-gray-100   text-gray-400',
};

const labels = {
    active:              'Active',
    closed:              'Closed',
    under_review:        'Under Review',
    restricted:          'Restricted',
    milestone_reached:   'Milestone Reached',
    submitted:           'Submitted',
    draft:               'Draft',
    public_consultation: 'Consultation',
    revision:            'Revision',
    finalized:           'Finalized',
    archived:            'Archived',
};
</script>

<template>
    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', styles[status] ?? 'bg-gray-100 text-gray-600']">
        {{ labels[status] ?? status }}
    </span>
</template>
