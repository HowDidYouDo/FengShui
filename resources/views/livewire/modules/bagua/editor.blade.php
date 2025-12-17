<?php
// /resources/views/livewire/modules/bagua/editor.blade.php
use App\Models\FloorPlan;
use App\Services\Metaphysics\MingGuaCalculator;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component {
    use AuthorizesRequests;

    public FloorPlan $floorPlan;
    public $imageUrl;
    public $baguaNotesCount = 0;

    #[Validate('nullable|numeric|min:0|max:360')]
    public $ventilationDirection;

    public function mount(FloorPlan $floorPlan): void
    {
        $floorPlan->load(['project', 'baguaNotes']);
        $this->authorize('view', $floorPlan->project);
        $this->floorPlan = $floorPlan;
        $this->ventilationDirection = $floorPlan->project->ventilation_direction ?? 0;

        if ($floorPlan->hasMedia('floor_plans')) {
            $media = $floorPlan->getFirstMedia('floor_plans');
            $this->imageUrl = route('media.floor-plans', ['floorPlan' => $floorPlan->id, 'media' => $media->id]);
        }
        $this->baguaNotesCount = $floorPlan->baguaNotes()->count();
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

    public function calculateBagua(): void
    {
        if (!$this->floorPlan->outer_bounds) {
            $this->dispatch('notify', message: __('Missing bounds!'));
            return;
        }

        try {
            $mingGua = app(MingGuaCalculator::class);
            $result = $mingGua->calculateClassicalBaguaForFloorPlan(
                $this->floorPlan,
                $this->ventilationDirection ?? 0
            );
        } catch (\Exception $e) {
            Log::error('Bagua FEHLER', ['error' => $e->getMessage()]);
            $this->dispatch('notify', message: 'Fehler: ' . $e->getMessage());
            return;
        }

        $this->floorPlan->refresh();
        $this->floorPlan->load('baguaNotes');
        $this->baguaNotesCount = $this->floorPlan->baguaNotes()->count();
    }
};

?>

<div x-data="baguaEditor({
        bounds: @js($floorPlan->outer_bounds),
        imageUrl: @js($imageUrl),
        renderWidth: {{ $renderWidth ?? 500 }},
        renderHeight: {{ $renderHeight ?? 500 }},
        baguaNotes: @js($floorPlan->baguaNotes->map(fn($n) => [
            'gua' => $n->gua_number,
            'col' => ($n->gua_number - 1) % 3,
            'row' => floor(($n->gua_number - 1) / 3),
            'data' => json_decode($n->content, true) ?? []
        ]))
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
            <input type="number"
                   wire:model.live="ventilationDirection"
                   min="0"
                   max="360"
                   step="1"
                   class="w-20 px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-700 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white"
                   placeholder="0-360">
            <span class="text-xs text-zinc-400">°</span>
            <flux:button size="sm" wire:click="updateVentilationDirection">
                {{ __('Apply') }}
            </flux:button>
        </div>

        <div class="flex gap-2">
            <flux:button
                wire:click="calculateBagua"
            >
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

    <!-- CANVAS AREA -->
    <div class="flex-1 relative overflow-hidden flex items-center justify-center p-8">
        <div
            class="relative shadow-2xl bg-white select-none max-w-full max-h-full transition-all duration-200 ease-out">

            <!-- 1. BILD -->
            <img x-ref="image" :src="imageUrl" @load="initDimensions"
                 class="block max-w-full max-h-[calc(100vh-150px)] object-contain pointer-events-none select-none">

            <!-- 2. SVG OVERLAY -->
            <svg class="absolute inset-0 w-full h-full z-10 overflow-visible pointer-events-none"
                 :viewBox="`0 0 ${renderWidth} ${renderHeight}`">

                <!-- 3x3 GRID -->
                <g class="opacity-60">
                    <line :x1="toView(h.x1)" :y1="toView(h.y1 + (h.y2-h.y1)*0.33)"
                          :x2="toView(h.x2)" :y2="toView(h.y1 + (h.y2-h.y1)*0.33)"
                          stroke="#ef4444" stroke-width="2" vector-effect="non-scaling-stroke"/>
                    <line :x1="toView(h.x1)" :y1="toView(h.y1 + (h.y2-h.y1)*0.66)"
                          :x2="toView(h.x2)" :y2="toView(h.y1 + (h.y2-h.y1)*0.66)"
                          stroke="#ef4444" stroke-width="2" vector-effect="non-scaling-stroke"/>
                    <line :x1="toView(h.x1 + (h.x2-h.x1)*0.33)" :y1="toView(h.y1)"
                          :x2="toView(h.x1 + (h.x2-h.x1)*0.33)" :y2="toView(h.y2)"
                          stroke="#ef4444" stroke-width="2" vector-effect="non-scaling-stroke"/>
                    <line :x1="toView(h.x1 + (h.x2-h.x1)*0.66)" :y1="toView(h.y1)"
                          :x2="toView(h.x1 + (h.x2-h.x1)*0.66)" :y2="toView(h.y2)"
                          stroke="#ef4444" stroke-width="2" vector-effect="non-scaling-stroke"/>
                    <rect :x="toView(h.x1)" :y="toView(h.y1)"
                          :width="toView(h.x2 - h.x1)" :height="toView(h.y2 - h.y1)"
                          fill="none" stroke="#3b82f6" stroke-width="2" vector-effect="non-scaling-stroke"/>
                </g>

                <!-- DB NOTES (Trigram-Rechtecke) -->
                @foreach($floorPlan->baguaNotes as $note)
                    @php
                        $rawContent = $note->content;
                        $data = json_decode($rawContent, true);
                        if (!$data) $data = json_decode(json_decode($rawContent, true), true) ?? [];

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
                              :y="toView(h.y1 + {{ $row }} * (h.y2-h.y1)/3)"
                              :width="toView((h.x2-h.x1)/3)"
                              :height="toView((h.y2-h.y1)/3)"
                              fill="{{ $bgcolor }}"
                              fill-opacity="0.6"
                              stroke="{{ $color }}"
                              stroke-width="2"
                              rx="8"/>
                        <text :x="toView(h.x1 + ({{ $col }} + 0.5) * (h.x2-h.x1)/3)"
                              :y="toView(h.y1 + {{ $row }} * (h.y2-h.y1)/3) + 16"
                              font-size="12"
                              font-weight="600"
                              fill="{{ $color }}"
                              text-anchor="middle">{{ $direction }}</text>
                        <text :x="toView(h.x1 + ({{ $col }} + 0.5) * (h.x2-h.x1)/3)"
                              :y="toView(h.y1 + ({{ $row }} + 0.5) * (h.y2-h.y1)/3) + 4"
                              font-size="16"
                              font-weight="700"
                              fill="{{ $color }}"
                              text-anchor="middle">{{ $trigram }}</text>
                    </g>
                @endforeach
            </svg>


            <!-- HANDLES (unverändert) -->
            <div
                class="absolute w-6 h-6 bg-blue-500 rounded-full -translate-x-1/2 -translate-y-1/2 cursor-nw-resize z-20 shadow-md border-2 border-white hover:scale-125 transition-transform"
                :style="`left: ${toView(h.x1)}px; top: ${toView(h.y1)}px`" @mousedown="startDrag('tl', $event)"></div>
            <div
                class="absolute w-6 h-6 bg-blue-500 rounded-full -translate-x-1/2 -translate-y-1/2 cursor-se-resize z-20 shadow-md border-2 border-white hover:scale-125 transition-transform"
                :style="`left: ${toView(h.x2)}px; top: ${toView(h.y2)}px`" @mousedown="startDrag('br', $event)"></div>
            <div
                class="absolute w-8 h-8 bg-white/80 rounded cursor-move z-10 flex items-center justify-center border border-zinc-400 shadow-sm hover:bg-white hover:scale-110 transition-transform"
                :style="`left: ${toView(h.x1 + (h.x2-h.x1)/2)}px; top: ${toView(h.y1 + (h.y2-h.y1)/2)}px; transform: translate(-50%, -50%)`"
                @mousedown="startDrag('move', $event)">
                <flux:icon.arrows-pointing-out class="size-4 text-black"/>
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
            dragging: null, startPos: {x: 0, y: 0}, initialH: {},

            init() {
                window.addEventListener('resize', () => this.updateDimensions());
                this.$watch('imageUrl', () => this.checkImage());
                this.checkImage();
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
                this.startPos = {x: e.clientX, y: e.clientY};
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
            }
        }))
    });
</script>
