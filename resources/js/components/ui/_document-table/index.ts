export { default as DocumentTable } from "./DocumentTable.vue"
export { default as DocumentTableContent } from "./DocumentTableContent.vue"
export { default as DocumentTableData } from "./DocumentTableData.vue"
export { default as DocumentTableMoreButton } from "./DocumentTableMoreButton.vue"

import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

// NOTE: hierarchy will look like this:
// DocumentTableCard
//   DocumentTableContent
//     DocumentTableRow
//       DocumentTableData
//       DocumentTableMoreButton

// FOR DOCUMENT TABLE CARD ==================================================

export { default as DocumentTableCard } from "./DocumentTableCard.vue"

export const documentTableVariants = cva(
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
export type DocumentTableVariants = VariantProps<typeof documentTableVariants>





// FOR DOCUMENT TABLE ROW ==================================================

export { default as DocumentTableRow } from "./DocumentTableRow.vue"

export const documentTableRowVariants = cva(
  "flex flex-row cursor-pointer items-center border-b border-custom-bg-dark transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light",
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
export type DocumentTableRowVariants = VariantProps<typeof documentTableVariants>
