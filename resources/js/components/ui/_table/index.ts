export { default as Table } from "./Table.vue"
export { default as TableHeader } from "./TableHeader.vue"
export { default as TableColumn } from "./TableColumn.vue"
export { default as TableSortColumn } from "./TableSortColumn.vue"
export { default as TableContent } from "./TableContent.vue"
export { default as TableData } from "./TableData.vue"
export { default as TableMoreButton } from "./TableMoreButton.vue"

import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

// NOTE: hierarchy will look like this:
// TableCard
//   TableHeader
//     TableColumn
//     TableSortColumn
//   TableContent
//     TableRow
//       TableData
//       TableMoreButton

// FOR TABLE CARD ==================================================

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





// FOR TABLE ROW ==================================================

export { default as TableRow } from "./TableRow.vue"

export const tableRowVariants = cva(
  "cursor-pointer border-b border-custom-bg-dark transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light",
  {
    variants: {
      status: {
        default: "text-custom-shadow",
        inactive: "bg-custom-bg/50 text-custom-shadow/50 dark:bg-custom-bg-dark/50",
      },
    },
    defaultVariants: {
      status: "default",
    },
  },
)
export type TableRowVariants = VariantProps<typeof tableRowVariants>
