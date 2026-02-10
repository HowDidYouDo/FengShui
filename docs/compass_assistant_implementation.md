# Compass Assistant Implementation Documentation

**Feature:** Compass & Facing Assistant
**Date:** 2026-02-10
**Status:** Implemented

## Overview

The Compass Assistant is a new tool in the Project Settings that helps users determine the accurate North orientation (North Deviation) and the House's Sitting/Facing directions using a smartphone compass reading and standard floor plan interaction.

## UI Components

### 1. Trigger
- **Location:** Project Settings Modal (`editproject.blade.php`)
- **Button:** "Compass Assistant" (visible only if floor plans are uploaded)
- **Position:** Next to the "North Deviation" input field.

### 2. Implementation (`CompassAssistant` Livewire Component)
- **Class:** `App\Livewire\Project\CompassAssistant`
- **View:** `resources/views/livewire/project/compass-assistant.blade.php`

## Logic & Math

### Step 1: User Input
1.  **Floor Plan Arrow:** User draws an arrow on the floor plan pointing **INTO** the house from the ventilation window.
    - This creates an `ImageArrowAngle` relative to the image's "Up" vector (0°).
2.  **Compass Reading:** User enters the compass degree (0-360°) from their phone, also pointing **INTO** the house.
    - `CompassReading` = Direction of Energy Entry (Ventilation / Sitting).

### Step 2: Calculation
The system calculates three key values:

1.  **North Deviation (Map Rotation):**
    - `Deviation = (CompassReading - ImageArrowAngle) % 360`
    - This value represents how much the "North" is rotated relative to the Image's "Up" orientation.

2.  **Sitting Direction:**
    - `Sitting = CompassReading`
    - Logic: The direction one looks when pointing INTO the house (towards the back).

3.  **Facing Direction:**
    - `Facing = (CompassReading + 180) % 360`
    - Logic: The direction the house faces (looking OUT).

### Step 3: Persistence
On "Apply Settings", the following fields on the `Project` model are updated:
- `compass_direction` (North Deviation)
- `sitting_direction`
- `ventilation_direction` (same as Sitting)

## Technical Details

- **AlpineJS Arrow Drawing:**
    - Custom Alpine component handles `mousedown`, `mousemove`, `mouseup` events.
    - SVG overlay renders the red arrow in real-time.
    - Angle calculation converts simple Euclidean trigonometry (`atan2`) into Compass coordinates (0° = Up, Clockwise).

- **Data Sync:**
    - The assistant emits a `project-updated` event upon saving.
    - `EditProject` component listens for this event and refreshes its local state to show the new values immediately.

## Translations
New strings have been added to:
- `lang/de.json`
- `lang/en.json`
- `lang/es.json`
- `lang/fr.json`

## Testing
To verify functionality:
1.  Open Project Settings for a project with a floor plan.
2.  Click "Kompass-Assistent".
3.  Draw an arrow pointing UP (0° image angle).
4.  Enter "0" (North) as compass reading.
5.  Result should be **Deviation: 0°**.
6.  Draw an arrow pointing LEFT (270° image angle).
7.  Enter "90" (East) as compass reading.
8.  Result should be **Deviation: 180°** (90 - 270 = -180 = 180).
