<script setup lang="ts">
defineProps<{ 
  headers: string[]
  hover?: boolean
  striped?: boolean
  compact?: boolean
}>()
</script>

<template>
  <div class="table-container">
    <table class="min-w-full text-left" :class="compact ? 'text-sm' : 'text-base'">
      <thead>
        <tr>
          <th 
            v-for="header in headers" 
            :key="header" 
            class="table-header"
            :class="compact ? 'px-4 py-3' : 'px-5 py-4'"
          >
            {{ header }}
          </th>
        </tr>
      </thead>
      <tbody>
        <slot />
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.table-container {
  @apply overflow-x-auto rounded-2xl;
  background: rgba(255, 255, 255, 0.68);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.03);
}

/* Table Header */
.table-header {
  @apply font-semibold tracking-tight;
  background: rgba(44, 95, 110, 0.08);
  backdrop-filter: blur(4px);
  color: #2c5f6e;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

/* Table Rows */
.table-container tbody tr {
  transition: all 0.2s ease;
  border-bottom: 1px solid rgba(0, 0, 0, 0.04);
}

.table-container tbody tr:last-child {
  border-bottom: none;
}

/* Hover effect */
.table-container tbody tr:hover {
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(4px);
}

/* Striped rows */
.table-container tbody tr:nth-child(even) {
  background: rgba(0, 0, 0, 0.01);
}

/* Table Cells */
.table-container td {
  @apply px-5 py-3.5;
  color: #4a4a4a;
}

/* Compact mode cells */
.table-container .text-sm td {
  @apply px-4 py-2.5;
}

/* Empty state styling */
.table-empty {
  @apply text-center py-8;
  color: #8a8a8a;
}

/* Scrollbar styling */
.table-container::-webkit-scrollbar {
  height: 6px;
}

.table-container::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 10px;
}

.table-container::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.15);
  border-radius: 10px;
}

.table-container::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.25);
}

/* Responsive */
@media (max-width: 640px) {
  .table-container td {
    @apply px-3 py-2.5;
    font-size: 0.875rem;
    line-height: 1.25rem;
  }
  
  .table-header {
    @apply px-3 py-2;
    font-size: 0.875rem;
    line-height: 1.25rem;
  }
}
</style>