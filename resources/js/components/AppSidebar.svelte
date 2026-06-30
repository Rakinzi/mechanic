<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Car from 'lucide-svelte/icons/car';
    import ClipboardList from 'lucide-svelte/icons/clipboard-list';
    import Gauge from 'lucide-svelte/icons/gauge';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import Settings2 from 'lucide-svelte/icons/settings-2';
    import Users from 'lucide-svelte/icons/users';
    import Wrench from 'lucide-svelte/icons/wrench';
    import type { Snippet } from 'svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import NavFooter from '@/components/NavFooter.svelte';
    import NavMain from '@/components/NavMain.svelte';
    import NavUser from '@/components/NavUser.svelte';
    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import { dashboard as adminDashboard } from '@/routes/admin';
    import { index as adminJobCards } from '@/routes/admin/job-cards';
    import { index as adminReports } from '@/routes/admin/reports';
    import { index as adminStages } from '@/routes/admin/stages';
    import { index as adminUsers } from '@/routes/admin/users';
    import { index as clientRepairs } from '@/routes/client/repairs';
    import { index as clientVehicles } from '@/routes/client/vehicles';
    import { index as technicianAssignedStages } from '@/routes/technician/assigned-stages';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const auth = $derived($page.props.auth as { user?: { roles?: string[] } | null });
    const userRoles = $derived((auth?.user?.roles ?? []) as string[]);

    const mainNavItems = $derived(
        userRoles.includes('admin')
            ? [
                { title: 'Dashboard', href: adminDashboard(), icon: Gauge },
                { title: 'Job Cards', href: adminJobCards(), icon: ClipboardList },
                { title: 'Stages', href: adminStages(), icon: Settings2 },
                { title: 'Users', href: adminUsers(), icon: Users },
                { title: 'Reports', href: adminReports(), icon: LayoutGrid },
            ]
            : userRoles.includes('technician')
              ? [
                    { title: 'Dashboard', href: dashboard(), icon: Gauge },
                    { title: 'My Stages', href: technicianAssignedStages(), icon: Wrench },
                ]
              : [
                    { title: 'Dashboard', href: dashboard(), icon: Gauge },
                    { title: 'My Vehicles', href: clientVehicles(), icon: Car },
                    { title: 'My Repairs', href: clientRepairs(), icon: ClipboardList },
                ],
    );

    const footerNavItems: NavItem[] = [];
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" asChild>
                        {#snippet children(props)}
                            <Link {...props} href={toUrl(dashboard())} class={props.class}>
                                <AppLogo />
                            </Link>
                        {/snippet}
                    </SidebarMenuButton>
                </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <NavMain items={mainNavItems} />
    </SidebarContent>

    <SidebarFooter>
        <NavFooter items={footerNavItems} />
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}
