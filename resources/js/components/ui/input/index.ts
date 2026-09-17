import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Input } from "./Input.vue"

export const inputVariants = cva(
  "file:text-foreground placeholder:text-custom-shadow/50 text-custom-shadow min-w-0 text-sm transition-[color,background-color,border-color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-white focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default: "w-full h-9 border border-custom-bg-dark dark:border-none dark:border-custom-bg-light rounded-md bg-custom-bg p-3 text-sm dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5",
        // "active-inline-edit": "w-fit bg-pink-300 underline focus-visible:ring-0",
        "inline-edit": "w-full min-w-0 text-end p-0 group-hover:underline group-focus-within:underline focus-visible:ring-0",
      },
      size: {
        default: "",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)

export type InputVariants = VariantProps<typeof inputVariants>
