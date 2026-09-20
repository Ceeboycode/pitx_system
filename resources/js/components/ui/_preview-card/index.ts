export { default as PreviewCard } from "./PreviewCard.vue"
export { default as PreviewCardRow } from "./PreviewCardRow.vue"
export { default as ReviewCardRow } from "./ReviewCardRow.vue"

// PreviewCard
  // the card shown inside SidePanel for the row that was just clicked in a table.
  // Only render it (and its SidePanel) once a row has been clicked; it emits
  // `close` from the X in its top-right corner, like the Dialog close button.
  // Contains details only - no action buttons (those live in the row dropdown
  // and on the entity's detail page).
  //
  // On Create pages the same card is used as a "review" card that is always open:
  // pass :closable="false" to hide the X, and render it without a v-if.

// PreviewCardRow
  // one "Label ........ value" line inside a PreviewCard body

// ReviewCardRow
  // like PreviewCardRow but for review cards: an icon, the label, and a value that copies
  // itself to the clipboard when clicked. Put several inside a
  // <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">, under a CardSeparator.

// Entity-specific cards live in components/internal/preview-cards
