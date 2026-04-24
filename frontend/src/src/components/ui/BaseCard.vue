<script setup lang="ts">
withDefaults(
  defineProps<{
    title?: string
    subtitle?: string
    padded?: boolean
    variant?: 'glass' | 'glass-light' | 'solid'
    hover?: boolean
  }>(),
  {
    title: '',
    subtitle: '',
    padded: true,
    variant: 'glass',
    hover: false,
  },
)
</script>

<template>
  <section 
    class="card-base" 
    :class="[
      padded ? 'p-5 sm:p-6' : '',
      variant === 'glass' ? 'card-glass' : '',
      variant === 'glass-light' ? 'card-glass-light' : '',
      variant === 'solid' ? 'card-solid' : '',
      hover ? 'card-hover' : '',
    ]"
  >
    <header v-if="title || subtitle" class="card-header">
      <p v-if="subtitle" class="card-subtitle">{{ subtitle }}</p>
      <h3 v-if="title" class="card-title">{{ title }}</h3>
    </header>
    <div class="card-content">
      <slot />
    </div>
  </section>
</template>

<style scoped>
.card-base {
  @apply rounded-2xl transition-all duration-300;
}

/* Glass Card - Default */
.card-glass {
  background: rgba(255, 255, 255, 0.72);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.03);
}

/* Light Glass Card - More transparent */
.card-glass-light {
  background: rgba(255, 255, 255, 0.48);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
}

/* Solid Card - No glass (fallback) */
.card-solid {
  background: white;
  border: 1px solid #e8e8e8;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
}

/* Hover Effect */
.card-hover:hover {
  transform: translateY(-4px);
  background: rgba(255, 255, 255, 0.82);
  border-color: rgba(255, 255, 255, 0.7);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
}

/* Card Header */
.card-header {
  @apply mb-5;
}

.card-subtitle {
  @apply text-[11px] font-semibold uppercase tracking-[0.2em];
  color: #8a8a8a;
}

.card-title {
  @apply mt-1.5 text-lg font-semibold tracking-tight;
  color: #1a1a1a;
}

/* Card Content */
.card-content {
  @apply relative;
}

/* Responsive */
@media (max-width: 640px) {
  .card-base[class*="p-"] {
    @apply p-4;
  }
  
  .card-title {
    @apply text-base;
  }
}
</style>