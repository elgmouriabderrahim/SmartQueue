<template>
  <div class="auth-wrapper">
    <!-- Ambient Background -->
    <div class="ambient-bg">
      <div class="ambient-glow-1"></div>
      <div class="ambient-glow-2"></div>
      <div class="ambient-glow-3"></div>
    </div>

    <div class="auth-container">
      <!-- Left Panel - Brand Experience -->
      <div class="auth-brand">
        <div class="brand-content">
          <!-- Logo -->
          <router-link to="/" class="brand-logo">
            <div class="logo-icon">
              <img src="/images/logo.png" alt="SmartQueue" class="logo-img" />
            </div>
            <div class="logo-text">
              <span class="logo-title">SmartQueue</span>
              <span class="logo-tagline">Smart Appointment Platform</span>
            </div>
          </router-link>

          <!-- Hero Message -->
          <div class="brand-hero">
            <h1 class="brand-heading">
              Simplify the
              <span class="brand-highlight">waiting experience.</span>
            </h1>
            <p class="brand-description">
              Automate booking flows and manage operations with a single, intuitive interface.
            </p>
          </div>

          <!-- Features List -->
          <div class="brand-features">
            <div class="feature-item">
              <div class="feature-icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                  <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
              <span>Smart queue management</span>
            </div>
            <div class="feature-item">
              <div class="feature-icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                  <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
              <span>Real-time appointment tracking</span>
            </div>
            <div class="feature-item">
              <div class="feature-icon">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                  <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
              <span>Instant notifications</span>
            </div>
          </div>
        </div>

        <!-- Decorative Mockup -->
        <div class="brand-mockup">
          <div class="mockup-card">
            <img src="/images/auth.png" alt="Dashboard Preview" class="mockup-img" />
          </div>
        </div>
      </div>

      <!-- Right Panel - Auth Form -->
      <div class="auth-form-panel">
        <div class="form-container">
          <router-view />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-wrapper {
  @apply min-h-screen flex items-center justify-center p-4 lg:p-6;
  background: linear-gradient(135deg, #f5f0e8 0%, #e8e4dc 100%);
  position: relative;
  overflow: hidden;
}

/* Ambient Background */
.ambient-bg {
  @apply absolute inset-0 overflow-hidden pointer-events-none;
}

.ambient-glow-1 {
  @apply absolute rounded-full;
  width: 500px;
  height: 500px;
  top: -200px;
  left: -200px;
  background: radial-gradient(circle, rgba(44, 95, 110, 0.15) 0%, transparent 70%);
  animation: float 20s ease-in-out infinite;
}

.ambient-glow-2 {
  @apply absolute rounded-full;
  width: 600px;
  height: 600px;
  bottom: -250px;
  right: -200px;
  background: radial-gradient(circle, rgba(201, 103, 46, 0.12) 0%, transparent 70%);
  animation: float 25s ease-in-out infinite reverse;
}

.ambient-glow-3 {
  @apply absolute rounded-full;
  width: 400px;
  height: 400px;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: radial-gradient(circle, rgba(44, 95, 110, 0.08) 0%, transparent 70%);
  animation: pulse 15s ease-in-out infinite;
}

/* Main Container */
.auth-container {
  @apply relative w-full max-w-[1300px] grid lg:grid-cols-2 rounded-3xl overflow-hidden;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.6);
}

/* Left Panel - Brand */
.auth-brand {
  @apply relative flex flex-col justify-between p-8 lg:p-12 overflow-hidden;
  background: linear-gradient(135deg, rgba(44, 95, 110, 0.95) 0%, rgba(30, 69, 79, 0.95) 100%);
  backdrop-filter: blur(4px);
}

.brand-content {
  @apply relative z-10;
}

/* Logo */
.brand-logo {
  @apply inline-flex items-center gap-3 transition-all duration-300 hover:scale-105;
}

.logo-icon {
  @apply rounded-2xl overflow-hidden shadow-xl;
  background: rgba(255, 255, 255, 0.15);
  padding: 8px;
}

.logo-img {
  @apply w-12 h-12 object-cover;
}

.logo-text {
  @apply flex flex-col;
}

.logo-title {
  @apply text-lg font-bold tracking-tight text-white;
}

.logo-tagline {
  @apply text-[10px] font-semibold uppercase tracking-[0.2em] text-amber-300;
}

/* Hero Section */
.brand-hero {
  @apply mt-12;
}

.brand-heading {
  @apply text-4xl lg:text-5xl font-bold leading-tight text-white;
}

.brand-highlight {
  @apply block bg-gradient-to-r from-amber-300 to-orange-400 bg-clip-text text-transparent;
}

.brand-description {
  @apply mt-4 text-white/70 text-base leading-relaxed max-w-md;
}

/* Features */
.brand-features {
  @apply mt-8 space-y-3;
}

.feature-item {
  @apply flex items-center gap-3 text-white/80 text-sm;
}

.feature-icon {
  @apply w-6 h-6 rounded-full bg-amber-400/20 flex items-center justify-center text-amber-300;
}

/* Mockup */
.brand-mockup {
  @apply relative z-10 mt-12;
}

.mockup-card {
  @apply relative rounded-2xl overflow-hidden shadow-2xl;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 12px;
  transform: rotate(-3deg);
  transition: all 0.4s ease;
}

.mockup-card:hover {
  transform: rotate(0deg) scale(1.02);
}

.mockup-img {
  @apply w-full h-auto rounded-xl;
}

/* Right Panel - Auth Form */
.auth-form-panel {
  @apply flex items-center justify-center p-8 lg:p-12;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(4px);
}

.form-container {
  @apply w-full max-w-sm;
}

/* Animations */
@keyframes float {
  0%, 100% {
    transform: translate(0, 0) scale(1);
  }
  33% {
    transform: translate(30px, -30px) scale(1.05);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.98);
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 0.3;
    transform: translate(-50%, -50%) scale(1);
  }
  50% {
    opacity: 0.5;
    transform: translate(-50%, -50%) scale(1.2);
  }
}

/* Responsive */
@media (max-width: 1024px) {
  .auth-container {
    @apply max-w-lg;
  }
  
  .brand-mockup {
    @apply hidden;
  }
  
  .brand-features {
    @apply hidden;
  }
  
  .brand-hero {
    @apply mt-6;
  }
  
  .brand-heading {
    @apply text-3xl;
  }
}
</style>