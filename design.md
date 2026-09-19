# UI Design Rules (preliminary)

Living document capturing UI consistency rules for this app. Rules marked
**[DECIDED]** are confirmed; rules marked **[PROPOSED]** are my default
suggestion (usually "whatever the majority of the app already does") and are
open for you to override before implementation.

---

## 1. Icons

**[DECIDED]** Icon library is `vue-remix-icons` only. `lucide-vue-next` is
being fully removed from the repo — including `resources/js/components/ui/*`
(vendored shadcn-vue primitives), not just app code. Risk accepted: if the
shadcn-vue CLI is re-run against one of those primitives later, it could
reintroduce a lucide import there and this rule will need re-applying to that
file.

**[DECIDED]** Every icon component usage must include `shrink-0` in its
`class` attribute, so icons never squash when their flex/grid container runs
out of space. Audit found 587 of 775 current icon usages (~76%, across 141
files) missing it — this is a systemic gap, not isolated mistakes.

**[DECIDED]** `resources/js/components/Icon.vue` (dynamic lucide icon-by-name
lookup) and the `LucideIcon` type import in `resources/js/types/index.d.ts`
are dead code — nothing renders `Icon.vue` or populates a `NavItem.icon`
field that would use it. Both are deleted as part of the lucide removal
rather than migrated.

### Confident lucide → remix replacements (existing app precedent)
Applied automatically since an identical substitution already exists
elsewhere in the app for the same concept:

| Lucide | Remix |
|---|---|
| RotateCcw | RiRestartLine |
| Save (generic) | RiSaveLine |
| Save (VehicleType edit, existing sibling precedent) | RiSaveLine |
| ArchiveX / Archive / FileArchive | RiArchive2Line |
| Power | RiShutDownLine |
| ArrowLeft | RiArrowLeftLine (RiArrowLeftSLine on `*/Trash.vue` pages, matching their existing convention) |
| Edit / Pencil | RiEditLine |
| X (close) / XIcon | RiCloseLine |
| Check | RiCheckLine |
| Sun | RiSunLine |
| Moon | RiMoonLine |
| Monitor | RiComputerLine |
| Search | RiSearchLine |
| Eye | RiEyeLine |
| EyeOff | RiEyeOffLine |
| RefreshCw | RiRefreshLine |
| KeyRound / LockKeyhole | RiKey2Line |
| MessageSquare / MessageSquareText | RiMessage2Line |
| Send | RiSendPlane2Line |
| Plus | RiAddLine |
| MapPin / MapPinned | RiMapPin2Line |
| Route (RouteIcon) | RiRouteLine |
| Building2 | RiBuilding2Line |
| Fingerprint | RiFingerprintLine |
| LogOut | RiLogoutBoxLine |
| Users | RiGroupLine |
| User / UserCircle2 / UserRound | RiUserLine |
| Shield | RiShieldLine |
| ShieldCheck | RiShieldCheckLine |
| Loader2 / Loader2Icon | RiLoader2Line |
| Download | RiDownloadLine |
| FileText | RiFileTextLine |
| FileCheck2 | RiFileCheckLine |
| FileWarning | RiFileWarningLine |
| CarFront / Bus / Truck | RiBusLine |
| Clock / Clock3 | RiTimeLine |
| Mail | RiMailLine |
| Phone | RiPhoneLine |
| ClipboardList | RiClipboardLine |
| AlertTriangle / TriangleAlert / TriangleAlertIcon | RiAlertLine |
| HelpCircle / CircleHelp | RiQuestionLine |
| BadgeCheck | RiVerifiedBadgeLine |
| Ellipsis / MoreHorizontal | RiMore2Line |
| ChevronDown/Right/Left/Up (Roles/Create.vue) | RiArrowDownSLine / RiArrowRightSLine / RiArrowLeftSLine / RiArrowUpSLine |

Starting from here, tell me where the lucide icons here are being used, and in what context.
| ChevronDownIcon/RightIcon/LeftIcon/UpIcon | RiArrowDownSLine / RiArrowRightSLine / RiArrowLeftSLine / RiArrowUpSLine |
| ChevronsUpDown / RiExpandUpDownLine |
| UserCheck | RiUserFollowLine |
| UserPlus | RiUserAddLine |
| UserCog | RiUserSettingsLine |
| Info / InfoIcon | RiInformationLine |
| Settings2 | RiSettings5Line |
| Bell | RiNotification3Line |
| ShieldBan | RiShieldLine |
| PanelLeftClose | RiSidebarFoldLine |
| PanelLeftOpen | RiSidebarUnfoldLine |
| Navigation | RiCompass3Line |
| Map | RiMapLine |
| Hash | RiHashtag |
| Palette | RiPaletteLine |
| Copy | RiFileCopyLine |
| ScanLine | RiQrScan2Line |
| LogIn | RiLoginBoxLine |
| Image / FileImage | RiImageLine |
| CalendarClock/CalendarDays | RiCalendarLine |
| Lock | RiLockLine |
| Database | RiDatabase2Line |
| ListOrdered / ListChecks | RiListUnordered |
| Radio | RiRfidLine |
| BarChart3 | RiBarChartBoxLine |

plus the vendored-primitive-only icons (
| Circle | RiCircleLine |
| MinusIcon | RiSubtractLine |
| OctagonXIcon | RiCloseCircleLine |
)

**[DECIDED]** `ChevronsUpDown` stays a distinct icon from `RiArrowDownSLine`
— they're not the same UI pattern. `RiArrowDownSLine` is the standard
`<Select>` component's trigger chevron (`ui/select/SelectTrigger.vue`, used
by every plain dropdown in the app — Gate/Status/Type pickers, etc.).
`ChevronsUpDown` (→ `RiExpandUpDownLine`) is only used by the custom
route-search combobox in `VehicleRouteAssignment.vue` /
`RouteSelectorWithPreview.vue`, which has a text input for searching/typing,
not just a static list — a meaningfully different interaction, so a
different icon correctly signals that distinction to users.

### Pages excluded from the icon migration entirely (strip, don't replace)
These pages predate the current UI redesign and aren't being touched yet —
remove their icons outright (neither lucide nor a remix replacement) rather
than migrating them:
- `pages/PrivacyPolicy.vue` (ArrowRight, ArrowLeft, Shield, Eye, Lock,
  Database, UserCheck, RefreshCw, Mail)
- `pages/External/Employee/Create.vue`
- `pages/External/Employee/Edit.vue`
- `pages/External/Employee/Show.vue`
- `pages/Welcome.vue`'s `Radio` and `BarChart3` specifically (the rest of
  Welcome.vue's icons are unaffected — see its own transition-rule exemption
  in Section 2)

| Gavel / ScrollText / Activity / CheckSquare | RiBubbleChartFill | this is the icon for now for these because i dont see me finding or using the remix icon equivalents soon. the instances of those lucide icons might have been used by earlier UI versions that ive yet to change myself.


## 2. Transitions

**[DECIDED]** Standard transition duration is `duration-200`. This is
already the overwhelming convention app-wide.

**[DECIDED]** `Welcome.vue` is exempt (has its own marketing-page transition
rules: `duration-300`, `duration-100`).

**[DECIDED]** Two other exceptions, kept as-is rather than forced to 200ms,
since they're not generic hover/state transitions:
- `components/ui/sheet/SheetContent.vue` — asymmetric `duration-500` (open) /
  `duration-200` (close) for the slide-out panel.
- `components/ui/input-otp/InputOTPSlot.vue` — `duration-1000`, a caret-blink
  animation.

---

## 3. CRUD wording ("Add" not "Create")

**[DECIDED]** Rule:
- Index page header button that navigates to/opens a create flow: **"Add
  [Entity]"**.
- Create.vue page title / create-dialog title: **"Add New [Entity]"**.
- Create-flow submit button: **"Add [Entity]"** (matches the header button's
  wording), with a `-ing` processing state (e.g. "Adding...").

### Already compliant (header button only)
Users, Gates, Route, Roles, VehicleType, External Dispatches, External
Employee.

### Needs updating
| File | Current | Change to |
|---|---|---|
| `Dispatches/Create.vue` | Title/Head "Create Dispatch" | "Add New Dispatch" |
| `Vehicles/Create.vue` | Title/Head/submit "Create Vehicle" / "Creating..." | "Add New Vehicle" (title), "Add Vehicle" / "Adding..." (submit) |
| `Roles/Create.vue` | Head/submit "Create Role" | "Add New Role" (title), "Add Role" (submit) |
| `Company/Create.vue` | Breadcrumb/Head "Create Company" | "Add New Company" |
| `vehicleType/CreateVehicleTypeDialog.vue` | Dialog title "Create Vehicle Type" | "Add New Vehicle Type" (button already correctly says "Add Vehicle Type") |
| `internal/company/CreateCompanyDialog.vue` | Dialog title "Create Company" | "Add New Company" — **note: this component is dead code, not rendered anywhere; low priority** |
| `External/Employee/Create.vue` | Mixes "Create"/"Creating..." with "Add Employee" internally | Standardize the whole file on "Add Employee" / "Adding..." |
| `Route/Create.vue` | Title already "Add Route" | Change to "Add New Route" (title only; it's a Create-page title, not an index button) |
| `components/internal/gate/CreateGateDialog.vue` | Submit button just says "Add" | "Add Gate" |

### Open question
`Vehicles/Index.vue`, `Company/Index.vue`, and `Dispatches/Index.vue` have no
top-level "Add" button at all — need to confirm whether that's intentional
(e.g. vehicles/dispatches created via a different flow) before assuming
they're missing one.

Yes, they do not need 'Add' buttons. 

---

## 4. Button label/icon standards (`variant="float-primary"` and related)

**[DECIDED]** Archive confirm buttons use `variant="destructive"` (not
`float-primary`) everywhere. `ArchiveRoleDialog.vue` is the one outlier to
fix (currently `float-primary`).

**[DECIDED]** Generic "proceed with this action" dialog buttons say
**"Confirm"** (not "Continue"). Files to update: both
`DocumentActionConfirmDialog.vue`s ("Continue" → "Confirm") and
`DownloadVerifiedDocumentsDialog.vue`s ("Continue" → "Confirm"). Already
correct: `vehicles/show/DocumentsTab.vue`, `External/Vehicles/Show.vue`,
`External/Employee/Show.vue`.

**[DECIDED]** Status-toggle dialogs say **"Activate"/"Inactivate"** (not "Set
Active"/"Set Inactive"). `External/Employee/Index.vue` is the one outlier to
fix.

**[DECIDED]** Status-toggle icon standardized on `RiShutDownLine` (matches
Gate/Route/User/VehicleType/External-Vehicle/Employee already).
`internal/vehicles/VehicleStatusDialog.vue` is the outlier (currently
`RiOctagonLine`/`RiSpam2Line`) to fix.

**[DECIDED]** "Save Changes" buttons carry no icon (matches
Gate/Route/User/Role) and always have a "Saving..." processing state.
`VehicleType/Edit.vue` is the outlier — drop its `RiSave3Line` icon and add
the missing processing-state text. When buttons are loading, use RiLoader2Line instead of RiSave3Line.

**[DECIDED]** Reset-password buttons standardize on the label **"Reset
Password"** with icon `RiShieldKeyholeLine`. `internal/users/ResetPasswordDialog.vue`
currently says "Send Temporary Password"; `External/Employee/Show.vue`'s "Reset Password" button is missing
the icon (add it).

**[DECIDED]** Row-level "go to detail page" links stay icon-only
(`RiExternalLinkLine`), matching Gate/User/Route/Dispatch — **except**
Company and Vehicle, where "Review" is a distinct, intentional verb (these
are moderation/approval workflows, not just viewing an existing record), so
their icon+"Review..." pattern is kept as-is. Standardize the review icon
itself on `RiFileCheckLine` (matches Company and External Vehicles); fix
`Vehicles/Index.vue`'s outlier `RiFileSearchLine`.
Do not use RiFileSearchLine, use RiFileCheckLine instead.

**[DECIDED]** "Register Vehicle" stays as-is (intentional operator-portal
wording, not an oversight). "Add Dispatch" becomes **"Dispatch Vehicle"**.
Location: `pages/External/Dispatches/Index.vue:798-805`, gated by
`v-if="canCreateDispatch"` → permission `external_dispatches.create`. Opens
`openCreateDialog` (a dialog, not a page navigation).

---

## 5. Resolved
- Confirmed: `UserCheck`, `Database`, `Radio`, `BarChart3`, `UserPlus`, and
  `UserCog` need no mapping — deleted along with their excluded pages
  instead of migrated.
- Plain `Shield` (not `ShieldCheck`/`ShieldBan`) → `RiShieldLine`. Used in
  `VehicleBasicInfoForm.vue` and `Roles/Create.vue`.
- `settings/TwoFactor.vue` — left as-is for now (unreachable via nav, but
  not being wired up or removed at this time).
