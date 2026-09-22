# Internal UI Design Standards

Source of truth for the look and behaviour of the **internal** (PITX staff/admin) side of
`pitx_system`. It documents the patterns already implemented in the codebase. It does not
introduce a new design system.

**Scope**

- In scope: every page rendered inside `AppLayout` (`resources/js/layouts/AppLayout.vue`),
  `resources/js/components/internal/**`, and the shared primitives in
  `resources/js/components/ui/**` those pages use.
- Out of scope: `pages/Dashboard.vue`, `pages/External/**` (operator portal, `ExternalLayout`),
  auth pages (`pages/auth/**`, `AuthLayout`), and public/marketing pages (`Welcome`, `FAQ`,
  `Terms*`, `Privacy*`, `Contact`, `CompanyRegistration`, `RegistrationStatus`).
- Related: `design.md` (repo root) is the decision log for icon, transition, wording and button
  label rules. Rules marked **[DECIDED]** there still apply and are summarised here.

**Reference implementations.** When unsure, copy these files:

| Page type | Reference |
|---|---|
| Index (list + filters + preview) | `pages/Gates/Index.vue` |
| Archive / trash list | `pages/Gates/Trash.vue` |
| Detail / edit (tabs) | `pages/Gates/Edit.vue` + `components/internal/gate/edit/DetailsTab.vue` |
| Create page | `pages/Users/Create.vue` |
| Create dialog | `components/internal/gate/CreateGateDialog.vue` |
| Confirm dialogs | `components/internal/gate/ArchiveGateDialog.vue`, `ToggleGateStatusDialog.vue` |
| Preview card | `components/internal/preview-cards/GatePreviewCard.vue` |

---

## 1. Golden rules

1. **Reuse before you write.** Use the custom primitives in `components/ui/_*` (underscore
   folders) before raw shadcn primitives, and raw shadcn primitives before hand-written markup.
2. **Use `custom-*` color tokens only.** Do not use raw Tailwind palette colors (`blue-500`,
   `gray-200`) or the shadcn tokens (`bg-background`, `text-muted-foreground`, `bg-primary`) in
   new internal UI. Status badges are the one exception (see §10).
3. **Every color must work in light and dark mode.** Pair each surface color with its `dark:`
   counterpart the same way the existing primitives do.
4. **Icons come from `vue-remix-icons` only**, and every icon carries `shrink-0`.
5. **Transitions use `duration-200`.**
6. **Rounded-full for actions, rounded-md for surfaces.** Buttons, search, pills and
   avatars are `rounded-full`; cards, inputs, dialogs, tables and dropdowns are `rounded-md`.
7. **No debug styling** (`bg-pink-300` and similar) in committed code.

---

## 2. Layout structure

### App shell

`AppLayout.vue` wraps `layouts/app/AppSidebarLayout.vue`:

```
SidebarProvider
├── AppSidebar                       (fixed left rail, see §12)
└── sidebar-inset  bg-custom-bg-light dark:bg-custom-bg, lg:pb-6 lg:pr-6
    ├── AppSidebarHeader             (sticky: SidebarTrigger + Breadcrumbs | Messaging + Notifications)
    └── <main>                       bg-custom-bg dark:bg-custom-bg-dark, lg:rounded-3xl, shadow-sm
        └── <slot />                 (the page)
```

`AppLayout` also mounts the `<Toaster position="bottom-right">` and turns `flash.success`,
`flash.error`, `flash.info` and `flash.warning` into toasts. Pages never mount their own Toaster.

Every internal page:

```vue
<template>
    <Head title="Gates" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>…</PanelLayout>
        <!-- page-level dialogs go here, after PanelLayout -->
    </AppLayout>
</template>
```

### Panels (`components/ui/_panels`)

| Component | Role | Key classes |
|---|---|---|
| `PanelLayout` | Row container for the page, adds top/bottom fade gradients | `flex-1 flex-row pr-3 overflow-y-auto no-scrollbar` |
| `MainPanel` | Main scrollable column (Index, Trash, Create pages) | `flex-1 flex-col gap-4 p-6 pr-3` |
| `LeadPanel` | Main column on detail pages (LeadingCard + Tabs) | `flex-col p-6 pr-3` |
| `SidePanel` | Right column, one third wide (preview card) | `min-w-1/3 max-w-1/3 p-6 px-3` |

Standard compositions:

- **Index / Trash:** `PanelLayout > MainPanel > Card` plus
  `SidePanel v-if="previewed" class="hidden lg:flex" > XPreviewCard`.
- **Detail / Edit:** `PanelLayout > LeadPanel > [ArchivedNotice] + LeadingCard + Tabs`, then `SidePanel`.
- **Create page:** `PanelLayout > MainPanel > LeadPanel(class="h-fit p-0") > LeadingCard variant="entity-crud"`,
  followed by the form `Card`s.

Scroll areas use `no-scrollbar`. Flex children that scroll need `min-h-0` (and `min-w-0` for
truncating text).

---

## 3. Typography

- Font: **Raleway** (`--font-sans` in `resources/css/app.css`). Do not set other font families.
  `font-mono` is used only for codes and IDs (for example the type-to-confirm text).
- Text colors: `text-custom-shadow` is the default for all UI text. Use `text-custom-body` and
  `text-custom-h1` only where they already appear (for example the sidebar user name).

| Use | Classes |
|---|---|
| Card title | `CardTitle` → `text-2xl font-semibold leading-none` |
| Card description / page subtitle | `CardDescription` (muted `text-sm`) |
| Section divider label | `CardSeparator` → `text-sm font-semibold uppercase text-custom-primary dark:text-custom-accent-3` |
| Table column header | `text-xs font-semibold uppercase tracking-widest` (built into `TableColumn`) |
| Body / table cells / inputs / labels | `text-sm` |
| Primary cell (record name) | `TableData class="font-semibold capitalize"` |
| Secondary / helper text | `text-sm text-custom-shadow/80` |
| Placeholder | `text-custom-shadow/50` (built into inputs) |
| Meta / timestamps / captions | `text-xs text-custom-shadow/70` |
| Empty-state title | `text-base font-semibold text-custom-shadow` |
| Highlighted entity name in dialog copy | `font-semibold text-custom-accent-3` |
| Numbers in tables | add `tabular-nums` |

Settings pages (`pages/settings/**`) use `Heading` / `HeadingSmall`. Other internal pages use
Card titles and do not use `Heading`.

---

## 4. Spacing

Use the Tailwind spacing scale. The recurring values:

| Context | Value |
|---|---|
| Panel padding | `p-6 pr-3` (built into the panels) |
| Gap between stacked cards in a panel | `gap-4` (MainPanel) / `space-y-4` |
| Card vertical padding | `py-6` (Card); horizontal `px-6` (CardHeader / CardContent) |
| Card header → content | `CardContent class="pt-2"` |
| Toolbar / button groups | `gap-2` |
| Label → input → message | `space-y-1` in dialogs, `space-y-2` on pages |
| Between form fields | `flex flex-col gap-y-2` (dialog), `gap-x-2` for side-by-side fields |
| Section blocks under a `CardSeparator` | `my-2 flex flex-col gap-0.5 text-sm text-custom-shadow` |
| Preview card rows | `space-y-2` |
| Dialog body | `py-4` (built into `AppDialog`) |
| Table cell | `py-1.5 pl-3` (built into `TableData`) |

---

## 5. Colors

All tokens are defined in `resources/css/app.css` with light and dark values and exposed as
Tailwind colors (`bg-custom-primary`, `text-custom-shadow/80`, and so on).

| Token | Role |
|---|---|
| `custom-bg-light` | Card, dialog and dropdown surface (light); app shell background |
| `custom-bg` | Inset surfaces: `<main>`, inputs, table header, float buttons, chips |
| `custom-bg-dark` | Borders and dividers (light mode); inputs and `<main>` (dark mode) |
| `custom-shadow` | Default text and icon color |
| `custom-body` / `custom-h1` | Stronger text, used sparingly |
| `custom-primary` | Brand blue: primary buttons, active sub-nav, LeadingCard, active tab underline |
| `custom-secondary` | Hover tints (`/10`, `/20`), LeadingCard and active tab in dark mode |
| `custom-accent-1` / `accent-2` | Red: `float-red` buttons, red LeadingCard, type-to-confirm text |
| `custom-accent-3` | Highlight: entity names in dialogs, CardSeparator (dark), checkbox checked |
| `custom-bg-destructive` | Error message and error toast background |
| `success` / `warning` / `info` / `destructive` | Semantic states (InputMessage, Alert, toasts, status dots) |

Standard pairings:

- Card surface: `bg-custom-bg-light dark:bg-custom-bg`
- Inset surface or chip: `bg-custom-bg dark:bg-custom-bg-dark` (or `dark:bg-custom-bg-light` inside a card)
- Border: `border-custom-bg-dark dark:border-custom-bg-light`
- Hover: `hover:bg-custom-secondary/10` for rows and nav, `hover:bg-custom-secondary/20` for buttons
- Selected row: `bg-custom-secondary/10`
- Dark-mode depth: `dark:inset-shadow-sm dark:inset-shadow-white/5` (cards, dialogs, dropdowns)

---

## 6. Buttons

Always use `Button` from `@/components/ui/button`. Variants are defined in `components/ui/button/index.ts`.

| Variant | Use |
|---|---|
| `float-primary` | Primary action: Add, Save Changes, Apply, Confirm, Activate/Restore |
| `float` | Secondary / Cancel in dialogs and form headers |
| `float-red` | Negative confirm (Archive, Delete, Reject, Inactivate). Normally rendered by `ConfirmDialog tone="negative"` |
| `float-secondary` | Alternate emphasis (rare) |
| `header-actions` | Icon buttons in card headers: back arrow, "more" (`RiMore2Line`), filter toggle |
| `ghost-outline` | Cancel inside popovers (filter panel) |
| `destructive` | Outlined red pill for "Clear" (filters) and inline destructive actions |
| `table-more` + size `icon-more` | Row "more" trigger (built into `TableMoreButton`) |
| `segmented` / `disabled` | Pagination (built into `InertiaPagination`) |
| `disabled` | Visual disabled state for Cancel/Save when the form is clean or processing |
| `default` / `ghost` | Plain text buttons (sidebar, settings nav) |

Sizes: `default` (h-9), `sm` (popover footers), `icon` (size-9, icon-only), `icon-text`
(icon only on mobile, icon plus label from `lg:`), `lg` (rare).

Rules:

- Icon plus label: `<RiAddLine class="h-4 w-4 shrink-0" /> <span>Add Gate</span>`.
- Icon-only buttons need `aria-label`.
- Order: Cancel on the left, primary on the right (`gap-2`, `justify-end`).
- Processing state: disable the button and swap the label for its `-ing` form: "Saving...",
  "Adding...", "Archiving...". If an icon is shown while loading, use `RiLoader2Line` with `animate-spin`.
- "Save Changes" buttons carry no icon.
- Edit forms: Cancel and Save Changes are `variant="disabled"` and `:disabled` while
  `!form.isDirty || form.processing`, then `float` / `float-primary` otherwise.
- Links styled as buttons: `<Button as-child variant="…"><Link :href="…">…</Link></Button>`.

Wording (from `design.md`):

- Index header button: **"Add [Entity]"**. Create page or dialog title: **"Add New [Entity]"**.
  Create submit: **"Add [Entity]"** / **"Adding..."**.
- Generic proceed button: **"Confirm"** (not "Continue").
- Status toggle: **"Activate" / "Inactivate"** with `RiShutDownLine`.
- Reset password: **"Reset Password"** with `RiShieldKeyholeLine`.
- Row "open detail" is icon-only `RiExternalLinkLine`. Company and Vehicle moderation uses
  **"Review"** with `RiFileCheckLine`. Never use `RiFileSearchLine`.
- Vehicles, Companies and Dispatches index pages have no "Add" button on purpose.

---

## 7. Forms and inputs

Primitives: `Input`, `Textarea`, `Select*` (reka-ui), `Label`, `InputMessage`, `CheckboxInput`
(`components/ui/_checkbox`), `EditableField` (`components/ui/_field`).

Field block:

```vue
<div class="space-y-2">
    <Label for="gate_name" class="flex items-center gap-1">
        Name <span class="text-destructive">*</span>
    </Label>
    <Input id="gate_name" v-model="form.gate_name" placeholder="e.g. Gate 1" />
    <InputMessage variant="destructive" :message="form.errors.gate_name" />
</div>
```

- Mark required fields with a red asterisk (`text-destructive`), and add
  "Fields with * are required." to the card description on create forms.
- Placeholders are examples (`e.g. Juan Dela Cruz`) or instructions (`Select a role`).
- Use `SelectTrigger class="w-full"` inside forms. Searchable selects put an `Input` in a `div class="p-2"`
  at the top of `SelectContent` (with `@keydown.stop`) and show `No roles found.` when nothing matches.
- Group long forms into sections with `CardSeparator title="…"`. Use `grid grid-cols-2 gap-x-2`
  for paired fields.
- Use `useForm` from Inertia. Submit with `@submit.prevent`, or with `AppDialog form @submit`.
- Wire routes with Wayfinder (`@/actions/...`, `@/routes/...`). Do not hardcode URLs.

### Inline editing (detail tabs)

Detail tabs show values read-only, and switch to inline inputs when the user can edit:

```vue
<div class="group flex flex-row items-center justify-between gap-2 overflow-hidden">
    <div class="inline-flex shrink-0 items-center gap-2">
        <RiBuildingLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
        <Label for="gate_name">Name</Label>
    </div>
    <EditableField :editable="canEdit">
        <template #edit>
            <span class="flex min-w-0 flex-1 flex-row items-center">
                <Input id="gate_name" v-model="form.gate_name" variant="inline-edit" />
                <RiEditLine class="h-4 w-0 shrink-0 opacity-0 … group-hover:w-4 group-hover:opacity-100" />
            </span>
        </template>
        <span class="min-w-0 flex-1 truncate text-right text-sm font-medium">{{ gate.gate_name }}</span>
    </EditableField>
</div>
```

`Input`, `Textarea` and `SelectTrigger` all support `variant="inline-edit"`.

### File / image upload

Use a dashed drop target (`border border-dashed border-custom-bg-dark`) with a hidden
`<input type="file" class="sr-only">`, an `RiImageAddLine` placeholder, a hover overlay, and a
round remove button in the top-right corner. List the accepted format, max size and recommendation
next to it. See `CreateGateDialog.vue`.

---

## 8. Tables

Use the `_table` primitives in this hierarchy:

```
TableCard :table-data-length
  Table v-if="rows.length"
    TableHeader            (adds the "Actions" column; pass hide-actions-column to omit)
      TableColumn
    TableContent
      TableRow :status="inactive|default"
        TableData
        TableMoreButton    (row dropdown)
  <empty state v-else>
InertiaPagination          (below TableCard, inside CardContent)
```

- Rows: single click opens the preview (`@click.left="openPreview(row)"`), double click opens
  the detail page (`@dblclick="router.visit(edit(row.id).url)"`).
- Selected row: `previewed?.id === row.id ? 'bg-custom-secondary/10' : ''`. Last row:
  `'rounded-b-md border-b-0'`.
- Inactive records: `TableRow status="inactive"` (dimmed).
- First column is the record name: `font-semibold capitalize`. Missing values render as `—`.
- Row dropdown (`TableMoreButton`): starts with `DropdownMenuLabel` (record name), then items in
  this order: View/Review → Edit → Activate/Inactivate → Archive. Trash pages show only Restore
  (and Force Delete where supported).
- Dropdown item markup: `<DropdownMenuItem class="group">` with the icon
  `class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow"`.
  Gate actions with `:disabled="!canX"` or `v-if` based on permissions.
- Document lists use `components/internal/documents/DocumentsTable.vue` (built on `_document-table`).

### Toolbar (search + filter)

Above the `TableCard`: `<div class="flex flex-row gap-2 lg:items-center lg:justify-between">`

- `SearchInput` (`components/SearchInput.vue`) fills the width. Pass `route`, `initial-value`,
  `placeholder="Search gates..."`, `only`, `:debounce="350"`, and `extra-params` so active
  filters are kept.
- Filter: `Popover` triggered by a `header-actions` / `icon-text` button with `RiFilter2Line`.
  The label reads "Filter" or "N filter(s) active", and the button gets `bg-custom-secondary/20`
  while filters are active. The panel is a `grid gap-y-2` of labelled fields
  (`text-sm text-custom-shadow/80`), a divider (`hr … bg-custom-bg-dark`), and a footer with
  `destructive sm` **Clear** (only if filters are active) on the left and `ghost-outline sm`
  **Cancel** plus `float-primary sm` **Apply** on the right.

---

## 9. Cards

| Component | Use |
|---|---|
| `Card` / `CardHeader` / `CardTitle` / `CardDescription` / `CardContent` | Every content block. Surface is `bg-custom-bg-light dark:bg-custom-bg rounded-md shadow-sm py-6` |
| `LeadingCard` (`_leading-card`) | Colored hero at the top of detail and create pages |
| `PreviewCard` + `PreviewCardRow` (`_preview-card`) | Side-panel record preview. Details only, no action buttons |
| `ReviewCardRow` | Icon + label + click-to-copy value rows on create "review" cards |
| `CardSeparator` (`_card-separator`) | Uppercase section label plus a divider line inside a card |
| `TableCard` | Bordered container for tables (see §8) |

`LeadingCard` props: `title`, `description`, `variant` (`entity-details` on detail pages,
`entity-crud` on create pages), `back` (index URL), `status` (`active` / `inactive` shows a status
pill), `more` (show the "more" dropdown; `false` on create pages), and `color` (`default`, `red`,
`grey`, `accent`). Dropdown actions go in its default slot. On mobile it adds a "Back" item
to the dropdown automatically.

Entity preview cards live in `components/internal/preview-cards/` and are exported from its
`index.ts`. Add new ones there, following `GatePreviewCard.vue`: they take the record plus
`archived?`, and emit `close`.

---

## 10. Badges and statuses

Use `Badge` from `@/components/ui/badge`. For record status, show a dot plus a label:

```vue
<Badge :class="['gap-1.5', statusClass(row.status)]">
    <span :class="['h-1.5 w-1.5 rounded-full', statusDot(row.status)]" />
    {{ row.status === 'active' ? 'Active' : 'Inactive' }}
</Badge>
```

Semantic status colors (Badge variants in `components/ui/badge/index.ts`, and the helpers in
`lib/vehicle-status.ts`):

| Meaning | Color | Badge variant |
|---|---|---|
| Active, verified, completed, healthy | emerald | `success` |
| Dispatched, in progress, docs completed | blue | `blue` |
| Pending, for verification, needs attention | amber | `warning` |
| Needs revision, suspended | orange | `orange` |
| Inactive, no activity, fallback | slate | `muted` |
| Rejected, invalid, expired | rose / destructive | `destructive` or rose classes |

- Vehicle and document statuses must use the helpers in `lib/vehicle-status.ts`
  (`operationalStatusClass/Dot/Label`, `verificationStatus*`, and so on). Company documents use
  `components/internal/company/show/documents/DocumentStatusBadge.vue`.
- Status labels are Title Case. Use `humanize()` for snake_case values.
- Other pills: `Badge variant="primary"` (brand pill) and `variant="inactive"` (neutral pill).
- An active status dot may use `animate-pulse`.

---

## 11. Dialogs and modals

Always build on `AppDialog` or `ConfirmDialog` from `@/components/ui/_app-dialog`. Do not use raw
`Dialog` / `DialogContent` for new internal dialogs.

**`AppDialog`**: forms and custom content.

- Props: `title`, `description`, `size` (`md` for confirmations and small forms, `lg` for reason
  forms and create dialogs, `xl` for larger forms, `viewer` for previews and maps), `closable`
  (off by default, so dialogs close through their Cancel button), `form` (wraps body and footer in
  a `<form>` and emits `submit`).
- Layout: header, separator, body (`py-4`), separator, footer (`justify-end gap-2`). Buttons inside
  are automatically `rounded-full`.
- Footer: `<Button variant="float" type="button">Cancel</Button>` then
  `<Button type="submit" variant="float-primary" :disabled="form.processing">`.

**`ConfirmDialog`**: yes/no confirmations.

- `tone="primary"` (restore, activate, verify) renders `float-primary`. `tone="negative"`
  (archive, delete, reject, inactivate) renders `float-red`.
- Pass `confirm-label`, `processing-label`, `:icon`, `:processing`, and handle `@confirm`.
- Irreversible actions (force delete) set `confirm-text` for type-to-confirm.
- Description copy: "Are you sure you want to archive
  `<span class="font-semibold text-custom-accent-3">{{ name }}</span>`? You can restore it later…"

Conventions:

- One dialog component per action, in `components/internal/<entity>/` (for example
  `ArchiveGateDialog.vue`, `RestoreGateDialog.vue`, `ToggleGateStatusDialog.vue`,
  `CreateGateDialog.vue`), exported from that folder's `index.ts`.
- Dialogs use `v-model:open` (`defineModel<boolean>('open')`) plus the record as a prop, and are
  rendered once at page level after `PanelLayout`.
- Titles: "Add New Gate", "Archive Gate", "Restore Gate", "Set Gate Status".
- Row and header actions open dialogs through `DropdownMenu` items.

---

## 12. Navigation and sidebar

- `components/AppSidebar.vue` is the internal sidebar. It is 288px wide, collapses to a 96px icon
  rail, and slides in on mobile over a `bg-black/45` overlay.
- Groups (`mainNavItems`): Home, Terminal Management, Terminal Operations, System Management,
  Platform Configurations. Each has a Remix icon and child items, and each child has a
  `permission` (for example `'gates.viewAny'`). Items and groups without visible children are
  hidden automatically through `can()`.
- To add a page, add a child item with a Wayfinder `href` and its `permission` to the right group.
  Do not add new top-level styling.
- Active states: parent `bg-custom-secondary/10`, active child `bg-custom-primary text-custom-bg-light`.
- Footer: Theme toggle, Activity Logs, FAQ, and the user dropdown (Settings, Log out).
- Header (`AppSidebarHeader`): `SidebarTrigger`, `Breadcrumbs`, `MessagingPanel`,
  `NotificationDropdown`.
- Breadcrumbs: every page passes `breadcrumbs: BreadcrumbItem[]` to `AppLayout`. Format is
  `[{ title: 'Gates', href: index().url }, { title: gate.gate_name, href: '#' }]`, and the last
  item is the current page.
- `<Head title>`: `"Gates"` (index), `"Archived Gates"` (trash), `"Add User"` (create),
  `` `Gate — ${gate.gate_name}` `` (detail).
- Detail pages use `Tabs` from `@/components/ui/_tabs` (underline style), driven by a `tabs` array
  of `{ value, label, icon, component }`. Standard order and icons:
  Overview (`RiDashboardHorizontalLine`), Details (`RiFileListLine`), related records (for example
  Routes `RiRouteLine`, Vehicles `RiBusLine`, Employees `RiGroupLine`), Dispatches
  (`RiRoadMapLine`), Incident Reports (`RiAlertLine`), History (`RiHistoryLine`). Tab components
  live in `components/internal/<entity>/edit/` (or `show/`). Tabs the role cannot access are left
  out of the array.
- Archives are reached from the Index header "more" dropdown ("Archives", `RiArchive2Line`). The
  Trash page header has a `header-actions` back button (`RiArrowLeftLine`).

---

## 13. Icons

- Library: `vue-remix-icons` only (`import { RiAddLine } from 'vue-remix-icons'`). No lucide.
- Every icon has `shrink-0`. Default size is `h-4 w-4` / `size-4`. Filter icon is `h-3.5 w-3.5`,
  status dots are `h-2 w-2`, and upload placeholders are `h-6 w-6`.
- Prefer the `Line` style. Use `Fill` only for state indicators (for example `RiCircleFill`).
- Muted icons next to labels use `text-custom-shadow/80`.

Standard mapping:

| Action / concept | Icon |
|---|---|
| Add | `RiAddLine` |
| Edit | `RiEditLine` |
| Archive / Archives | `RiArchive2Line` |
| Restore | `RiRestartLine` |
| Activate / Inactivate | `RiShutDownLine` |
| More actions | `RiMore2Line` |
| Back | `RiArrowLeftLine` |
| Open detail | `RiExternalLinkLine` |
| Review | `RiFileCheckLine` |
| Filter | `RiFilter2Line` |
| Search / clear | `RiSearchLine` / `RiCloseLine` |
| Loading | `RiLoader2Line animate-spin` (or `<Spinner />`) |
| Download | `RiDownloadLine` |
| Image upload | `RiImageAddLine` |
| Reset password | `RiShieldKeyholeLine` |
| Error / warning | `RiErrorWarningLine` / `RiAlertLine` |

The full lucide → remix table is in `design.md` §1.

---

## 14. Responsive behavior

Tailwind breakpoints, mobile first. `lg:` is the main desktop breakpoint.

- Below `lg`: the side panel and preview are hidden (`SidePanel class="hidden lg:flex"`), and the
  `<main>` card loses its rounded corners and outer padding.
- Header primary actions: the button shows only at `lg:` (`class="hidden lg:flex"`) and is
  repeated as a `DropdownMenuItem class="lg:hidden"` inside the "more" menu. Follow the same
  pattern for any new header action.
- `size="icon-text"` buttons hide their label below `lg:` (`<span class="hidden lg:flex">`).
- The LeadingCard back button is desktop-only and becomes a "Back" dropdown item on mobile.
- Pagination stacks (`flex-col`) and becomes a row from `lg:`.
- `TabsList` scrolls horizontally (`overflow-x-scroll no-scrollbar`).
- Tables scroll inside `Table` (`overflow-auto`). Do not wrap them in extra scroll containers.
- Sidebar: off-canvas on mobile, collapsible from `md:`.
- Use `truncate` together with `min-w-0` on flex children that hold names.

---

## 15. Reusable components (catalogue)

**Custom primitives** (`components/ui/_*`): `_app-dialog` (AppDialog, ConfirmDialog),
`_archived-notice`, `_badge`, `_card-separator`, `_checkbox` (CheckboxInput), `_document-table`,
`_field` (EditableField), `_input-message` (InputMessage), `_leading-card`, `_logo`
(Logo/LogoImage/LogoFallback for images with fallback), `_panels`, `_preview-card`, `_table`, `_tabs`.

**shadcn-vue primitives** (`components/ui/*`, themed with `custom-*` tokens): button, badge,
card, input, textarea, select, label, dropdown-menu, popover, tooltip, alert, separator,
skeleton, spinner, sonner, avatar, breadcrumb, sidebar, calendar, switch, and others. Add new ones
through the shadcn-vue CLI, then re-theme them to `custom-*` tokens and Remix icons.

**App components** (`components/*.vue`): `SearchInput`, `InertiaPagination`, `Breadcrumbs`,
`AppSidebar`, `AppSidebarHeader`, `NotificationDropdown`, `MessagingPanel`, `AlertError`,
`AddressSelectPH`, `PasswordRequirements`.

**Domain components** (`components/internal/<entity>/`): dialogs, `edit/` or `show/` tabs, and
per-entity `index.ts` barrels. Shared cross-entity pieces live in `internal/preview-cards/` and
`internal/documents/`.

**Helpers**: `lib/can.ts` (`can('gates.update')`), `lib/format.ts`, `lib/vehicle-status.ts`,
`lib/utils.ts` (`cn`, `toUrl`), `composables/useInitials.ts`, `composables/useDocumentSelection.ts`.

New shared components go in `components/ui/_<name>/` with an `index.ts` that exports the
component and a short usage comment (see `_input-message/index.ts` and `_preview-card/index.ts`).
Accept a `class` prop and merge it with `cn()`.

---

## 16. Loading states

- **Form submit:** disable the submit button and show the `-ing` label ("Saving...", "Adding...").
  Optionally show `RiLoader2Line class="h-4 w-4 shrink-0 animate-spin"` or `<Spinner />`.
- **Confirmations:** pass `:processing` and `processing-label` to `ConfirmDialog`. It disables
  both buttons.
- **Edit forms:** disable Cancel and Save while `form.processing`.
- **Search and filters:** rely on Inertia partial reloads (`only`, `preserveState`,
  `preserveScroll`). There are no page spinners.
- **Background async** (bulk downloads and similar): use `toast.loading(…)`, then update it to
  success or error.
- **Deferred props / lazy sections:** use `Skeleton` (`components/ui/skeleton`) blocks shaped like
  the final content (`animate-pulse`). No internal page uses this yet, so follow the Inertia v2
  deferred-props guidance when adding one.
- Inline "Loading…" text is `text-sm text-custom-shadow/80`.

---

## 17. Empty states

Tables and lists show an illustration, a title and a hint inside the `TableCard`:

```vue
<div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
    <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
        <img :src="emptyRafikiUrl" alt="" aria-hidden="true" class="w-1/3 object-contain opacity-90" />
        <div class="space-y-1">
            <p class="text-base font-semibold text-custom-shadow">No gates found</p>
            <p class="text-sm text-custom-shadow/80">
                {{ activeFilterCount > 0 ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search or add a new gate.' }}
            </p>
        </div>
    </div>
</div>
```

- Illustrations come from `components/assets/*-rafiki.svg`. Use `Empty-rafiki.svg` for generic
  lists and `Documents-rafiki.svg` for documents.
- Title: **"No [entities] found"**. The hint changes depending on whether filters are active.
- Small inline empties (inside preview cards, select lists): `<InputMessage message="No routes assigned." />`
  (default variant) or `<p class="px-2 py-1 text-sm text-custom-shadow/80">No roles found.</p>`.
- Missing single values render as `—`.

---

## 18. Error states

- **Validation errors:** `InputMessage variant="destructive"` under each field (§19).
- **Action result:** the backend flashes `success`, `error`, `info` or `warning`, and `AppLayout`
  shows it as a toast. Client-only failures call `toast.error('…')` from `vue-sonner`.
- **List of errors in a block:** `AlertError` (`components/AlertError.vue`, destructive `Alert`).
- **Archived / read-only records:** `ArchivedNotice entity="gate"` above the LeadingCard, and turn
  editing off.
- **Permission-restricted actions:** disable (`:disabled="!canX"`) or hide the control. Detail
  tabs switch copy to "View gate details." when the user cannot edit.
- **HTTP error pages:** `pages/Error.vue` (illustration, title, description, `float-primary`
  "Go back home"). Do not build page-level error screens elsewhere.
- **Warnings in context:** `Alert variant="warning"` or `InputMessage variant="warning"`.

---

## 19. Validation messages

- Component: `InputMessage` from `@/components/ui/_input-message`. `InputError` is removed, so do
  not reintroduce it.
- Always pass the variant explicitly for errors, because the component's default variant is the
  neutral `default` style: `<InputMessage variant="destructive" :message="form.errors.field" />`.
- Place it directly under the field, inside the field's `space-y-*` block. It hides itself when
  `message` is empty, so it can always be rendered.
- Variants: `destructive` (errors), `warning` (cautions), `success` (confirmations), `info`
  (hints), `default` (neutral note or small empty state). Use `title` for a bold first line and
  the `actions` slot for inline buttons.
- Message text comes from Laravel Form Requests. Do not duplicate validation copy on the client.
- In inline-edit rows, render it only when editable: `v-if="canEdit"`.

---

## 20. Page headers and action areas

**Index / Trash header** (inside the main `Card`):

```vue
<CardHeader class="flex flex-row gap-2">
    <div class="flex flex-col">
        <CardTitle class="flex items-center gap-2"><span class="font-semibold">Gates</span></CardTitle>
        <CardDescription>List of all gates in the system.</CardDescription>
    </div>
    <div class="flex flex-1 justify-end gap-2">
        <div class="items-center gap-2 sm:justify-end lg:flex">
            <Button variant="float-primary" class="hidden lg:flex" @click="createOpen = true">
                <RiAddLine class="h-4 w-4 shrink-0" /><span>Add Gate</span>
            </Button>
            <DropdownMenu>  <!-- header-actions icon button, RiMore2Line -->
                <!-- lg:hidden duplicate of "Add Gate", then "Archives" link -->
            </DropdownMenu>
        </div>
    </div>
</CardHeader>
```

- The Index main `Card` uses `class="min-h-0 min-w-0 flex-1 lg:h-full"`, and its `CardContent`
  uses `class="flex min-h-0 flex-1 flex-col space-y-4 pt-2"`.
- Trash headers put a `header-actions` back button before the title:
  "Archived Gates" / "Restore archived gates to the active gates list."
- Detail pages: `LeadingCard variant="entity-details"` holds the title, status pill, back button
  and the "more" dropdown with record actions (Activate/Inactivate, Archive, …).
- Section cards inside tabs: title and description on the left, actions on the right (Cancel,
  Save Changes, or a `header-actions` "more" dropdown).
- Descriptions are short sentences ending with a period: "Review and manage gate details."

---

## 21. Known inconsistencies (do not copy)

These existing patterns differ from the standard above. Leave them alone unless the task
touches that file, and follow the standard in new code.

- `layouts/settings/Layout.vue` uses `bg-pink-300` (debug) and shadcn tokens (`bg-blue-50`,
  `text-muted-foreground`).
- `_badge/ProgressiveBadge.vue` contains a card component rather than a badge, and is not exported.
- `CreateGateDialog.vue` uses `RiLoaderLine`. The standard is `RiLoader2Line`.
- Raw `Dialog` usage in `internal/company/vehicles/VehicleRouteAssignment.vue`. New dialogs use `AppDialog`.
- Status badge classes are redefined per page (`statusClass` / `statusDot` in Gates, Routes and
  preview cards). Prefer Badge variants or shared helpers when you add new statuses.
- The `_input-message/index.ts` comment says the default variant is `destructive`, but the code
  defaults to `default`. Always pass `variant` explicitly.
- `design.md` §4 says archive buttons use `variant="destructive"`. The shipped standard is
  `ConfirmDialog tone="negative"`, which renders `float-red`.
