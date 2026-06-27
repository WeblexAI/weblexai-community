<script setup lang="ts">
import OverviewCollaboratorsSkeleton from '@/components/Skeletons/OverviewCollaboratorsSkeleton.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { overviewGetCollaborators } from '@/lib/api';
import type { CollaboratorWithPivotI } from '@/types';
import { Users } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const members = ref<CollaboratorWithPivotI[]>([]);
const loading = ref(true);

onMounted(async () => {
    const response = await overviewGetCollaborators();
    members.value = response?.members ?? [];
    loading.value = false;
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2 text-lg">
                <Users class="h-5 w-5 text-primary" />
                Project Members
            </CardTitle>
        </CardHeader>
        <CardContent>
            <OverviewCollaboratorsSkeleton v-if="loading" />
            <div v-else-if="members.length" class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="member in members" :key="member.id" class="flex items-center gap-3 rounded-lg border p-3">
                    <Avatar>
                        <AvatarFallback>{{ member.name.charAt(0).toUpperCase() }}</AvatarFallback>
                    </Avatar>
                    <div>
                        <div class="font-medium">{{ member.name }}</div>
                        <div class="text-xs text-muted-foreground capitalize">{{ member.pivot.role }}</div>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">No additional members are assigned. Administrators manage membership in the admin panel.</p>
        </CardContent>
    </Card>
</template>
