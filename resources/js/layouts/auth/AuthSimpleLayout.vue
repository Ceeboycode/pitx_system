<script setup lang="ts">
import PitxLogo from '@/components/assets/PITX.png'
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
  <div class="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-[#f5f4f2] p-6 md:p-10 overflow-hidden">

    <svg
      class="pointer-events-none absolute inset-0 h-full w-full"
      viewBox="0 0 900 600"
      preserveAspectRatio="xMidYMid slice"
      xmlns="http://www.w3.org/2000/svg"
    >
      <line x1="100" y1="0" x2="100" y2="600" stroke="#e2dfd9" stroke-width="0.5"/>
      <line x1="800" y1="0" x2="800" y2="600" stroke="#e2dfd9" stroke-width="0.5"/>
      <line x1="0" y1="100" x2="900" y2="100" stroke="#e2dfd9" stroke-width="0.5"/>
      <line x1="0" y1="500" x2="900" y2="500" stroke="#e2dfd9" stroke-width="0.5"/>

      <line x1="100" y1="0" x2="100" y2="260" stroke="#c0281c" stroke-width="2"/>
      <line x1="100" y1="260" x2="60" y2="260" stroke="#c0281c" stroke-width="2"/>
      <line x1="60" y1="260" x2="60" y2="500" stroke="#c0281c" stroke-width="2"/>
      <line x1="60" y1="500" x2="100" y2="500" stroke="#c0281c" stroke-width="2"/>
      <line x1="100" y1="500" x2="100" y2="600" stroke="#c0281c" stroke-width="1.5" opacity="0.35"/>

      <circle cx="100" cy="100" r="4" fill="none" stroke="#c0281c" stroke-width="1.5"/>
      <circle cx="100" cy="100" r="1.5" fill="#c0281c"/>
      <circle cx="100" cy="260" r="4" fill="none" stroke="#c0281c" stroke-width="1.5"/>
      <circle cx="100" cy="260" r="1.5" fill="#c0281c"/>
      <circle cx="60"  cy="260" r="3" fill="#c0281c" opacity="0.5"/>
      <circle cx="60"  cy="500" r="3" fill="#c0281c" opacity="0.5"/>
      <circle cx="100" cy="500" r="4" fill="none" stroke="#c0281c" stroke-width="1.5"/>
      <circle cx="100" cy="500" r="1.5" fill="#c0281c"/>

      <line x1="100" y1="160" x2="115" y2="160" stroke="#c0281c" stroke-width="1" opacity="0.4"/>
      <line x1="100" y1="200" x2="115" y2="200" stroke="#c0281c" stroke-width="1" opacity="0.4"/>
      <line x1="100" y1="380" x2="115" y2="380" stroke="#c0281c" stroke-width="1" opacity="0.4"/>
      <line x1="100" y1="430" x2="115" y2="430" stroke="#c0281c" stroke-width="1" opacity="0.4"/>

      <line x1="100" y1="40" x2="140" y2="40" stroke="#c0281c" stroke-width="1" opacity="0.3"/>
      <line x1="140" y1="40" x2="140" y2="80" stroke="#c0281c" stroke-width="1" opacity="0.3"/>

      <text x="68" y="258" font-family="monospace" font-size="7" fill="#c0281c" opacity="0.4" font-weight="700">BAY A</text>
      <text x="68" y="498" font-family="monospace" font-size="7" fill="#c0281c" opacity="0.4" font-weight="700">BAY B</text>

      <line x1="800" y1="0" x2="800" y2="180" stroke="#1a4fa0" stroke-width="2"/>
      <line x1="800" y1="180" x2="840" y2="180" stroke="#1a4fa0" stroke-width="2"/>
      <line x1="840" y1="180" x2="840" y2="420" stroke="#1a4fa0" stroke-width="2"/>
      <line x1="840" y1="420" x2="800" y2="420" stroke="#1a4fa0" stroke-width="2"/>
      <line x1="800" y1="420" x2="800" y2="600" stroke="#1a4fa0" stroke-width="1.5" opacity="0.35"/>

      <circle cx="800" cy="100" r="4" fill="none" stroke="#1a4fa0" stroke-width="1.5"/>
      <circle cx="800" cy="100" r="1.5" fill="#1a4fa0"/>
      <circle cx="800" cy="180" r="4" fill="none" stroke="#1a4fa0" stroke-width="1.5"/>
      <circle cx="800" cy="180" r="1.5" fill="#1a4fa0"/>
      <circle cx="840" cy="180" r="3" fill="#1a4fa0" opacity="0.5"/>
      <circle cx="840" cy="420" r="3" fill="#1a4fa0" opacity="0.5"/>
      <circle cx="800" cy="420" r="4" fill="none" stroke="#1a4fa0" stroke-width="1.5"/>
      <circle cx="800" cy="420" r="1.5" fill="#1a4fa0"/>
      <circle cx="800" cy="500" r="4" fill="none" stroke="#1a4fa0" stroke-width="1.5"/>
      <circle cx="800" cy="500" r="1.5" fill="#1a4fa0"/>

      <line x1="800" y1="300" x2="785" y2="300" stroke="#1a4fa0" stroke-width="1" opacity="0.4"/>
      <line x1="800" y1="350" x2="785" y2="350" stroke="#1a4fa0" stroke-width="1" opacity="0.4"/>
      <line x1="800" y1="460" x2="785" y2="460" stroke="#1a4fa0" stroke-width="1" opacity="0.4"/>

      <line x1="800" y1="40" x2="760" y2="40" stroke="#1a4fa0" stroke-width="1" opacity="0.3"/>
      <line x1="760" y1="40" x2="760" y2="80" stroke="#1a4fa0" stroke-width="1" opacity="0.3"/>

      <text x="748" y="178" font-family="monospace" font-size="7" fill="#1a4fa0" opacity="0.4" font-weight="700">LINE 1</text>
      <text x="748" y="418" font-family="monospace" font-size="7" fill="#1a4fa0" opacity="0.4" font-weight="700">LINE 2</text>

      <circle cx="200" cy="150" r="1.5" fill="#c0281c" opacity="0.1"/>
      <circle cx="200" cy="180" r="1.5" fill="#c0281c" opacity="0.1"/>
      <circle cx="200" cy="210" r="1.5" fill="#c0281c" opacity="0.1"/>
      <circle cx="230" cy="150" r="1.5" fill="#c0281c" opacity="0.1"/>
      <circle cx="230" cy="180" r="1.5" fill="#c0281c" opacity="0.1"/>
      <circle cx="230" cy="210" r="1.5" fill="#c0281c" opacity="0.1"/>
      <circle cx="670" cy="390" r="1.5" fill="#1a4fa0" opacity="0.1"/>
      <circle cx="670" cy="420" r="1.5" fill="#1a4fa0" opacity="0.1"/>
      <circle cx="670" cy="450" r="1.5" fill="#1a4fa0" opacity="0.1"/>
      <circle cx="700" cy="390" r="1.5" fill="#1a4fa0" opacity="0.1"/>
      <circle cx="700" cy="420" r="1.5" fill="#1a4fa0" opacity="0.1"/>
      <circle cx="700" cy="450" r="1.5" fill="#1a4fa0" opacity="0.1"/>
    </svg>

    
    <div class="relative z-10 w-full max-w-sm">
      <div class="flex flex-col gap-8">
        <div class="flex flex-col items-center gap-4">
          <Link :href="home()" class="flex flex-col items-center gap-2 font-medium">
            <div class="mb-1 flex h-12 w-12 items-center justify-center rounded-md">
              <img :src="PitxLogo" alt="PITX Logo" class="max-h-12 max-w-xs object-contain"/>
            </div>
            <span class="sr-only">{{ title }}</span>
          </Link>
          <div class="space-y-2 text-center">
            <h1 class="text-xl font-medium">{{ title }}</h1>
            <p class="text-center text-sm text-muted-foreground">{{ description }}</p>
          </div>
        </div>
        <slot />
      </div>
    </div>
  </div>
</template>