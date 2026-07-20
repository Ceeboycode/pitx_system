import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-hidden",
  {
    variants: {
      variant: {
        // ── shadcn defaults ──────────────────────────────────────
        default:
          "border-transparent bg-primary text-primary-foreground [a&]:hover:bg-primary/90",
        secondary:
          "border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90",
        destructive:
          "border-transparent bg-destructive text-white [a&]:hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60",
        outline:
          "text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
        primary:
          "bg-custom-primary rounded-full px-2 text-custom-bg-light capitalize border-none",
        inactive:
          "bg-custom-bg rounded-full px-2 text-custom-shadow capitalize border-none",
        "accent-3":
          "bg-custom-accent-3 text-custom-shadow text-custom-bg-light dark:text-custom-bg-light",

        // ── Semantic status variants ─────────────────────────────

        // Green — verified / healthy / stable / tracked
        success:
          "border-emerald-200 bg-emerald-100 text-emerald-700 [a&]:hover:bg-emerald-100/80",

        // Blue — active / dispatched / departed / running / docs_completed
        blue:
          "border-blue-200 bg-blue-100 text-blue-700 [a&]:hover:bg-blue-100/80",

        // Amber — pending / arrived / needs attention / watchlist active
        warning:
          "border-amber-200 bg-amber-100 text-amber-700 [a&]:hover:bg-amber-100/80",

        // Orange — needs_revision
        orange:
          "border-orange-200 bg-orange-100 text-orange-700 [a&]:hover:bg-orange-100/80",

        // Slate — inactive / no activity / catch-all
        muted:
          "border-transparent bg-slate-100 text-slate-500 [a&]:hover:bg-slate-100/80",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
