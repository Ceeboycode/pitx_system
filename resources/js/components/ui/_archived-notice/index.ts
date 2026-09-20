export { default as ArchivedNotice } from "./ArchivedNotice.vue"

// ArchivedNotice
  // banner for the top of a detail page whose record is archived (the page gets `isArchived: true`).
  // Editing controls are hidden by lib/can.ts, which treats every write permission as not granted
  // while `isArchived` is true.
