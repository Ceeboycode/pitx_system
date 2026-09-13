export { default as MainPanel } from "./MainPanel.vue"
export { default as LeadPanel } from "./LeadPanel.vue"
export { default as SidePanel } from "./SidePanel.vue"
export { default as PanelLayout } from "./PanelLayout.vue"

// LeadPanel
  // for UI that has tabs, parent component of leading card and Tabs

// SidePanel
  // for UI of messages and notifications

// MainPanel
  // for any UI that does not have tabs or is not for messages and notifications

// PanelLayout
  // main parent of all panel components, like this:
  // PanelLayout
    // MainPanel
    // SidePanel
    
  // OR

  // PanelLayout
    // LeadPanel
    // SidePanel