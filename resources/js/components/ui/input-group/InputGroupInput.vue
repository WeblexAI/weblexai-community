<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { Input } from '@/components/ui/input'

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <Input
    data-slot="input-group-control"
    :class="cn(
      'flex-1 rounded-e-none me-2',
      props.class,
    )"
  />
</template>
