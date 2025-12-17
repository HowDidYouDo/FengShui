<?php
// /resources/views/livewire/modules/bagua/managefloorplans.blade.php
use App\Jobs\AnalyzeFloorPlanJob;
use App\Models\Customer;
use App\Models\FloorPlan;
use App\Models\Project;
use App\Jobs\AnalyzeFloorPlan;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component {
    use WithFileUploads;
    use AuthorizesRequests;

    // Wichtig für $this->authorize()

    public Customer $customer;
    public Project $project;

    // Upload Input
    public $newPlanImage;
    public string $newPlanTitle = '';

    public function mount(Customer $customer, Project $project): void
    {
        // 1. Security: Darf ich Kunde & Projekt sehen?
        $this->authorize('view', $customer);
        $this->authorize('view', $project);

        // 2. Logic Integrity: Gehört dieses Projekt wirklich zu diesem Kunden?
        // projects table hat 'customer_id'.
        if ($project->customer_id !== $customer->id) {
            abort(404, 'Project does not belong to this customer context.');
        }

        $this->customer = $customer;
        $this->project = $project;
    }

    public function uploadPlan(): void
    {
        $this->validate([
            'newPlanImage' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240', // 10MB
            'newPlanTitle' => 'required|string|max:50',
        ]);

        // 1. Limit Check (Max 3 Pläne pro Projekt)
        if ($this->project->floorPlans()->count() >= 3) {
            $this->addError('newPlanImage', __('Maximum of 3 floor plans reached.'));
            return;
        }

        // 2. Create Plan Record
        $plan = $this->project->floorPlans()->create([
            'title' => $this->newPlanTitle,
            'sort_order' => $this->project->floorPlans()->count(),
        ]);

        // 3. Attach Media (Spatie)
        $media = $plan->addMedia($this->newPlanImage)->toMediaCollection('floor_plans');

        // 4. Async Analysis Job
        AnalyzeFloorPlanJob::dispatch($plan);

        // Reset & Notify
        $this->reset(['newPlanImage', 'newPlanTitle']);

        // Flux Toast (wenn installiert) oder Event dispatch
        // $this->dispatch('plan-uploaded');
    }

    public function deletePlan(int $planId)
    {
        // Security: Gehört der Plan zu meinem Projekt?
        $plan = $this->project->floorPlans()->find($planId);

        if ($plan) {
            $plan->delete(); // Spatie löscht Datei automatisch
        }
    }

    public function with()
    {
        return [
            'plans' => $this->project->floorPlans()->with('media')->orderBy('sort_order')->get(),
        ];
    }
};
?>

<div class="space-y-8" wire:poll.visible.2s>

    <!-- INFO HEADER -->
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Floor Plans') }}</h3>
            <p class="text-sm text-zinc-500">{{ __('Upload up to 3 floor plans for') }} <span
                    class="font-medium text-brand-blue">{{ $project->name }}</span>.</p>
        </div>
        <div class="text-xs text-zinc-400">
            {{ $plans->count() }} / 3 {{ __('Plans') }}
        </div>
    </div>

    <!-- UPLOAD FORM (Only show if limit not reached) -->
    @if($plans->count() < 3)
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800">
            <form wire:submit="uploadPlan" class="space-y-4">
                <flux:input wire:model="newPlanTitle" :label="__('Plan Name (e.g. Ground Floor)')"
                    :placeholder="__('e.g. Ground Floor')" required />

                <!-- Drag & Drop Area -->
                <div x-data="{ dragging: false }" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                    @drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                    class="border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
                    :class="dragging ? 'border-brand-blue bg-brand-blue/5' : 'border-zinc-300 dark:border-zinc-700 hover:border-brand-blue'"
                    @click="$refs.fileInput.click()">
                    <input x-ref="fileInput" type="file" wire:model="newPlanImage" class="hidden" accept="image/*">

                    <div class="flex flex-col items-center gap-2 text-zinc-500">
                        <flux:icon.cloud-arrow-up class="size-8 mb-2 text-zinc-400" />
                        @if($newPlanImage)
                            <span class="font-bold text-brand-blue">{{ $newPlanImage->getClientOriginalName() }}</span>
                        @else
                            <span>{{ __('Click or drag image here to upload') }}</span>
                            <span class="text-xs text-zinc-400">{{ __('JPG, PNG up to 10MB') }}</span>
                        @endif
                    </div>
                </div>
                @error('newPlanImage') <span class="text-red-500 text-sm block mt-1">{{ $message }}</span> @enderror

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" class="bg-brand-blue text-white w-full sm:w-auto"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ __('Upload & Analyze') }}</span>
                        <span wire:loading class="flex items-center gap-2">
                            <flux:icon.arrow-path class="animate-spin size-4" /> {{ __('Uploading...') }}
                        </span>
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    <!-- PLANS LIST -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($plans as $plan)
            <div
                class="group relative bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all">

                <!-- Image Preview -->
                <div
                    class="aspect-video bg-zinc-100 dark:bg-zinc-800 relative overflow-hidden border-b border-zinc-100 dark:border-zinc-800">
                    @if($plan->hasMedia('floor_plans'))
                        @php
                            $media = $plan->getFirstMedia('floor_plans');
                            // Secure URL generieren
                            $imageUrl = route('media.floor-plans', ['floorPlan' => $plan->id, 'media' => $media->id]);
                            $bounds = $plan->outer_bounds;
                        @endphp
                        <img src="{{ $imageUrl }}" class="w-full h-full object-contain" id="preview-img-{{ $plan->id }}">

                        {{-- Bagua Grid Overlay (nur wenn Bounds vorhanden) --}}
                        @if($bounds)
                            <svg class="absolute inset-0 w-full h-full pointer-events-none z-[5]"
                                viewBox="0 0 {{ $bounds['image_width'] }} {{ $bounds['image_height'] }}"
                                preserveAspectRatio="xMidYMid meet">
                                <g opacity="0.7">
                                    {{-- Outer Frame --}}
                                    <rect x="{{ $bounds['x1'] }}" y="{{ $bounds['y1'] }}"
                                        width="{{ $bounds['x2'] - $bounds['x1'] }}" height="{{ $bounds['y2'] - $bounds['y1'] }}"
                                        fill="none" stroke="#3b82f6" stroke-width="3" vector-effect="non-scaling-stroke" />

                                    {{-- Horizontal Grid Lines (3x3) --}}
                                    <line x1="{{ $bounds['x1'] }}" y1="{{ $bounds['y1'] + ($bounds['y2'] - $bounds['y1']) * 0.33 }}"
                                        x2="{{ $bounds['x2'] }}" y2="{{ $bounds['y1'] + ($bounds['y2'] - $bounds['y1']) * 0.33 }}"
                                        stroke="#ef4444" stroke-width="2" vector-effect="non-scaling-stroke" />
                                    <line x1="{{ $bounds['x1'] }}" y1="{{ $bounds['y1'] + ($bounds['y2'] - $bounds['y1']) * 0.66 }}"
                                        x2="{{ $bounds['x2'] }}" y2="{{ $bounds['y1'] + ($bounds['y2'] - $bounds['y1']) * 0.66 }}"
                                        stroke="#ef4444" stroke-width="2" vector-effect="non-scaling-stroke" />

                                    {{-- Vertical Grid Lines (3x3) --}}
                                    <line x1="{{ $bounds['x1'] + ($bounds['x2'] - $bounds['x1']) * 0.33 }}" y1="{{ $bounds['y1'] }}"
                                        x2="{{ $bounds['x1'] + ($bounds['x2'] - $bounds['x1']) * 0.33 }}" y2="{{ $bounds['y2'] }}"
                                        stroke="#ef4444" stroke-width="2" vector-effect="non-scaling-stroke" />
                                    <line x1="{{ $bounds['x1'] + ($bounds['x2'] - $bounds['x1']) * 0.66 }}" y1="{{ $bounds['y1'] }}"
                                        x2="{{ $bounds['x1'] + ($bounds['x2'] - $bounds['x1']) * 0.66 }}" y2="{{ $bounds['y2'] }}"
                                        stroke="#ef4444" stroke-width="2" vector-effect="non-scaling-stroke" />
                                </g>
                            </svg>
                        @endif
                    @else
                        <div class="flex items-center justify-center h-full text-zinc-400">{{ __('No Image') }}</div>
                    @endif

                    <!-- Status Badges -->
                    @if($plan->outer_bounds)
                        <div
                            class="absolute top-2 left-2 bg-green-500/90 text-white text-[10px] font-bold px-2 py-1 rounded backdrop-blur-sm shadow-sm flex items-center gap-1">
                            <flux:icon.check class="size-3" /> {{ __('AI READY') }}
                        </div>
                    @else
                        <div
                            class="absolute top-2 left-2 bg-amber-500/90 text-white text-[10px] font-bold px-2 py-1 rounded backdrop-blur-sm shadow-sm flex items-center gap-1 animate-pulse">
                            <flux:icon.arrow-path class="size-3 animate-spin" /> {{ __('ANALYZING...') }}
                        </div>
                    @endif

                    <!-- HOVER OVERLAY: EDIT BUTTON -->
                    <!-- Nur anzeigen, wenn Bild fertig analysiert ist (AI READY) -->
                    @if($plan->outer_bounds)
                        <a href="{{ route('modules.bagua.editor', $plan) }}"
                            class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-[2px] cursor-pointer z-10">
                            <div
                                class="bg-white text-zinc-900 px-4 py-2 rounded-full font-bold shadow-lg flex items-center gap-2 transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                <flux:icon.pencil-square class="size-4 text-brand-blue" />
                                {{ __('Open Editor') }}
                            </div>
                        </a>
                    @endif
                </div>

                <div class="p-4 flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-zinc-900 dark:text-white truncate max-w-[150px]">{{ $plan->title }}</h4>
                        <p class="text-xs text-zinc-500">{{ $plan->created_at->format('d.m.Y H:i') }}</p>
                    </div>

                    <flux:button icon="trash" size="sm" variant="danger" wire:click="deletePlan({{ $plan->id }})"
                        wire:confirm="{{ __('Delete this floor plan permanently?') }}">
                    </flux:button>
                </div>
            </div>
        @endforeach
    </div>

    @if($plans->isEmpty())
        <div class="text-center py-12 text-zinc-400 border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl">
            <p>{{ __('No floor plans uploaded yet.') }}</p>
        </div>
    @endif

</div>
