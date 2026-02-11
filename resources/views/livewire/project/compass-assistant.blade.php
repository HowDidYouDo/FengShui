<div x-data="{
    step: @entangle('step'),
    compassReading: @entangle('compassReading'),

    // --- Pure Alpine drawing state (NO entangle = instant reactivity) ---
    isDragging: false,
    dragMode: 'none',
    startX: 0,
    startY: 0,
    endX: 0,
    endY: 0,
    lastMouseX: 0,
    lastMouseY: 0,
    angle: 0,
    length: 0,

    // --- Methods ---
    getContainerRect(e) {
        const container = e.target.closest('.image-container');
        return container ? container.getBoundingClientRect() : e.target.getBoundingClientRect();
    },

    startDrawing(e) {
        e.preventDefault();
        const rect = this.getContainerRect(e);
        const touch = e.touches ? e.touches[0] : e;
        const clickX = touch.clientX - rect.left;
        const clickY = touch.clientY - rect.top;

        if (this.length > 10) {
            if (this.dist(clickX, clickY, this.endX, this.endY) < 20) {
                this.dragMode = 'move-end';
                this.isDragging = true;
                return;
            }
            if (this.dist(clickX, clickY, this.startX, this.startY) < 20) {
                this.dragMode = 'move-start';
                this.isDragging = true;
                return;
            }
            if (e.target.dataset && e.target.dataset.role === 'arrow-hit') {
                this.dragMode = 'move-line';
                this.lastMouseX = clickX;
                this.lastMouseY = clickY;
                this.isDragging = true;
                return;
            }
        }

        this.dragMode = 'create';
        this.startX = clickX;
        this.startY = clickY;
        this.endX = clickX;
        this.endY = clickY;
        this.isDragging = true;
    },

    dist(ax, ay, bx, by) {
        return Math.sqrt((ax - bx) ** 2 + (ay - by) ** 2);
    },

    draw(e) {
        if (!this.isDragging) return;
        e.preventDefault();
        const rect = this.getContainerRect(e);
        const touch = e.touches ? e.touches[0] : e;
        const x = touch.clientX - rect.left;
        const y = touch.clientY - rect.top;

        switch (this.dragMode) {
            case 'create':
            case 'move-end':
                this.endX = x;
                this.endY = y;
                break;
            case 'move-start':
                this.startX = x;
                this.startY = y;
                break;
            case 'move-line':
                const dx = x - this.lastMouseX;
                const dy = y - this.lastMouseY;
                this.startX += dx;
                this.startY += dy;
                this.endX += dx;
                this.endY += dy;
                this.lastMouseX = x;
                this.lastMouseY = y;
                break;
        }
        this.recalc();
    },

    stopDrawing() {
        if (!this.isDragging) return;
        this.isDragging = false;
        this.dragMode = 'none';
        this.recalc();

        // Sync final values to Livewire (one-time, no lag)
        $wire.set('imageArrowAngle', this.angle);
        $wire.set('imageArrowLength', this.length);
    },

    recalc() {
        const dx = this.endX - this.startX;
        const dy = this.endY - this.startY;
        this.length = Math.sqrt(dx * dx + dy * dy);

        const rad = Math.atan2(dy, dx);
        let deg = rad * (180 / Math.PI);
        let compass = deg + 90;
        if (compass < 0) compass += 360;
        if (compass >= 360) compass -= 360;
        this.angle = compass;
    },

    // Midpoint getters for the label
    get midX() { return (this.startX + this.endX) / 2; },
    get midY() { return (this.startY + this.endY) / 2; },
    get angleText() { return this.angle.toFixed(1) + '°'; },
    get hasArrow() { return this.length > 10; },
}">
    <flux:modal wire:model="modalOpen" class="min-w-[20rem] md:min-w-[40rem]">
        <div class="space-y-6">

            <!-- HEADER -->
            <div>
                <flux:heading size="lg">{{ __('Compass Assistant') }}</flux:heading>
                <flux:subheading>{{ __('Determine North & Facing Direction') }}</flux:subheading>
            </div>

            <!-- STEP 1: INTRO -->
            <div x-show="step === 1" class="space-y-6">
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-6 border border-zinc-200 dark:border-zinc-700">
                    <h3 class="font-bold text-lg mb-4">{{ __('Instructions') }}</h3>
                    <ul class="space-y-3 text-sm text-zinc-600 dark:text-zinc-300 list-disc list-inside">
                        <li>{{ __('Ask the client which window is mainly used for ventilation.') }}</li>
                        <li>{{ __('Stand at this window looking out.') }}</li>
                        <li>{{ __('Hold the smartphone with the compass app active.') }}</li>
                        <li><strong>{{ __('Point the TOP of the phone INTO the room (away from the window).') }}</strong>
                        </li>
                        <li>{{ __('Note the compass reading (0-360°).') }}</li>
                    </ul>
                </div>

                <!-- Visual Aid -->
                <div class="flex justify-center py-4">
                    <div
                        class="relative w-32 h-32 border-2 border-dashed border-zinc-300 rounded-lg flex items-center justify-center">
                        <span class="text-xs text-zinc-400 absolute top-2">{{ __('Window') }}</span>
                        <div class="flex flex-col items-center gap-1">
                            <flux:icon.device-phone-mobile
                                class="size-12 text-zinc-800 dark:text-zinc-200 rotate-180" />
                            <flux:icon.arrow-down class="size-4 text-primary-500 animate-bounce" />
                            <span class="text-[10px] uppercase font-bold text-primary-500">{{ __('Into Room') }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <flux:button variant="primary" wire:click="nextStep">{{ __('Start Measurement') }}</flux:button>
                </div>
            </div>


            <!-- STEP 2: INPUT -->
            <div x-show="step === 2" class="space-y-6">

                @if($floorPlans->count() > 1)
                    <flux:select wire:model="selectedFloorPlanId" label="{{ __('Select Floor Plan') }}">
                        @foreach($floorPlans as $fp)
                            <flux:select.option value="{{ $fp->id }}">{{ $fp->title ?? __('Floor Plan') }}
                                {{ $loop->iteration }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- LEFT: Image Interaction -->
                    <div class="space-y-2">
                        <flux:label>{{ __('Draw Arrow pointing INTO the house') }}</flux:label>
                        <p class="text-xs text-zinc-500">{{ __('Click and drag from the window INWARDS.') }}</p>

                        @if($selectedFloorPlanId && $floorPlans->find($selectedFloorPlanId))
                            <div class="relative w-full h-64 bg-zinc-100 dark:bg-zinc-800 rounded-lg overflow-hidden border border-zinc-300 cursor-crosshair image-container select-none touch-none"
                                @mousedown="startDrawing($event)" @mousemove.window="draw($event)"
                                @mouseup.window="stopDrawing()" @touchstart.prevent="startDrawing($event)"
                                @touchmove.prevent.window="draw($event)" @touchend.window="stopDrawing()">

                                @php
                                    $currentPlan = $floorPlans->find($selectedFloorPlanId);
                                    $media = $currentPlan?->getFirstMedia('floor_plans');
                                    $imageUrl = $media ? route('media.floor-plans', ['floorPlan' => $currentPlan, 'media' => $media]) : '';
                                @endphp
                                <img src="{{ $imageUrl }}" class="w-full h-full object-contain pointer-events-none"
                                    draggable="false">

                                <!-- SVG Arrow Overlay -->
                                <svg class="absolute inset-0 w-full h-full pointer-events-none">
                                    <defs>
                                        <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5"
                                            orient="auto">
                                            <polygon points="0 0, 10 3.5, 0 7" fill="#f43f5e" />
                                        </marker>
                                    </defs>

                                    <!-- Invisible hit-area for grabbing the line -->
                                    <line x-show="hasArrow" :x1="startX" :y1="startY" :x2="endX" :y2="endY"
                                        stroke="transparent" stroke-width="20" class="pointer-events-auto cursor-move"
                                        data-role="arrow-hit" />

                                    <!-- Visible arrow line -->
                                    <line x-show="isDragging || hasArrow" :x1="startX" :y1="startY" :x2="endX" :y2="endY"
                                        stroke="#f43f5e" stroke-width="3" marker-end="url(#arrowhead)"
                                        class="pointer-events-none" />

                                    <!-- Start handle -->
                                    <circle x-show="hasArrow && !isDragging" :cx="startX" :cy="startY" r="6" fill="white"
                                        stroke="#f43f5e" stroke-width="2" class="pointer-events-auto cursor-grab" />

                                    <!-- End handle -->
                                    <circle x-show="hasArrow && !isDragging" :cx="endX" :cy="endY" r="6" fill="#f43f5e"
                                        stroke="white" stroke-width="2" class="pointer-events-auto cursor-grab" />

                                    <!-- Angle label on the arrow -->
                                    <template x-if="length > 25">
                                        <g>
                                            <rect :x="midX - 28" :y="midY - 12" width="56" height="22" rx="4"
                                                fill="rgba(255,255,255,0.92)" stroke="#f43f5e" stroke-width="1" />
                                            <text :x="midX" :y="midY + 3" text-anchor="middle" fill="#f43f5e" font-size="12"
                                                font-weight="700" x-text="angleText"></text>
                                        </g>
                                    </template>
                                </svg>
                            </div>
                        @else
                            <div class="w-full h-64 flex items-center justify-center bg-zinc-100 rounded-lg">
                                <span class="text-zinc-400">{{ __('No floor plan selected') }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-xs text-zinc-500">
                            <span>{{ __('Arrow Angle:') }} <span
                                    x-text="hasArrow ? angle.toFixed(1) + '°' : '-'"></span></span>
                            <span class="text-red-500"
                                x-show="length > 0 && length < 10">{{ __('Line too short') }}</span>
                        </div>
                        @error('imageArrowAngle') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- RIGHT: Compass Input -->
                    <div class="space-y-4">
                        <div
                            class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                            <h4 class="font-bold text-sm text-blue-800 dark:text-blue-300 mb-2">
                                {{ __('Compass Reading') }}</h4>
                            <p class="text-xs text-blue-600 dark:text-blue-400 mb-4">
                                {{ __('Enter the degrees shown on the phone when pointing INTO the room.') }}</p>

                            <flux:input wire:model="compassReading" type="number" step="0.1" min="0" max="360"
                                label="{{ __('Reading (°)') }}" placeholder="0.00" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-between pt-4">
                    <flux:button variant="ghost" wire:click="previousStep">{{ __('Back') }}</flux:button>
                    <flux:button variant="primary" wire:click="nextStep" x-bind:disabled="!compassReading || !hasArrow">
                        {{ __('Calculate') }}</flux:button>
                </div>
            </div>


            <!-- STEP 3: PREVIEW -->
            <div x-show="step === 3" class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Result Card 1: North Deviation -->
                    <div
                        class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 text-center">
                        <div class="text-xs text-zinc-500 uppercase tracking-wider mb-1">{{ __('North Deviation') }}
                        </div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $calculatedNorthDeviation }}°
                        </div>
                        <div class="text-xs text-zinc-400 mt-1">{{ __('Map Rotation') }}</div>
                    </div>

                    <!-- Result Card 2: Facing -->
                    <div
                        class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 text-center">
                        <div class="text-xs text-zinc-500 uppercase tracking-wider mb-1">{{ __('Facing (Out)') }}</div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $calculatedFacing }}°</div>
                        <div class="text-xs text-zinc-400 mt-1">{{ __('House Face') }}</div>
                    </div>

                    <!-- Result Card 3: Sitting -->
                    <div
                        class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 text-center">
                        <div class="text-xs text-zinc-500 uppercase tracking-wider mb-1">{{ __('Sitting (In)') }}</div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $calculatedSitting }}°</div>
                        <div class="text-xs text-zinc-400 mt-1">{{ __('Energy Entry') }}</div>
                    </div>
                </div>

                <div
                    class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 text-sm text-green-800 dark:text-green-300">
                    <flux:icon.check-circle class="inline-block size-4 mr-1" />
                    {{ __('Calculation complete. Click Apply to update the project settings.') }}
                </div>

                <div class="flex justify-between pt-4">
                    <flux:button variant="ghost" wire:click="previousStep">{{ __('Back') }}</flux:button>
                    <flux:button variant="primary" wire:click="save">{{ __('Apply Settings') }}</flux:button>
                </div>
            </div>

        </div>
    </flux:modal>
</div>