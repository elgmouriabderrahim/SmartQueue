<template>
  <footer class="glass-footer mt-20">
    <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
      <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.5fr,1fr,1fr,1.2fr]">
        <!-- Brand Column -->
        <div class="space-y-4">
          <div class="flex items-center gap-3">
            <img 
              src="/images/logo-black.png" 
              alt="SmartQueue logo" 
              class="h-10 w-10 rounded-xl object-cover shadow-sm" 
              loading="lazy" 
            />
            <p class="text-lg font-semibold tracking-tight text-stone-800">SmartQueue</p>
          </div>
          <p class="max-w-md text-sm leading-relaxed text-stone-500">
            Smart appointment and queue management for faster public services and better citizen experience.
          </p>
          <div class="flex gap-3 pt-2">
            <a href="#" class="social-icon">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z" />
              </svg>
            </a>
            <a href="#" class="social-icon">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z" />
              </svg>
            </a>
            <a href="#" class="social-icon">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12z" />
              </svg>
            </a>
          </div>
        </div>

        <!-- Quick Links -->
        <div>
          <p class="text-sm font-semibold uppercase tracking-wider text-stone-600">Quick Links</p>
          <ul class="mt-4 space-y-2.5">
            <li><a href="mailto:contact@smartqueue.local" class="footer-link">Contact</a></li>
            <li><router-link to="/services" class="footer-link">Services</router-link></li>
            <li><router-link to="/about" class="footer-link">About Us</router-link></li>
          </ul>
        </div>

        <!-- Product -->
        <div>
          <p class="text-sm font-semibold uppercase tracking-wider text-stone-600">Product</p>
          <ul class="mt-4 space-y-2.5">
            <li><router-link to="/institutions" class="footer-link">Institutions</router-link></li>
            <li><router-link to="/auth/register" class="footer-link">Create Account</router-link></li>
          </ul>
        </div>

        <!-- Newsletter / Stay updated -->
        <div class="space-y-4">
          <p class="text-sm font-semibold uppercase tracking-wider text-stone-600">Stay Updated</p>
          <p class="text-sm text-stone-500">Get notified about new features and updates.</p>
          <div class="flex gap-2">
            <input 
              type="email" 
              placeholder="Your email" 
              class="newsletter-input"
            />
            <button class="newsletter-btn">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="border-t border-white/20 py-5 text-center">
      <p class="text-xs text-stone-400">
        Copyright {{ new Date().getFullYear() }} SmartQueue. All rights reserved.
      </p>
    </div>
  </footer>
</template>

<style scoped>
.glass-footer {
  background: rgba(255, 255, 255, 0.72);
  backdrop-filter: blur(16px);
  border-top: 1px solid rgba(255, 255, 255, 0.5);
  box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.02);
}

.footer-link {
  @apply text-sm text-stone-500 transition-all duration-200;
  position: relative;
}

.footer-link:hover {
  @apply text-stone-800;
  transform: translateX(4px);
}

.social-icon {
  @apply flex h-9 w-9 items-center justify-center rounded-full text-stone-500 transition-all duration-200;
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(4px);
  border: 1px solid rgba(255, 255, 255, 0.4);
}

.social-icon:hover {
  @apply text-stone-800;
  background: rgba(255, 255, 255, 0.8);
  transform: translateY(-2px);
  border-color: rgba(255, 255, 255, 0.8);
}

.newsletter-input {
  @apply flex-1 rounded-xl border px-3 py-2 text-sm outline-none transition-all duration-200;
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(4px);
  border-color: rgba(0, 0, 0, 0.08);
  color: #1a1a1a;
}

.newsletter-input:focus {
  border-color: var(--accent-500);
  background: rgba(255, 255, 255, 0.9);
  box-shadow: 0 0 0 3px rgba(44, 95, 110, 0.1);
}

.newsletter-input::placeholder {
  color: #9ca3af;
}

.newsletter-btn {
  @apply rounded-xl px-4 transition-all duration-200;
  background: rgba(44, 95, 110, 0.85);
  backdrop-filter: blur(4px);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.newsletter-btn:hover {
  background: rgba(44, 95, 110, 1);
  transform: translateX(2px);
}
</style>