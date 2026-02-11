---
title: "Implementation Plan: Residents Compatibility Report"
description: "Implement a summary report of residents, their Gua attributes, and compatibility with the house facing direction, as per user requirement."
---

## 1. Overview
We will implement a new section (or partial) in the Bagua/Flying Stars report view that mirrors the user's provided PDF example. This report lists:
- House details (Year, Facing Trigram/Gua, Facing Element).
- Residents details (Name, Birth Year, Gua, Elements, Group, Relationship/Compatibility Code, Best Direction).

## 2. Backend Logic (MingGuaCalculator)
We need to extend `MingGuaCalculator` to support:
1.  **Year Element Calculation**: `getYearElement(int $year): string`.
    -   Based on the Heavenly Stem of the year (last digit).
    -   Metal (0,1), Water (2,3), Wood (4,5), Fire (6,7), Earth (8,9).
2.  **Compatibility Codes**: Ensure `analyzeCompatibility` returns the codes (A+, A1, D2, etc.) standardly.
    -   It currently does (lines 201+). `A+` is Sheng Qi. `A1` is Tian Yi. `D2` is Wu Gui.
    -   We simply need to call this method passing PersonGua and HouseFacingGua.

## 3. Data Gathering (View Layer / Helper)
Inside the Blade view (or a dedicated Service/Component if complex), we will:
1.  Determine **House Facing Gua**.
    -   We have `$facingDirection` (Degrees). Use `MingGuaCalculator::calculateMountain` or similar to determine the Trigram?
    -   Actually, we have `MingGuaCalculator::CLASSICAL_TRIGRAMS`.
    -   We need to map Degrees -> Trigram (1-9).
    -   Grid 1(N), 8(NE), 3(E), 4(SE), 9(S), 2(SW), 7(W), 6(NW).
    -   The Bagua Grid logic already does this mapping. We can reuse it or create a simple helper `degreesToTrigram(float $deg): int`.
2.  Iterate through all People (Owner + FamilyMembers).
    -   Calculate Gua, Elements, Group (East/West).
    -   Calculate Compatibility with House Facing Gua (`analyzeCompatibility(PersonGua, HouseFacingGua)`).
    -   Determine Best Direction (Sheng Qi).

## 4. Frontend (Blade)
Create `resources/views/livewire/modules/bagua/partials/residents-summary.blade.php`.
-   **Header**: House Summary.
-   **List**: Best Compass Direction for each person.
-   **Table**: Columns as requested.
    -   Person Name
    -   Birth Year
    -   Gua Number
    -   Trigram Name (from `CLASSICAL_TRIGRAMS`)
    -   Trigram Element
    -   Birth Element (Year Element)
    -   Group (OH/WH) - translate to Ost/West.
    -   Relationship (Code from `analyzeCompatibility`).
    -   Best Direction (Code from `getBestDirection`).

## 5. Integration
Include this partial in `flying-stars-report.blade.php` at the bottom or in a new tab.

## 6. Execution Steps
1.  Add `getYearElement` to `MingGuaCalculator`.
2.  Add `degreesToTrigram` helper to `MingGuaCalculator` (if not present).
3.  Create the Blade partial.
4.  Include it in the main report.
