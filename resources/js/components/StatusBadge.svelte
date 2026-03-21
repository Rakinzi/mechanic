<script lang="ts">
    let { status }: { status: string } = $props();

    const labels: Record<string, string> = {
        NOT_STARTED: 'Not Started',
        IN_PROGRESS: 'In Progress',
        OVERDUE: 'Overdue',
        BLOCKED: 'Blocked',
        COMPLETED: 'Completed',
        PENDING: 'Pending',
        APPROVED: 'Approved',
        REJECTED: 'Rejected',
        OPEN: 'Open',
        CLOSED: 'Closed',
    };

    const toneClass = $derived.by(() => {
        if (status === 'COMPLETED' || status === 'APPROVED') {
            return 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-300';
        }
        if (status === 'OVERDUE' || status === 'REJECTED') {
            return 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300';
        }
        if (status === 'IN_PROGRESS') {
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300';
        }
        if (status === 'BLOCKED') {
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300';
        }
        if (status === 'PENDING') {
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/40 dark:text-yellow-300';
        }
        return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
    });

    const label = $derived(labels[status] ?? status.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()));
</script>

<span class={`inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ${toneClass}`}>{label}</span>
