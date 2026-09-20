export { default as AppDialog } from "./AppDialog.vue"
export { default as ConfirmDialog } from "./ConfirmDialog.vue"

// Every internal dialog is built from these two, so they all look the same:
// a rounded-md p-6 card, a header, separators between header / body / footer,
// rounded-full buttons, and the same widths.
//
// ConfirmDialog
  // confirmations (restore, activate, verify), negative actions (archive, inactivate, reject, delete)
  // and type-to-confirm (:confirm-text="'DELETE'"). Cancel is `float`; the main button is
  // `float-primary` (tone="primary") or `float-red` (tone="negative"). Put reasons or extra fields in
  // the default slot and the sentence in the #description slot.
//
// AppDialog
  // the bare chrome, for forms (float-primary submit) and viewers (all buttons `float`).
  //   size: md (default: confirmations, small forms) | lg (reason forms) | xl (larger forms) | viewer (previews, maps)
  //   form: wraps body + footer in a <form>, so a type="submit" button in #footer submits it
