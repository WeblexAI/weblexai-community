<script setup lang="ts">
import { computed, HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils'
import { type AlertVariants, alertVariants } from '.'
import { CheckCircle, Info, AlertTriangle } from 'lucide-vue-next'

const props = defineProps<{
  class?: HTMLAttributes['class']
  variant?: AlertVariants['variant']
}>()

const Icon = computed(() => {
    switch (props.variant) {
        case 'success':
            return CheckCircle
        case 'info':
            return Info
        case 'destructive':
            return AlertTriangle
        default:
            return null
    }
})

</script>

<template>
  <div
    data-slot="alert"
    :class="cn(alertVariants({ variant }), props.class)"
    role="alert"
  >
      <component v-if="Icon" :is="Icon"/>
    <slot />
  </div>
</template>
