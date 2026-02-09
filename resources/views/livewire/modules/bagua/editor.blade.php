<?php
// /resources/views/livewire/modules/bagua/editor.blade.php
use App\Models\FloorPlan;
use App\Services\Metaphysics\MingGuaCalculator;
use App\Models\BaguaNote;
use App\Models\RoomAssignment;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component {
    use AuthorizesRequests;

    public FloorPlan $floorPlan;
    public ?string $imageUrl = null;
    public int $baguaNotesCount = 0;
    public array $familyMembers = [];

    #[Validate('nullable|numeric|min:0|max:360')]
    public $ventilationDirection;

    public function mount(FloorPlan $floorPlan): void
    {
        $floorPlan->load(['project.customer', 'baguaNotes.roomAssignments.familyMember', 'baguaNotes.roomAssignments.customer']);
        $this->authorize('view', $floorPlan->project);
        $this->floorPlan = $floorPlan;
        $this->ventilationDirection = $floorPlan->project->ventilation_direction ?? 0;

        if ($floorPlan->hasMedia('floor_plans')) {
            $media = $floorPlan->getFirstMedia('floor_plans');
            $this->imageUrl = route('media.floor-plans', ['floorPlan' => $floorPlan->id, 'media' => $media->id]);
        }

        $this->baguaNotesCount = $floorPlan->baguaNotes()->count();

        // Load family members + Owner
        $customer = $floorPlan->project->customer;
        $this->familyMembers = [];

        if ($customer) {
            // Add Main Customer
            $guaAttr = $customer->life_gua ? app(MingGuaCalculator::class)->getAttributes($customer->life_gua) : null;
            $elementColors = $guaAttr ? app(MingGuaCalculator::class)->getElementColors($guaAttr['element']) : null;

            $this->familyMembers[] = [
                'id' => $customer->id,
                'name' => $customer->name . ' (' . __('Owner') . ')',
                'type' => 'customer',
                'ming_gua' => $customer->life_gua,
                'element' => $guaAttr['element'] ?? null,
                'colors' => $elementColors,
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($customer->name) . '&background=random'
            ];

            // Add Family Members
            foreach ($customer->familyMembers as $member) {
                $mGuaAttr = $member->life_gua ? app(MingGuaCalculator::class)->getAttributes($member->life_gua) : null;
                $mElementColors = $mGuaAttr ? app(MingGuaCalculator::class)->getElementColors($mGuaAttr['element']) : null;

                $this->familyMembers[] = [
                    'id' => $member->id,
                    'name' => $member->name,
                    'type' => 'family_member',
                    'ming_gua' => $member->life_gua,
                    'element' => $mGuaAttr['element'] ?? null,
                    'colors' => $mElementColors,
                    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($member->name) . '&background=random'
                ];
            }
        }
    }

    private function calculateMingGua($person): ?int
    {
        return $person->life_gua;
    }

    public function assignPerson($type, $id, $guaNumber): void
    {
        // 1. Validate inputs
        if (!in_array($type, ['customer', 'family_member'])) {
            $this->dispatch('notify', message: __('Invalid person type'), type: 'error');
            return;
        }

        // Validate ownership
        if ($type === 'customer') {
             if ($id != $this->floorPlan->project->customer_id) {
                 $this->dispatch('notify', message: __('Invalid customer'), type: 'error');
                 return;
             }
        } else {
             $isFamily = $this->floorPlan->project->customer->familyMembers()->where('id', $id)->exists();
             if (!$isFamily) {
                 $this->dispatch('notify', message: __('Invalid family member'), type: 'error');
                 return;
             }
        }

        // 2. Find BaguaNote for this sector
        $baguaNote = $this->floorPlan->baguaNotes()->where('gua_number', $guaNumber)->first();

        if (!$baguaNote) {
            $this->dispatch('notify', message: __('Please calculate Bagua grid first!'), type: 'error');
            return;
        }

        // 3. Check for duplicates across the entire floor plan
        $allBaguaNoteIds = $this->floorPlan->baguaNotes()->pluck('id');
        $alreadyAssigned = RoomAssignment::whereIn('bagua_note_id', $allBaguaNoteIds)
            ->where(function ($query) use ($type, $id) {
                if ($type === 'customer') {
                    $query->where('customer_id', $id);
                } else {
                    $query->where('family_member_id', $id);
                }
            })
            ->exists();

        if ($alreadyAssigned) {
            $this->dispatch('notify', message: __('Person is already assigned to a room on this floor plan.'), type: 'error');
            return;
        }

        // 4. Create Assignment
        RoomAssignment::create([
            'bagua_note_id' => $baguaNote->id,
            'customer_id' => $type === 'customer' ? $id : null,
            'family_member_id' => $type === 'family_member' ? $id : null,
        ]);

        $this->refreshAndDispatch();
        $this->dispatch('notify', message: __('Assigned successfully!'));
    }

    public function removeAssignment($assignmentId): void
    {
        $assignment = RoomAssignment::find($assignmentId);

        if (!$assignment) return;

        // Verify ownership via BaguaNote -> FloorPlan
        $note = $assignment->baguaNote;
        if (!$note || $note->floor_plan_id !== $this->floorPlan->id) {
             return;
        }

        $assignment->delete();

        $this->refreshAndDispatch();
        $this->dispatch('notify', message: __('Assignment removed.'));
    }

    private function refreshAndDispatch()
    {
        $this->floorPlan->load(['baguaNotes.roomAssignments.familyMember', 'baguaNotes.roomAssignments.customer']);
        $this->dispatch('bagua-updated', data: $this->getBaguaNotesData());
    }

    public function saveBounds($bounds): void
    {
        $this->floorPlan->update(['outer_bounds' => $bounds]);
        $this->dispatch('notify', message: __('Changes saved successfully!'));
    }

    public function updateVentilationDirection(): void
    {
        $this->validate();

        $this->floorPlan->project->update([
            'ventilation_direction' => $this->ventilationDirection
        ]);

        $this->calculateBagua();
        $this->dispatch('notify', message: __('Direction updated and Bagua recalculated!'));
    }

    public function getBaguaNotesData(): array
    {
        return $this->floorPlan->baguaNotes->map(fn($n) => [
            'gua' => $n->gua_number,
            'col' => 2 - (($n->gua_number - 1) % 3),
            'row' => 2 - floor(($n->gua_number - 1) / 3),
            'data' => $this->decodeContent($n->content),
            'assignments' => $n->roomAssignments->map(fn($a) => [
                'id' => $a->id,
                'person_name' => $a->familyMember ? $a->familyMember->name : ($a->customer ? $a->customer->name : '?'),
                'type' => $a->familyMember ? 'family_member' : 'customer',
                'ming_gua' => $a->familyMember ? $a->familyMember->life_gua : ($a->customer ? $a->customer->life_gua : null),
                'element' => $a->familyMember ? (app(MingGuaCalculator::class)->getAttributes($a->familyMember->life_gua)['element'] ?? null) : ($a->customer ? (app(MingGuaCalculator::class)->getAttributes($a->customer->life_gua)['element'] ?? null) : null),
                'colors' => $a->familyMember ? app(MingGuaCalculator::class)->getElementColors(app(MingGuaCalculator::class)->getAttributes($a->familyMember->life_gua)['element'] ?? '') : ($a->customer ? app(MingGuaCalculator::class)->getElementColors(app(MingGuaCalculator::class)->getAttributes($a->customer->life_gua)['element'] ?? '') : null),
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($a->familyMember ? $a->familyMember->name : ($a->customer ? $a->customer->name : '?'))
            ])->values()->toArray()
        ])->values()->toArray();
    }

    private function decodeContent($content)
    {
        $data = json_decode($content, true);
        if (!$data && $content) {
             $data = json_decode(json_decode($content, true), true);
        }
        return $data ?? [];
    }

    public function calculateBagua(): void
    {
        if (!$this->floorPlan->outer_bounds) {
            $this->dispatch('notify', message: __('Missing bounds!'));
            return;
        }

        try {
            app(MingGuaCalculator::class)->calculateClassicalBaguaForFloorPlan(
                $this->floorPlan,
                $this->ventilationDirection ?? 0
            );
        } catch (Exception $e) {
            Log::error('Bagua FEHLER', ['error' => $e->getMessage()]);
            $this->dispatch('notify', message: __('Error') . ': ' . $e->getMessage());
            return;
        }

        $this->floorPlan->refresh();
        $this->floorPlan->load(['baguaNotes.roomAssignments.familyMember', 'baguaNotes.roomAssignments.customer']);
        $this->baguaNotesCount = $this->floorPlan->baguaNotes()->count();

        $this->dispatch('bagua-updated', data: $this->getBaguaNotesData());
    }
};

?>

<div x-data="baguaEditor({
        bounds: @js($floorPlan->outer_bounds),
        imageUrl: @js($imageUrl),
        renderWidth: {{ $renderWidth ?? 500 }},
        renderHeight: {{ $renderHeight ?? 500 }},
        baguaNotes: @js($this->getBaguaNotesData()),
        familyMembers: @js($familyMembers)
    })" class="h-[calc(100vh-65px)] flex flex-col bg-zinc-100 dark:bg-zinc-950">

    <!-- TOOLBAR -->
    <div
        class="h-16 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between px-6 bg-white dark:bg-zinc-900 shrink-0 z-20">
        <div class="flex items-center gap-4">
            <flux:button icon="arrow-left" variant="subtle"
                :href="route('modules.bagua.show', ['customer' => $floorPlan->project->customer_id, 'tab' => 'map'])">
                {{ __('Back') }}
            </flux:button>

            <div>
                <h2 class="font-bold text-zinc-900 dark:text-white">{{ $floorPlan->title }}</h2>
                <p class="text-xs text-zinc-400">
                    {{ __('Adjust the grid to match the house corners.') }}
                </p>
            </div>
        </div>

        <!-- Ventilation Direction Input -->
        <div class="flex items-center gap-2 ml-4">
            <label class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Ventilation Direction') }}:
            </label>
            <input type="number" wire:model.live="ventilationDirection" min="0" max="360" step="1"
                class="w-20 px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-700 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white"
                placeholder="0-360">
            <span class="text-xs text-zinc-400">°</span>
            <flux:button size="sm" wire:click="updateVentilationDirection">
                {{ __('Apply') }}
            </flux:button>
        </div>

        <div class="flex gap-2">
            <flux:button wire:click="calculateBagua">
                {{ __('Bagua') }}
                @if($baguaNotesCount > 0)
                    <span class="text-xs">({{ $baguaNotesCount }})</span>
                @endif
            </flux:button>

            <flux:button variant="primary" @click="save">
                {{ __('Save Changes') }}
            </flux:button>
        </div>
    </div>

    <!-- CANVAS AREA + SIDEBAR -->
    <div class="flex-1 flex overflow-hidden">
        <div class="flex-1 relative overflow-hidden flex items-center justify-center p-8" @dragover.prevent
            @drop.prevent="onDropMember($event)">
            <div
                class="relative shadow-2xl bg-white select-none max-w-full max-h-full transition-all duration-200 ease-out">

                <!-- 1. BILD -->
                <img x-ref="image" :src="imageUrl" @load="initDimensions" alt="{{ __('Floor plan') }}"
                    class="block max-w-full max-h-[calc(100vh-150px)] object-contain pointer-events-none select-none">

                <!-- 2. SVG OVERLAY -->
                <svg class="absolute inset-0 w-full h-full z-10 overflow-visible pointer-events-none"
                    :viewBox="`0 0 ${renderWidth} ${renderHeight}`">

                    <!-- 3x3 GRID -->
                    <g class="opacity-60">
                        <line :x1="toView(h.x1)" :y1="toView(h.y1 + (h.y2-h.y1)*0.33)" :x2="toView(h.x2)"
                            :y2="toView(h.y1 + (h.y2-h.y1)*0.33)" stroke="#ef4444" stroke-width="2"
                            vector-effect="non-scaling-stroke" />
                        <line :x1="toView(h.x1)" :y1="toView(h.y1 + (h.y2-h.y1)*0.66)" :x2="toView(h.x2)"
                            :y2="toView(h.y1 + (h.y2-h.y1)*0.66)" stroke="#ef4444" stroke-width="2"
                            vector-effect="non-scaling-stroke" />
                        <line :x1="toView(h.x1 + (h.x2-h.x1)*0.33)" :y1="toView(h.y1)"
                            :x2="toView(h.x1 + (h.x2-h.x1)*0.33)" :y2="toView(h.y2)" stroke="#ef4444" stroke-width="2"
                            vector-effect="non-scaling-stroke" />
                        <line :x1="toView(h.x1 + (h.x2-h.x1)*0.66)" :y1="toView(h.y1)"
                            :x2="toView(h.x1 + (h.x2-h.x1)*0.66)" :y2="toView(h.y2)" stroke="#ef4444" stroke-width="2"
                            vector-effect="non-scaling-stroke" />
                        <rect :x="toView(h.x1)" :y="toView(h.y1)" :width="toView(h.x2 - h.x1)"
                            :height="toView(h.y2 - h.y1)" fill="none" stroke="#3b82f6" stroke-width="2"
                            vector-effect="non-scaling-stroke" />
                    </g>

                    <!-- DB NOTES (Trigram-Rechtecke) -->
                    @foreach($floorPlan->baguaNotes as $note)
                        @php
                            $rawContent = $note->content;
                            $data = json_decode($rawContent, true);
                            if (!$data)
                                $data = json_decode(json_decode($rawContent, true), true) ?? [];

                            $guaNum = $note->gua_number;
                            $trigram = $data['name'] ?? '???';
                            $direction = $data['direction'] ?? '?';
                            $bgcolor = $data['bg_color'] ?? '#d1d5db';
                            $color = $data['color'] ?? '#6b7280';

                            $row = 2 - floor(($guaNum - 1) / 3);
                            $col = 2 - (($guaNum - 1) % 3);
                        @endphp

                        <g>
                            <rect :x="toView(h.x1 + {{ $col }} * (h.x2-h.x1)/3)"
                                :y="toView(h.y1 + {{ $row }} * (h.y2-h.y1)/3)" :width="toView((h.x2-h.x1)/3)"
                                :height="toView((h.y2-h.y1)/3)" fill="{{ $bgcolor }}" fill-opacity="0.6"
                                stroke="{{ $color }}" stroke-width="2" rx="8" />
                            <text :x="toView(h.x1 + ({{ $col }} + 0.5) * (h.x2-h.x1)/3)"
                                :y="toView(h.y1 + {{ $row }} * (h.y2-h.y1)/3) + 16" font-size="12" font-weight="600"
                                fill="{{ $color }}" text-anchor="middle">{{ $direction }}</text>
                            <text :x="toView(h.x1 + ({{ $col }} + 0.5) * (h.x2-h.x1)/3)"
                                :y="toView(h.y1 + ({{ $row }} + 0.5) * (h.y2-h.y1)/3) + 4" font-size="16" font-weight="700"
                                fill="{{ $color }}" text-anchor="middle">{{ $trigram }}</text>
                        </g>
                    @endforeach
                </svg>

                <!-- ASSIGNED MEMBERS OVERLAY -->
                <template x-for="note in baguaNotes" :key="note.gua">
                    <div class="absolute flex flex-wrap gap-1 justify-center content-center p-1 pointer-events-auto"
                        :style="`
                        left: ${toView(h.x1 + note.col * (h.x2-h.x1)/3)}px;
                        top: ${toView(h.y1 + note.row * (h.y2-h.y1)/3) + 30}px;
                        width: ${toView((h.x2-h.x1)/3)}px;
                        height: ${toView((h.y2-h.y1)/3) - 30}px;
                        `">
                        <template x-for="assign in note.assignments" :key="assign.id">
                            <div class="relative group flex items-center justify-center rounded-full shadow-md border-2 p-0.5 transition-all hover:scale-110"
                                :class="assign.colors ? assign.colors[2] + ' ' + assign.colors[1] : 'bg-white border-zinc-200 dark:border-zinc-700'"
                                :title="assign.person_name + ' (Gua ' + assign.ming_gua + ')'">
                                <img :src="assign.avatar" :alt="assign.person_name" class="w-10 h-10 rounded-full object-cover">
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-[10px] font-bold"
                                     :class="assign.colors ? assign.colors[0] : 'text-zinc-500'"
                                     x-text="assign.ming_gua"></div>
                                <button @click="$wire.removeAssignment(assign.id)"
                                    class="absolute -top-1 -right-1 bg-red-500 hover:bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs shadow-sm opacity-0 group-hover:opacity-100 transition-all transform z-10 cursor-pointer">
                                    &times;
                                </button>
                            </div>
                        </template>
                    </div>
                </template>


                <!-- HANDLES (unverändert) -->
                <div class="absolute w-6 h-6 bg-blue-500 rounded-full -translate-x-1/2 -translate-y-1/2 cursor-nw-resize z-20 shadow-md border-2 border-white hover:scale-125 transition-transform"
                    :style="`left: ${toView(h.x1)}px; top: ${toView(h.y1)}px`" @mousedown="startDrag('tl', $event)">
                </div>
                <div class="absolute w-6 h-6 bg-blue-500 rounded-full -translate-x-1/2 -translate-y-1/2 cursor-se-resize z-20 shadow-md border-2 border-white hover:scale-125 transition-transform"
                    :style="`left: ${toView(h.x2)}px; top: ${toView(h.y2)}px`" @mousedown="startDrag('br', $event)">
                </div>
                <div class="absolute w-8 h-8 bg-white/80 rounded cursor-move z-10 flex items-center justify-center border border-zinc-400 shadow-sm hover:bg-white hover:scale-110 transition-transform"
                    :style="`left: ${toView(h.x1 + (h.x2-h.x1)/2)}px; top: ${toView(h.y1 + (h.y2-h.y1)/2)}px; transform: translate(-50%, -50%)`"
                    @mousedown="startDrag('move', $event)">
                    <flux:icon.arrows-pointing-out class="size-4 text-black" />
                </div>

            </div>


        </div>


        <!-- SIDEBAR -->
        <div
            class="w-64 bg-white dark:bg-zinc-900 border-l border-zinc-200 dark:border-zinc-800 p-4 shrink-0 flex flex-col gap-4 overflow-y-auto z-30">
            <div>
                <h3 class="font-bold text-sm mb-2 text-zinc-900 dark:text-gray-100">{{ __('Family Members') }}</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">{{ __('Drag to assign to a room.') }}</p>

                <div class="flex flex-col gap-2">
                    <template x-for="member in familyMembers" :key="member.type + member.id">
                        <div draggable="true" @dragstart="startDragMember($event, member)"
                            class="flex items-center gap-3 p-2 rounded-xl hover:shadow-md cursor-grab active:cursor-grabbing border-2 transition-all group"
                            :class="member.colors ? member.colors[2] + ' ' + member.colors[1] : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700'">
                            <div class="relative">
                                <img :src="member.avatar" :alt="member.name" class="w-10 h-10 rounded-full border-2 border-white dark:border-zinc-900 shadow-sm">
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-[10px] font-bold shadow-sm"
                                     :class="member.colors ? member.colors[0] : 'text-zinc-500'"
                                     x-text="member.ming_gua"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold truncate text-zinc-900 dark:text-gray-100"
                                    x-text="member.name"></p>
                                <p class="text-[10px] uppercase tracking-wider font-semibold opacity-70"
                                   :class="member.colors ? member.colors[0] : 'text-zinc-500'"
                                   x-text="member.element || '{{ __('Unknown') }}'"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('baguaEditor', (config) => ({
            imageUrl: config.imageUrl,
            naturalWidth: 0, naturalHeight: 0, renderWidth: 0, renderHeight: 0, scale: 1,
            h: {
                x1: config.bounds?.x1 || 100,
                y1: config.bounds?.y1 || 100,
                x2: config.bounds?.x2 || 500,
                y2: config.bounds?.y2 || 500
            },
            baguaNotes: config.baguaNotes || [],
            familyMembers: config.familyMembers || [],
            dragOverNote: null,
            dragging: null, startPos: { x: 0, y: 0 }, initialH: {},

            init() {
                window.addEventListener('resize', () => this.updateDimensions());
                this.$watch('imageUrl', () => this.checkImage());
                this.checkImage();

                // Listen for updates from Livewire (Browser Event)
                window.addEventListener('bagua-updated', (event) => {
                    console.log('Bagua Updated:', event.detail.data);
                    this.baguaNotes = event.detail.data;
                });
            },

            checkImage() {
                const img = this.$refs.image;
                if (!img) return;
                if (img.complete && img.naturalWidth > 0) this.initDimensions();
                else img.onload = () => this.initDimensions();
            },

            initDimensions() {
                const img = this.$refs.image;
                if (!img) return;
                this.naturalWidth = img.naturalWidth;
                this.naturalHeight = img.naturalHeight;
                if (!config.bounds?.x1) {
                    this.h.x1 = this.naturalWidth * 0.2;
                    this.h.y1 = this.naturalHeight * 0.2;
                    this.h.x2 = this.naturalWidth * 0.8;
                    this.h.y2 = this.naturalHeight * 0.8;
                }
                this.updateDimensions();
            },

            updateDimensions() {
                const img = this.$refs.image;
                if (!img) return;
                this.renderWidth = img.width;
                this.renderHeight = img.height;
                this.scale = this.naturalWidth > 0 ? (this.renderWidth / this.naturalWidth) : 1;
            },

            toView(val) {
                return val * this.scale;
            },
            toImg(val) {
                return val / this.scale;
            },

            startDrag(handle, e) {
                e.preventDefault();
                e.stopPropagation();
                this.dragging = handle;
                this.startPos = { x: e.clientX, y: e.clientY };
                this.initialH = JSON.parse(JSON.stringify(this.h));
                this._boundOnDrag = this.onDrag.bind(this);
                this._boundStopDrag = this.stopDrag.bind(this);
                window.addEventListener('mousemove', this._boundOnDrag);
                window.addEventListener('mouseup', this._boundStopDrag);
            },

            onDrag(e) {
                if (!this.dragging) return;
                const dxView = e.clientX - this.startPos.x;
                const dyView = e.clientY - this.startPos.y;
                const dx = this.toImg(dxView);
                const dy = this.toImg(dyView);

                if (this.dragging === 'tl') {
                    this.h.x1 = this.initialH.x1 + dx;
                    this.h.y1 = this.initialH.y1 + dy;
                } else if (this.dragging === 'br') {
                    this.h.x2 = this.initialH.x2 + dx;
                    this.h.y2 = this.initialH.y2 + dy;
                } else if (this.dragging === 'move') {
                    this.h.x1 = this.initialH.x1 + dx;
                    this.h.y1 = this.initialH.y1 + dy;
                    this.h.x2 = this.initialH.x2 + dx;
                    this.h.y2 = this.initialH.y2 + dy;
                }
            },

            stopDrag() {
                window.removeEventListener('mousemove', this._boundOnDrag);
                window.removeEventListener('mouseup', this._boundStopDrag);
                this.dragging = null;
            },

            save() {
                const newBounds = {
                    x1: Math.round(this.h.x1),
                    y1: Math.round(this.h.y1),
                    x2: Math.round(this.h.x2),
                    y2: Math.round(this.h.y2),
                    image_width: this.naturalWidth,
                    image_height: this.naturalHeight
                };
                this.$wire.saveBounds(newBounds);
            },



            startDragMember(e, member) {
                e.dataTransfer.effectAllowed = 'copy';
                e.dataTransfer.setData('text/plain', JSON.stringify(member));
                e.dataTransfer.setData('application/json', JSON.stringify(member));
            },

            onDropMember(e) {
                const img = this.$refs.image;
                if (!img) return;

                // 1. Get Drop Coordinates relative to Image/Grid
                const rect = img.getBoundingClientRect();
                const xView = e.clientX - rect.left;
                const yView = e.clientY - rect.top;

                // 2. Convert to Grid Relative Coords (0..3, 0..3)
                // Grid Top-Left in View Coords:
                const gridX1 = this.toView(this.h.x1);
                const gridY1 = this.toView(this.h.y1);
                const gridW_Total = this.toView(this.h.x2 - this.h.x1);
                const gridH_Total = this.toView(this.h.y2 - this.h.y1);

                // Relative position within grid (0.0 to 1.0)
                const relX = (xView - gridX1) / gridW_Total;
                const relY = (yView - gridY1) / gridH_Total;

                if (relX < 0 || relX > 1 || relY < 0 || relY > 1) {
                    console.log("Dropped outside grid");
                    return;
                }

                // 3. Determine Row/Col (3x3)
                const col = Math.floor(relX * 3);
                const row = Math.floor(relY * 3);

                // 4. Map to Gua
                // Find which "note" is at this col/row.

                const targetNote = this.baguaNotes.find(n => n.col === col && n.row === row);

                if (!targetNote) {
                    // Maybe Center (col 1, row 1) which might be empty/null in BaguaNotes?
                    // if col=1, row=1 -> Center (Gua 5 usually invalid or special)
                    console.log("No bagua note found at", col, row);
                    return;
                }

                // 5. Get Member Data
                try {
                    const memberData = JSON.parse(e.dataTransfer.getData('application/json'));
                    // 6. Call Livewire
                    this.$wire.assignPerson(memberData.type, memberData.id, targetNote.gua);
                } catch (err) {
                    console.error("Drop error", err);
                }
            }
        }))
    });
</script>
