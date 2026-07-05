import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

export const buttonVariants = cva(
  "inline-flex cursor-pointer px-3 items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default: "text-custom-shadow hover:bg-custom-secondary/20",
        primary: "bg-custom-primary text-custom-bg-light hover:bg-custom-primary/90",
        destructive:
          "bg-custom-bg-light text-custom-shadow hover:text-white hover:bg-destructive/90",
        logout:
          "items-center justify-start text-custom-shadow hover:text-destructive-foreground cursor-pointer",
        outline:
          "border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50",
        secondary:
          "bg-secondary text-secondary-foreground hover:bg-secondary/80",
        ghost:
          "text-custom-shadow hover:bg-custom-secondary hover:text-custom-bg-light dark:hover:bg-accent/50",
        link: "text-primary underline-offset-4 hover:underline",
        float: "bg-custom-bg text-custom-shadow hover:shadow-md dark:shadow-none hover:dark:inset-shadow-sm hover:dark:inset-shadow-white/5 rounded-full hover:bg-custom-secondary/20 cursor-pointer dark:bg-custom-bg-light dark:hover:bg-custom-secondary/20 hover:-translate-y-0.5",
        "float-primary": "bg-custom-primary text-custom-bg-light hover:shadow-md hover:dark:text-custom-shadow hover:dark:shadow-none dark:inset-shadow-sm dark:inset-shadow-white/5 rounded-full hover:bg-custom-primary/60 cursor-pointer hover:-translate-y-0.5",
        dropdown: "p-2 w-full justify-start rounded-md text-custom-shadow hover:bg-custom-secondary/20 cursor-pointer",
        "ghost-outline": "border border-custom-bg-light rounded-full hover:bg-custom-secondary/20 cursor-pointer",
      },
      size: {
        "default": "h-9 px-4 py-2 has-[>svg]:px-3",
        "sm": "h-8 rounded-md gap-1.5 px-3 has-[>svg]:px-2.5",
        "lg": "h-10 rounded-md px-6 has-[>svg]:px-4",
        "icon": "size-9",
        "icon-sm": "size-8",
        "icon-lg": "size-10",
        "icon-text": "size-9 lg:h-9 lg:w-fit items-center flex flex-row",
        dropdown: "items-center",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)
export type ButtonVariants = VariantProps<typeof buttonVariants>
