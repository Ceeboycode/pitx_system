import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Textarea } from "./Textarea.vue"

export const textareaVariants = cva(
  "placeholder:text-custom-shadow/50 text-custom-shadow field-sizing-content w-full text-sm transition-[color,background-color,border-color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-white aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default: "flex min-h-16 rounded-md bg-custom-bg border border-custom-bg-dark dark:border-none dark:border-custom-bg-light p-3 dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]",
        "inline-edit": "min-w-0 text-start p-0 bg-transparent border-none group-hover:underline group-focus-within:underline focus-visible:ring-0",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)

export type TextareaVariants = VariantProps<typeof textareaVariants>
