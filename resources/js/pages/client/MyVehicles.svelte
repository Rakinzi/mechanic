<script lang="ts">
    import { Accordion } from '@skeletonlabs/skeleton-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ClientLayout from '@/layouts/ClientLayout.svelte';
    import type { BreadcrumbItem } from '@/types';

    let { vehicles }: { vehicles: any[] } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'My Vehicles', href: '/client/vehicles' }];
</script>

<AppHead title="My Vehicles" />

<ClientLayout {breadcrumbs}>
    <section class="space-y-3">
        <Accordion multiple>
            {#each vehicles as vehicle (vehicle.id)}
                <Accordion.Item value={String(vehicle.id)} class="rounded-lg border px-3 py-2">
                    <Accordion.ItemTrigger class="flex w-full items-center justify-between text-left">
                        <span class="font-medium">{vehicle.registration_number}</span>
                        <span class="text-sm text-muted-foreground">{vehicle.job_cards.length} jobs</span>
                    </Accordion.ItemTrigger>
                    <Accordion.ItemContent class="text-sm text-muted-foreground">
                        {vehicle.make} {vehicle.model}
                    </Accordion.ItemContent>
                </Accordion.Item>
            {/each}
        </Accordion>
    </section>
</ClientLayout>
