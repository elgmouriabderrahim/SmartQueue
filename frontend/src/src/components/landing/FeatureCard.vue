<script setup lang="ts">
defineProps<{
  title: string
  description: string
  accent?: 'slate' | 'orange' | 'blue' | 'emerald'
}>()
</script>

<template>
  <article
    class="group feature-card rounded-3xl p-6 transition-all duration-500"
    :class="{
      'glass-accent-slate': accent === 'slate' || !accent,
      'glass-accent-orange': accent === 'orange',
      'glass-accent-blue': accent === 'blue',
      'glass-accent-emerald': accent === 'emerald',
    }"
  >
    <div
      class="icon-wrapper inline-flex h-14 w-14 items-center justify-center rounded-2xl transition-all duration-300 group-hover:scale-110"
      :class="{
        'icon-slate': accent === 'slate' || !accent,
        'icon-orange': accent === 'orange',
        'icon-blue': accent === 'blue',
        'icon-emerald': accent === 'emerald',
      }"
    >
      <slot name="icon" />
    </div>

    <h3 class="mt-5 text-xl font-semibold tracking-tight text-stone-900">{{ title }}</h3>
    <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ description }}</p>

    <div v-if="$slots.default" class="mt-4 text-sm">
      <slot />
    </div>
  </article>
</template>

<style scoped>
/* Base glass styles */
.feature-card {
  background: rgba(255, 255, 255, 0.68);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.03);
}

.feature-card:hover {
  transform: translateY(-6px);
  background: rgba(255, 255, 255, 0.82);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
  border-color: rgba(255, 255, 255, 0.6);
}

/* Icon wrappers with glass + gradient */
.icon-wrapper {
  backdrop-filter: blur(8px);
  transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
}

/* Slate accent */
.glass-accent-slate {
  background: rgba(255, 255, 255, 0.68);
  border: 1px solid rgba(100, 116, 139, 0.15);
}

.icon-slate {
  background: linear-gradient(135deg, rgba(100, 116, 139, 0.15), rgba(100, 116, 139, 0.05));
  border: 1px solid rgba(100, 116, 139, 0.2);
  color: #475569;
}

.group:hover .icon-slate {
  background: linear-gradient(135deg, rgba(100, 116, 139, 0.25), rgba(100, 116, 139, 0.1));
  border-color: rgba(100, 116, 139, 0.35);
  color: #1e293b;
}

/* Orange accent */
.glass-accent-orange {
  background: rgba(255, 255, 255, 0.68);
  border: 1px solid rgba(249, 115, 22, 0.15);
}

.icon-orange {
  background: linear-gradient(135deg, rgba(249, 115, 22, 0.12), rgba(249, 115, 22, 0.04));
  border: 1px solid rgba(249, 115, 22, 0.2);
  color: #ea580c;
}

.group:hover .icon-orange {
  background: linear-gradient(135deg, rgba(249, 115, 22, 0.22), rgba(249, 115, 22, 0.08));
  border-color: rgba(249, 115, 22, 0.35);
  color: #c2410c;
}

/* Blue accent */
.glass-accent-blue {
  background: rgba(255, 255, 255, 0.68);
  border: 1px solid rgba(59, 130, 246, 0.15);
}

.icon-blue {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(59, 130, 246, 0.04));
  border: 1px solid rgba(59, 130, 246, 0.2);
  color: #2563eb;
}

.group:hover .icon-blue {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.22), rgba(59, 130, 246, 0.08));
  border-color: rgba(59, 130, 246, 0.35);
  color: #1d4ed8;
}

/* Emerald accent */
.glass-accent-emerald {
  background: rgba(255, 255, 255, 0.68);
  border: 1px solid rgba(16, 185, 129, 0.15);
}

.icon-emerald {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(16, 185, 129, 0.04));
  border: 1px solid rgba(16, 185, 129, 0.2);
  color: #059669;
}

.group:hover .icon-emerald {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.22), rgba(16, 185, 129, 0.08));
  border-color: rgba(16, 185, 129, 0.35);
  color: #047857;
}
</style>