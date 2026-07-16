<script setup lang="ts">

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
  <div class="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-custom-bg dark:bg-custom-bg-dark p-6 md:p-10 overflow-hidden">

    <svg
      class="auth-route-map pointer-events-none absolute inset-0 h-full w-full"
      viewBox="0 0 900 600"
      preserveAspectRatio="xMidYMid slice"
      xmlns="http://www.w3.org/2000/svg"
    >
      <!-- LABEL: color: red-->
      <line x1="100" y1="0" x2="100" y2="600" stroke="var(--custom-shadow)" stroke-opacity="0.6" stroke-width="0.5"/>
      <line x1="800" y1="0" x2="800" y2="600" stroke="var(--custom-shadow)" stroke-opacity="0.6" stroke-width="0.5"/>
      <line x1="0" y1="100" x2="900" y2="100" stroke="var(--custom-shadow)" stroke-opacity="0.6" stroke-width="0.5"/>
      <line x1="0" y1="500" x2="900" y2="500" stroke="var(--custom-shadow)" stroke-opacity="0.6" stroke-width="0.5"/>

      <!-- LABEL: color: red-->
      <line x1="100" y1="0" x2="100" y2="260" stroke="var(--custom-accent-2)" stroke-width="1.5"/>
      <line x1="100" y1="260" x2="60" y2="260" stroke="var(--custom-accent-2)" stroke-width="1.5"/>
      <line x1="60" y1="260" x2="60" y2="500" stroke="var(--custom-accent-2)" stroke-width="1.5"/>
      <line x1="60" y1="500" x2="100" y2="500" stroke="var(--custom-accent-2)" stroke-width="1.5"/>
      <line x1="100" y1="500" x2="100" y2="600" stroke="var(--custom-accent-2)" stroke-width="2"/>

      <!-- LABEL: squares -->
      <rect x="96" y="96" width="8" height="8" fill="none" stroke="var(--custom-primary)" stroke-width="3"/>
      <!-- CODE: <rect x="96" y="256" width="8" height="8" fill="none" stroke="var(--custom-primary)" stroke-width="3"/> -->
      <rect x="56" y="256" width="8" height="8" fill="none" stroke="var(--custom-primary)" stroke-width="3"/>
      <!-- CODE: <rect x="57" y="497" width="6" height="6" fill="none" stroke="var(--custom-primary)" stroke-width="3" opacity="0.5"/> -->
      <rect x="96" y="496" width="8" height="8" fill="none" stroke="var(--custom-primary)" stroke-width="3"/>

      <line x1="100" y1="160" x2="115" y2="160" stroke="var(--custom-accent-2)" stroke-width="1" opacity="0.4"/>
      <line x1="100" y1="200" x2="115" y2="200" stroke="var(--custom-accent-2)" stroke-width="1" opacity="0.4"/>
      <line x1="100" y1="380" x2="115" y2="380" stroke="var(--custom-accent-2)" stroke-width="1" opacity="0.4"/>
      <line x1="100" y1="430" x2="115" y2="430" stroke="var(--custom-accent-2)" stroke-width="1" opacity="0.4"/>

      <line x1="100" y1="40" x2="140" y2="40" stroke="var(--custom-accent-1)" stroke-width="1" opacity="0.3"/>
      <line x1="140" y1="40" x2="140" y2="80" stroke="var(--custom-accent-1)" stroke-width="1" opacity="0.3"/>

      <text x="74" y="258" font-family="monospace" font-size="7" fill="var(--custom-shadow)" opacity="0.6" font-weight="700">IMUS</text>
      <text x="68" y="498" font-family="monospace" font-size="7" fill="var(--custom-shadow)" opacity="0.6" font-weight="700">DASMA</text>

      <!-- LABEL: red lines, blue square markers -->
      <line x1="800" y1="0" x2="800" y2="180" stroke="var(--custom-accent-2)" stroke-width="1.5"/>
      <line x1="800" y1="180" x2="840" y2="180" stroke="var(--custom-accent-2)" stroke-width="1.5"/>
      <line x1="840" y1="180" x2="840" y2="420" stroke="var(--custom-accent-2)" stroke-width="1.5"/>
      <line x1="840" y1="420" x2="800" y2="420" stroke="var(--custom-accent-2)" stroke-width="1.5"/>
      <line x1="800" y1="420" x2="800" y2="600" stroke="var(--custom-accent-2)" stroke-width="2"/>

      <!-- LABEL: squares -->
      <rect x="796" y="96" width="8" height="8" fill="none" stroke="var(--custom-primary)" stroke-width="3"/>
      <!-- CODE: <rect x="796" y="176" width="8" height="8" fill="none" stroke="var(--custom-primary)" stroke-width="3"/> -->
      <rect x="836" y="176" width="8" height="8" fill="none" stroke="var(--custom-primary)" stroke-width="3"/>
      <!-- CODE: <rect x="837" y="417" width="6" height="6" fill="none" stroke="var(--custom-primary)" stroke-width="3" opacity="0.5"/> -->
      <rect x="796" y="416" width="8" height="8" fill="none" stroke="var(--custom-primary)" stroke-width="3"/>
      <rect x="796" y="496" width="8" height="8" fill="none" stroke="var(--custom-primary)" stroke-width="3"/>

      <line x1="800" y1="300" x2="785" y2="300" stroke="var(--custom-accent-2)" stroke-width="1" opacity="0.4"/>
      <line x1="800" y1="350" x2="785" y2="350" stroke="var(--custom-accent-2)" stroke-width="1" opacity="0.4"/>
      <line x1="800" y1="460" x2="785" y2="460" stroke="var(--custom-accent-2)" stroke-width="1" opacity="0.4"/>

      <line x1="800" y1="40" x2="760" y2="40" stroke="var(--custom-accent-2)" stroke-width="1" opacity="0.3"/>
      <line x1="760" y1="40" x2="760" y2="80" stroke="var(--custom-accent-2)" stroke-width="1" opacity="0.3"/>

      <text x="810" y="178" font-family="monospace" font-size="7" fill="var(--custom-shadow)" opacity="0.6" font-weight="700">TRECE</text>
      <text x="806" y="418" font-family="monospace" font-size="7" fill="var(--custom-shadow)" opacity="0.6" font-weight="700">TAGAYTAY</text>

      <!-- CODE: <rect x="198" y="148" width="4" height="4" fill="none" stroke="var(--custom-primary)" stroke-width="1.5" opacity="0.4"/>
      <rect x="198" y="178" width="4" height="4" fill="none" stroke="var(--custom-primary)" stroke-width="1.5" opacity="0.4"/>
      <rect x="198" y="208" width="4" height="4" fill="none" stroke="var(--custom-primary)" stroke-width="1.5" opacity="0.4"/>
      <rect x="228" y="148" width="4" height="4" fill="none" stroke="var(--custom-primary)" stroke-width="1.5" opacity="0.4"/>
      <rect x="228" y="178" width="4" height="4" fill="none" stroke="var(--custom-primary)" stroke-width="1.5" opacity="0.4"/>
      <rect x="228" y="208" width="4" height="4" fill="none" stroke="var(--custom-primary)" stroke-width="1.5" opacity="0.4"/>
      <rect x="668" y="388" width="4" height="4" fill="none" stroke="var(--custom-primary)" stroke-width="1.5" opacity="0.4"/>
      <rect x="668" y="418" width="4" height="4" fill="none" stroke="var(--custom-primary)" stroke-width="1.5" opacity="0.4"/>
      <rect x="668" y="448" width="4" height="4" fill="none" stroke="var(--custom-primary)" stroke-width="1.5" opacity="0.4"/>
      <rect x="698" y="388" width="4" height="4" fill="none" stroke="var(--custom-primary)" stroke-width="1.5" opacity="0.4"/>
      <rect x="698" y="418" width="4" height="4" fill="none" stroke="var(--custom-primary)" stroke-width="1.5" opacity="0.4"/>
      <rect x="698" y="448" width="4" height="4" fill="none" stroke="var(--custom-primary)" stroke-width="1.5" opacity="0.4"/> -->
    </svg>

    
    <div class="relative z-10 w-full">
      <slot />
    </div>
  </div>
</template>
