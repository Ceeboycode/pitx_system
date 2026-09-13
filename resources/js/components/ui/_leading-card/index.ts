export { default as LeadingCardDecoration } from "./LeadingCardDecoration.vue"

// FOR LEADING CARD

import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as LeadingCard } from "./LeadingCard.vue"

// DOESNT WORK!
export const leadingCardVariants = cva(
  "shrink-0 group",
  {
    variants: {
      variant: {
        default: "pb-0",
        dashboard: "pb-0",
        "entity-details": "rounded-b-none shadow-none",
        "entity-crud" : "pb-6",
      },
      color: {
        default: "text-custom-bg-light dark:text-custom-shadow bg-custom-primary dark:bg-custom-secondary",
        red: "text-custom-bg-light dark:text-custom-shadow bg-custom-accent-1 dark:bg-custom-accent-2",
        grey: "text-custom-shadow dark:text-custom-shadow bg-custom-bg-dark dark:bg-custom-bg-light",
        accent: "text-custom-bg-light dark:text-custom-shadow bg-custom-accent-3 dark:bg-custom-accent-3",
      }
    },
    defaultVariants: {
      variant: "default",
      color: "default"
    },
  },
)
export type LeadingCardVariants = VariantProps<typeof leadingCardVariants>
