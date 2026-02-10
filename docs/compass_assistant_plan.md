# Implementation Plan: Compass & Facing Assistant

This plan outlines the development of a "Compass & Facing Assistant" to help clients determine the accurate North direction and property orientation using their smartphone compass and a floor plan.

## 1. Overview

**Goal:** Simplify the process of determining the correct North orientation and Facing/Sitting directions for a project by correlating a physical compass reading (pointing into the building) with a specific wall on the floor plan.

**User Workflow:**
1.  User clicks "Compass Assistant" in Project Settings (only available if floor plans exist).
2.  **Instruction:** Assistant explains how to hold the phone (top edge pointing INTO the building) at a main ventilation window/door.
3.  **Visual Input:** User selects the representative floor plan and draws an arrow/line on the specific window/wall pointing INTO the house (matching the phone's direction).
4.  **Data Input:** User enters the compass degree (0-360°) read from their phone.
5.  **Calculation:** System calculates:
    *   **North Deviation:** Rotation of the map so North is accurately oriented.
    *   **Sitting Direction:** The direction pointing into the house (Energy Entry).
    *   **Facing Direction:** The direction pointing out of the house (usually Sitting + 180°).
6.  **Save:** Updates the project settings.

## 2. Technical Components

### A. New Livewire Component: `CompassAssistant`
*(New Files: `app/Livewire/Project/CompassAssistant.php`, `resources/views/livewire/project/compass-assistant.blade.php`)*

This component will handle the multi-step modal wizard.

**State Variables:**
- `$step` (1: Intro, 2: Image/Input, 3: Preview)
- `$selectedFloorPlanId`
- `$imageArrowAngle` (Angle of the arrow drawn on image relative to Image Up/12 o'clock)
- `$compassReading` (User input degrees)
- `$calculatedNorthDeviation`
- `$calculatedSitting`
- `$calculatedFacing`

**Logic:**
1.  **Mount:** Load project and available floor plans.
2.  **Step 1 (Intro):** Static view with clear instructions and graphical aid (Icon showing phone perpendicular to wall pointing in).
3.  **Step 2 (Input):**
    - Display selected floor plan using an **AlpineJS** wrapper.
    - **Interaction:** User clicks & drags to draw an arrow on the plan.
    - AlpineJS calculates the angle of this arrow relative to the image's vertical axis (0° = Up, 90° = Right, etc.).
    - Input field for `Compass Reading`.
4.  **Step 3 (Preview):**
    - Calculate `NorthDeviation = (CompassReading - ImageArrowAngle) % 360`.
    - Calculate `Sitting = CompassReading`.
    - Calculate `Facing = (CompassReading + 180) % 360`.
    - Show the floor plan rotated or with a North Arrow superimposed to verify accuracy.
5.  **Save:** Update `Project` model keys: `compass_direction` (North Dev), `sitting_direction`, `ventilation_direction`, `facing_mountain` (auto-calc).

### B. Frontend Interaction (AlpineJS)
We will use a custom Alpine data object for the image interaction:
- `x-data="arrowDrawer()"`
- Events: `mousedown` (start point), `mousemove` (drag), `mouseup` (end point).
- Render an SVG overlay on top of the image to show the drawn arrow.
- detailed math to compute the angle `atan2(dy, dx)` shifted to match Compass coordinates (0 = Up, CW).

### C. Integration in `EditProject`
*(File: `resources/views/livewire/modules/bagua/editproject.blade.php`)*
- Add a "Compass Assistant" button next to the "North Deviation" input.
- Button is `disabled` or hidden if `$project->floorPlans->count() === 0`.
- Clicking opens the `CompassAssistant` modal (using `Flux::modal` or Livewire event).

## 3. Step-by-Step Implementation

### Step 1: Create the Component
Create the Livewire component and basic view structure with the 3 steps.

### Step 2: Implement AlpineJS Arrow Drawer
Build the interactive image container where users can draw an "Inward Arrow".
- **Challenge:** Image responsiveness.
- **Solution:** Use percentage-based coordinates or SVGs that scale with the image container.

### Step 3: Implement Calculation Logic
Implement the math to derive North Deviation and Facing parameters.
- **Formula:** `NorthDeviation = (UserRealDegrees - ImageArrowDegrees)`.
- *Example:* Wall is at Bottom (Arrow UP = 0°). User reads "North" (0°). Deviation = 0.
- *Example:* Wall is at Right (Arrow LEFT = 270°). User reads "East" (90°). Deviation = 90 - 270 = -180 = 180.

### Step 4: UI Refinement & Integration
- Add the trigger button in `editproject.blade.php`.
- Ensure the modal looks "Simple yet Precise" as requested.
- Add "Save" logic to update the parent `Project` model.

## 4. Database Updates
No schema changes required. We are updating existing fields in the `projects` table:
- `compass_direction` (mapped to North Deviation)
- `sitting_direction`
- `ventilation_direction` (mapped to Compass/Sitting reading)
- `facing_mountain` (Optional: auto-set based on calculated Facing)

## 5. Verification
- Test with 0°, 90°, 180°, 270° walls.
- Verify the "North Arrow" on the map points correctly after calculation.
