<?php
// /resources/views/livewire/modules/bagua/managefamilymembers.blade.php
use App\Models\Customer;
use App\Models\FamilyMember;
use Livewire\Volt\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;

new class extends Component {
    use AuthorizesRequests;

    public Customer $customer;

    // Modal State
    public bool $showCreateModal = false;
    public bool $showEditModal = false;

    // Form Fields
    public ?int $editingMemberId = null;
    public string $name = '';
    public string $relationship = '';
    public string $birth_date = '';
    public string $birth_time = '';
    public string $birth_place = '';
    public string $gender = 'm';

    public function mount(Customer $customer): void
    {
        // Security: Darf ich den Kunden sehen?
        $this->authorize('view', $customer);
        $this->customer = $customer;
    }

    public function addMember(): void
    {
        // Check limit (max 5 family members)
        if ($this->customer->familyMembers()->count() >= 5) {
            $this->addError('name', __('Maximum of 5 family members reached.'));
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'relationship' => ['nullable', Rule::in(array_keys(FamilyMember::getRelationships()))],
            'birth_date' => 'required|date',
            'birth_time' => 'required|date_format:H:i',
            'birth_place' => 'required|string|max:255',
            'gender' => 'required|in:m,f',
        ]);

        $this->customer->familyMembers()->create([
            'name' => $this->name,
            'relationship' => $this->relationship,
            'birth_date' => $this->birth_date,
            'birth_time' => $this->birth_time,
            'birth_place' => $this->birth_place,
            'gender' => $this->gender,
        ]);

        // Reset & Close
        $this->reset(['name', 'relationship', 'birth_date', 'birth_time', 'birth_place', 'gender', 'showCreateModal']);
        $this->gender = 'm'; // Reset to default
    }

    public function editMember(int $memberId): void
    {
        // Security: Gehört das Mitglied zu meinem Kunden?
        $member = $this->customer->familyMembers()->find($memberId);

        if (!$member) {
            return;
        }

        // Load data into form
        $this->editingMemberId = $member->id;
        $this->name = $member->name;
        $this->relationship = $member->relationship ?? '';
        $this->birth_date = $member->birth_date->format('Y-m-d');
        $this->birth_time = $member->birth_time ? \Carbon\Carbon::parse($member->birth_time)->format('H:i') : '';
        $this->birth_place = $member->birth_place ?? '';
        $this->gender = $member->gender ?? 'm';

        $this->showEditModal = true;
    }

    public function updateMember(): void
    {
        // Security: Gehört das Mitglied zu meinem Kunden?
        $member = $this->customer->familyMembers()->find($this->editingMemberId);

        if (!$member) {
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'relationship' => ['nullable', Rule::in(array_keys(FamilyMember::getRelationships()))],
            'birth_date' => 'required|date',
            'birth_time' => 'required|date_format:H:i',
            'birth_place' => 'required|string|max:255',
            'gender' => 'required|in:m,f',
        ]);

        $member->update([
            'name' => $this->name,
            'relationship' => $this->relationship,
            'birth_date' => $this->birth_date,
            'birth_time' => $this->birth_time,
            'birth_place' => $this->birth_place,
            'gender' => $this->gender,
        ]);

        // Reset & Close
        $this->reset(['editingMemberId', 'name', 'relationship', 'birth_date', 'birth_time', 'birth_place', 'gender', 'showEditModal']);
        $this->gender = 'm';
    }

    public function deleteMember(int $memberId): void
    {
        // Security: Gehört das Mitglied zu meinem Kunden?
        $member = $this->customer->familyMembers()->find($memberId);

        if ($member) {
            $member->delete();
        }
    }

    public function with()
    {
        return [
            'members' => $this->customer->familyMembers()->orderBy('created_at')->get(),
        ];
    }
};
?>

<div class="space-y-6">

    <!-- HEADER -->
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Family Members') }}</h3>
            <p class="text-sm text-zinc-500">{{ __('Add up to 5 family members for comprehensive analysis.') }}</p>
        </div>
        <div class="text-xs text-zinc-400">
            {{ $members->count() }} / 5 {{ __('Members') }}
        </div>
    </div>

    <!-- ADD BUTTON (Only show if limit not reached) -->
    @if($members->count() < 5)
        <button
            wire:click="$set('showCreateModal', true)"
            class="w-full flex items-center justify-center gap-2 p-4 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 hover:border-brand-blue hover:bg-brand-blue/5 transition-all text-zinc-400 hover:text-brand-blue"
        >
            <flux:icon.plus class="size-5" />
            <span class="font-medium">{{ __('Add Family Member') }}</span>
        </button>
    @endif

    <!-- MEMBERS LIST -->
    @if($members->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($members as $member)
                <div class="group relative bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 hover:shadow-md transition-all">

                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <!-- Avatar -->
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-brand-orange/10 text-brand-orange font-bold text-lg shrink-0">
                                {{ substr($member->name, 0, 1) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <h4 class="font-bold text-zinc-900 dark:text-white truncate">{{ $member->name }}</h4>
                                @if($member->relationship)
                                    <p class="text-xs text-zinc-500">{{ $member->getRelationshipLabel() }}</p>
                                @else
                                    <p class="text-xs text-zinc-400 italic">{{ __('No relationship specified') }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <flux:button
                                icon="pencil"
                                size="sm"
                                variant="subtle"
                                wire:click="editMember({{ $member->id }})"
                            />
                            <flux:button
                                icon="trash"
                                size="sm"
                                variant="danger"
                                wire:click="deleteMember({{ $member->id }})"
                                wire:confirm="{{ __('Delete this family member permanently?') }}"
                            />
                        </div>
                    </div>

                    <!-- Birth Data -->
                    <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800/50 text-sm space-y-1">
                        <div class="flex items-center gap-2 text-zinc-500">
                            <flux:icon.calendar class="size-4 text-zinc-400" />
                            <span>{{ $member->birth_date->format('d.m.Y') }}</span>
                            @if($member->birth_time)
                                <span class="text-zinc-400">•</span>
                                <flux:icon.clock class="size-4 text-zinc-400" />
                                <span>{{ \Carbon\Carbon::parse($member->birth_time)->format('H:i') }}</span>
                            @endif
                        </div>

                        @if($member->birth_place)
                            <div class="flex items-center gap-2 text-zinc-500">
                                <flux:icon.map-pin class="size-4 text-zinc-400" />
                                <span>{{ $member->birth_place }}</span>
                            </div>
                        @endif

                        <!-- Gender Badge -->
                        <div class="flex items-center gap-2">
                            @if($member->gender === 'm')
                                <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-medium">
                                    <flux:icon.user class="size-3" />
                                    {{ __('Male') }}
                                </div>
                            @else
                                <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300 text-xs font-medium">
                                    <flux:icon.user-circle class="size-3" />
                                    {{ __('Female') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8 text-zinc-400 border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl">
            <flux:icon.users class="size-12 mx-auto mb-2 text-zinc-300" />
            <p>{{ __('No family members added yet.') }}</p>
        </div>
    @endif

    <!-- CREATE MODAL -->
    <flux:modal wire:model="showCreateModal" class="min-w-[20rem] md:min-w-[40rem]">
        <div class="space-y-6">
            <div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Add Family Member') }}</h3>
                <p class="text-sm text-zinc-500">{{ __('Add a family member to include them in the Feng Shui analysis.') }}</p>
            </div>

            <form wire:submit="addMember" class="space-y-6">

                <!-- Basic Information -->
                <div class="space-y-4">
                    <h4 class="text-sm font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">{{ __('Basic Information') }}</h4>

                    <flux:input
                        wire:model="name"
                        :label="__('Full Name')"
                        :placeholder="__('e.g. Maria Schmidt')"
                        required
                    />

                    <flux:select
                        wire:model="relationship"
                        :label="__('Relationship')"
                        :placeholder="__('Select relationship...')"
                    >
                        <flux:select.option value="">{{ __('No relationship specified') }}</flux:select.option>
                        @foreach(FamilyMember::getRelationships() as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Birth Data (Required for Gua) -->
                <div class="space-y-4">
                    <h4 class="text-sm font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">{{ __('Birth Data') }}</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left: Birth Place & Gender -->
                        <div class="space-y-4">
                            <flux:input
                                wire:model="birth_place"
                                :label="__('Birth Place')"
                                icon="map-pin"
                                type="text"
                                :placeholder="__('e.g. Hamburg')"
                                required
                            />

                            <flux:radio.group wire:model="gender" :label="__('Biological Sex')" required>
                                <div class="flex gap-4">
                                    <!-- Male Option -->
                                    <label class="flex-1 relative cursor-pointer">
                                        <input type="radio" wire:model="gender" value="m" class="peer sr-only">
                                        <div class="p-4 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 hover:border-brand-blue dark:hover:border-brand-blue peer-checked:border-brand-orange peer-checked:bg-brand-orange/5 transition-all flex items-center justify-center gap-3 h-full">
                                            <flux:icon.user class="size-5 text-zinc-400 peer-checked:text-brand-orange" />
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300 peer-checked:text-brand-orange">
                                                {{ __('Male') }}
                                            </span>
                                        </div>
                                        <div class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 text-brand-orange transition-opacity">
                                            <flux:icon.check-circle class="size-4" />
                                        </div>
                                    </label>

                                    <!-- Female Option -->
                                    <label class="flex-1 relative cursor-pointer">
                                        <input type="radio" wire:model="gender" value="f" class="peer sr-only">
                                        <div class="p-4 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 hover:border-brand-blue dark:hover:border-brand-blue peer-checked:border-brand-orange peer-checked:bg-brand-orange/5 transition-all flex items-center justify-center gap-3 h-full">
                                            <flux:icon.user-circle class="size-5 text-zinc-400 peer-checked:text-brand-orange" />
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300 peer-checked:text-brand-orange">
                                                {{ __('Female') }}
                                            </span>
                                        </div>
                                        <div class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 text-brand-orange transition-opacity">
                                            <flux:icon.check-circle class="size-4" />
                                        </div>
                                    </label>
                                </div>
                            </flux:radio.group>
                        </div>

                        <!-- Right: Date & Time -->
                        <div class="grid grid-cols-2 gap-4 content-start">
                            <flux:input
                                wire:model="birth_date"
                                :label="__('Birth Date')"
                                type="date"
                                icon="calendar"
                                required
                            />

                            <flux:input
                                wire:model="birth_time"
                                :label="__('Birth Time')"
                                type="time"
                                icon="clock"
                                required
                            />
                        </div>
                    </div>

                    <flux:text size="xs" class="text-zinc-500">
                        {{ __('Required for calculation of energetic cycles.') }}
                    </flux:text>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button wire:click="$set('showCreateModal', false)" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Add Member') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- EDIT MODAL -->
    <flux:modal wire:model="showEditModal" class="min-w-[20rem] md:min-w-[40rem]">
        <div class="space-y-6">
            <div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Edit Family Member') }}</h3>
                <p class="text-sm text-zinc-500">{{ __('Update the family member details.') }}</p>
            </div>

            <form wire:submit="updateMember" class="space-y-6">

                <!-- Basic Information -->
                <div class="space-y-4">
                    <h4 class="text-sm font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">{{ __('Basic Information') }}</h4>

                    <flux:input
                        wire:model="name"
                        :label="__('Full Name')"
                        :placeholder="__('e.g. Maria Schmidt')"
                        required
                    />

                    <flux:select
                        wire:model="relationship"
                        :label="__('Relationship')"
                        :placeholder="__('Select relationship...')"
                    >
                        <flux:select.option value="">{{ __('No relationship specified') }}</flux:select.option>
                        @foreach(FamilyMember::getRelationships() as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Birth Data (Required for Gua) -->
                <div class="space-y-4">
                    <h4 class="text-sm font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">{{ __('Birth Data') }}</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left: Birth Place & Gender -->
                        <div class="space-y-4">
                            <flux:input
                                wire:model="birth_place"
                                :label="__('Birth Place')"
                                icon="map-pin"
                                type="text"
                                :placeholder="__('e.g. Hamburg')"
                                required
                            />

                            <flux:radio.group wire:model="gender" :label="__('Biological Sex')" required>
                                <div class="flex gap-4">
                                    <!-- Male Option -->
                                    <label class="flex-1 relative cursor-pointer">
                                        <input type="radio" wire:model="gender" value="m" class="peer sr-only">
                                        <div class="p-4 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 hover:border-brand-blue dark:hover:border-brand-blue peer-checked:border-brand-orange peer-checked:bg-brand-orange/5 transition-all flex items-center justify-center gap-3 h-full">
                                            <flux:icon.user class="size-5 text-zinc-400 peer-checked:text-brand-orange" />
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300 peer-checked:text-brand-orange">
                                                {{ __('Male') }}
                                            </span>
                                        </div>
                                        <div class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 text-brand-orange transition-opacity">
                                            <flux:icon.check-circle class="size-4" />
                                        </div>
                                    </label>

                                    <!-- Female Option -->
                                    <label class="flex-1 relative cursor-pointer">
                                        <input type="radio" wire:model="gender" value="f" class="peer sr-only">
                                        <div class="p-4 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 hover:border-brand-blue dark:hover:border-brand-blue peer-checked:border-brand-orange peer-checked:bg-brand-orange/5 transition-all flex items-center justify-center gap-3 h-full">
                                            <flux:icon.user-circle class="size-5 text-zinc-400 peer-checked:text-brand-orange" />
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300 peer-checked:text-brand-orange">
                                                {{ __('Female') }}
                                            </span>
                                        </div>
                                        <div class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 text-brand-orange transition-opacity">
                                            <flux:icon.check-circle class="size-4" />
                                        </div>
                                    </label>
                                </div>
                            </flux:radio.group>
                        </div>

                        <!-- Right: Date & Time -->
                        <div class="grid grid-cols-2 gap-4 content-start">
                            <flux:input
                                wire:model="birth_date"
                                :label="__('Birth Date')"
                                type="date"
                                icon="calendar"
                                required
                            />

                            <flux:input
                                wire:model="birth_time"
                                :label="__('Birth Time')"
                                type="time"
                                icon="clock"
                                required
                            />
                        </div>
                    </div>

                    <flux:text size="xs" class="text-zinc-500">
                        {{ __('Required for calculation of energetic cycles.') }}
                    </flux:text>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button wire:click="$set('showEditModal', false)" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

</div>
