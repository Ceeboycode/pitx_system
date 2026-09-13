import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Select } from "./Select.vue"
export { default as SelectContent } from "./SelectContent.vue"
export { default as SelectGroup } from "./SelectGroup.vue"
export { default as SelectItem } from "./SelectItem.vue"
export { default as SelectItemText } from "./SelectItemText.vue"
export { default as SelectLabel } from "./SelectLabel.vue"
export { default as SelectScrollDownButton } from "./SelectScrollDownButton.vue"
export { default as SelectScrollUpButton } from "./SelectScrollUpButton.vue"
export { default as SelectSeparator } from "./SelectSeparator.vue"
export { default as SelectTrigger } from "./SelectTrigger.vue"
export { default as SelectValue } from "./SelectValue.vue"

export const selectTriggerVariants = cva(
  "flex flex-row items-center justify-between text-custom-shadow cursor-pointer data-[placeholder]:text-custom-shadow [&_svg:not([class*='text-'])]:text-custom-shadow focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive text-sm whitespace-nowrap transition-[color,box-shadow] transition-all duration-200 outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 data-[size=default]:h-9 data-[size=sm]:h-8 *:data-[slot=select-value]:line-clamp-1 *:data-[slot=select-value]:flex *:data-[slot=select-value]:items-center *:data-[slot=select-value]:gap-2 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
  {
    variants: {
      variant: {
        default: "w-fit gap-2 rounded-md border px-3 py-2 border-custom-bg-dark dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5 bg-custom-bg",
        "inline-edit": "w-fit h-fit text-end p-0 group-hover:underline group-focus-within:underline focus-visible:ring-0",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)

export type SelectTriggerVariants = VariantProps<typeof selectTriggerVariants>
