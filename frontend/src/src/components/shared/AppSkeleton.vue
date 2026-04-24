<template>
  <div class="skeleton-glass">
    <div class="skeleton-line skeleton-line-short" />
    <div class="skeleton-line skeleton-line-full" />
    <div class="skeleton-line skeleton-line-medium" />
  </div>
</template>

<style scoped>
.skeleton-glass {
  @apply space-y-3 rounded-2xl p-5;
  background: rgba(255, 255, 255, 0.48);
  backdrop-filter: blur(4px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
}

.skeleton-line {
  @apply rounded-xl;
  background: linear-gradient(
    90deg,
    rgba(203, 213, 225, 0.4) 0%,
    rgba(203, 213, 225, 0.6) 50%,
    rgba(203, 213, 225, 0.4) 100%
    );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}

.skeleton-line-short {
  @apply h-4;
  width: 33%;
}

.skeleton-line-full {
  @apply h-4;
  width: 100%;
}

.skeleton-line-medium {
  @apply h-4;
  width: 66%;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Optional: Pulsing variant for glass cards */
.skeleton-glass-pulse {
  animation: glassPulse 2s ease-in-out infinite;
}

@keyframes glassPulse {
  0%, 100% {
    background: rgba(255, 255, 255, 0.48);
    border-color: rgba(255, 255, 255, 0.3);
  }
  50% {
    background: rgba(255, 255, 255, 0.6);
    border-color: rgba(255, 255, 255, 0.5);
  }
}

/* For avatar/icon skeletons */
.skeleton-circle {
  @apply rounded-full;
  background: linear-gradient(
    90deg,
    rgba(203, 213, 225, 0.4) 0%,
    rgba(203, 213, 225, 0.6) 50%,
    rgba(203, 213, 225, 0.4) 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}

/* For card skeletons */
.skeleton-card {
  background: rgba(255, 255, 255, 0.48);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 28px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.02);
}
</style>