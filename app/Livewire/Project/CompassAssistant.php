<?php

namespace App\Livewire\Project;

use App\Models\Project;
use Livewire\Component;
use Illuminate\Validation\ValidationException;

use Livewire\Attributes\On;

class CompassAssistant extends Component
{
    public Project $project;
    public bool $modalOpen = false;
    public int $step = 1;

    // Data for Step 2
    public $selectedFloorPlanId;
    public $compassReading; // Degrees (0-360)
    public $imageArrowAngle; // Degrees (0-360, 0=Project North/Up)
    public $imageArrowLength; // Length in pixels (for simple validation)

    // Calculated Results for Step 3
    public $calculatedNorthDeviation;
    public $calculatedSitting;
    public $calculatedFacing;

    #[On('openCompassAssistant')]
    public function openCompassAssistant()
    {
        $this->resetForm();
        
        // Select first floor plan by default if available
        if ($this->project->floorPlans->count() > 0) {
            $this->selectedFloorPlanId = $this->project->floorPlans->first()->id;
        }

        $this->modalOpen = true;
    }

    public function resetForm()
    {
        $this->step = 1;
        $this->selectedFloorPlanId = null;
        $this->compassReading = null;
        $this->imageArrowAngle = null;
        $this->imageArrowLength = 0;
        $this->calculatedNorthDeviation = null;
        $this->calculatedSitting = null;
        $this->calculatedFacing = null;
    }

    public function nextStep()
    {
        if ($this->step === 2) {
            $this->validate([
                'selectedFloorPlanId' => 'required',
                'compassReading' => 'required|numeric|min:0|max:360',
                'imageArrowAngle' => 'required|numeric',
                'imageArrowLength' => 'required|numeric|min:10', // Minimum length to avoid accidental clicks
            ], [
                'imageArrowLength.min' => __('Please draw a longer arrow on the floor plan to indicate the direction clearly.'),
                'imageArrowAngle.required' => __('Please draw an arrow on the floor plan pointing INTO the house.'),
            ]);

            $this->calculateValues();
        }

        $this->step++;
    }

    public function previousStep()
    {
        $this->step--;
    }

    public function calculateValues()
    {
        // Formula: North Deviation = (Compass Reading - Image Arrow Angle) % 360
        // Example: Wall at Bottom (Arrow UP = 0°). Reading North (0°). Dev = 0.
        // Example: Wall at Right (Arrow LEFT = 270°). Reading East (90°). Dev = 90 - 270 = -180 = 180.
        
        $deviation = ($this->compassReading + $this->imageArrowAngle);
        
        // Normalize to 0-360 positive
        while ($deviation < 0) $deviation += 360;
        while ($deviation >= 360) $deviation -= 360;

        $this->calculatedNorthDeviation = round($deviation, 2);

        // User correction: Viewing (Facing) and Sitting directions were swapped.
        // New Logic: 
        // Facing = Compass Reading.
        // Sitting = Compass Reading + 180.

        $this->calculatedSitting = round($this->compassReading, 2);
        
        $sitting = ($this->compassReading + 180) % 360;
        $this->calculatedFacing = round($sitting, 2);
    }

    public function save()
    {
        // Update Project with calculated values
        $this->project->update([
            'compass_direction' => $this->calculatedNorthDeviation,
            'sitting_direction' => $this->calculatedSitting,
            'ventilation_direction' => $this->calculatedFacing, // Facing = where you look out = ventilation direction
        ]);

        // Refresh the model so the parent gets fresh data
        $this->project->refresh();

        $this->modalOpen = false;

        // Dispatch event to refresh parent component
        $this->dispatch('project-updated');
    }

    public function render()
    {
        return view('livewire.project.compass-assistant', [
            'floorPlans' => $this->project->floorPlans,
        ]);
    }
}
