import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as InputMessage } from "./InputMessage.vue"

export const inputMessageVariants = cva(
  "mt-2 flex w-full flex-row items-start gap-x-2 rounded-md px-3 py-2",
  {
    variants: {
      variant: {
        default: "text-center flex justify-center bg-custom-bg dark:bg-custom-bg-light text-custom-shadow",
        destructive: "border border-custom-bg-destructive bg-custom-bg-destructive/40 text-custom-shadow",
        warning: "border border-warning bg-warning/40 text-custom-shadow",
        success: "border border-success bg-success/40 text-custom-shadow",
        info: "border border-info bg-info/40 text-custom-shadow",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)

export type InputMessageVariants = VariantProps<typeof inputMessageVariants>

// InputMessage
  // the small coloured message under a form field or inside a form section: validation errors
  // (destructive), cautions (warning), confirmations (success) and hints (info).
  // replaces the old InputError and InputWarning components: `<InputMessage :message="form.errors.name" />`
  // is a destructive message, `variant="warning"` a warning. It hides itself when there is nothing
  // to show, so it can always be rendered.
  //
  // props   message  one line of text
  //         title    bold first line
  //         variant  destructive (default) | warning | success | info
  //         icon     another icon component (default: RiCheckboxCircleLine for success,
  //                  RiErrorWarningLine for the other variants)
  //         hideIcon no icon
  //         class    merged over the defaults (e.g. `mt-1`, `justify-center`, `text-center`)
  // slots   default  anything: extra text, lists, links (shown under the message)
  //         actions  buttons, shown in a row under the content
