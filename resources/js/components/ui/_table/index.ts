export { default as Table } from "./Table.vue"
// export { default as TableBody } from "./TableBody.vue"
// export { default as TableCaption } from "./TableCaption.vue"
// export { default as TableCell } from "./TableCell.vue"
// export { default as TableEmpty } from "./TableEmpty.vue"
// export { default as TableFooter } from "./TableFooter.vue"
// export { default as TableHead } from "./TableHead.vue"
export { default as TableHeader } from "./TableHeader.vue"
export { default as TableRow } from "./TableRow.vue"
export { default as TableColumn } from "./TableColumn.vue"
export { default as TableSortColumn } from "./TableSortColumn.vue"
export { default as TableContent } from "./TableContent.vue"
export { default as TableData } from "./TableData.vue"
export { default as TableMoreButton } from "./TableMoreButton.vue"

// FOR TABLE CARD

import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as TableCard } from "./TableCard.vue"

export const tableVariants = cva(
  "flex min-h-0 flex-1 max-h-fit flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none",
  {
    variants: {
      variant: {
        default: "border-solid",
        empty: "border-dashed",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)
export type TableVariants = VariantProps<typeof tableVariants>
