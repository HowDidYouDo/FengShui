<?php

namespace App\Livewire\Modules\Crm;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    // Table State
    public string $search = '';
    public string $genderFilter = '';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    // Modal State
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public ?int $editingCustomerId = null;

    // Form Fields
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    #[Validate('required|date')]
    public string $birth_date = '';

    #[Validate('required|date_format:H:i')]
    public string $birth_time = '';

    #[Validate('required|string|max:255')]
    public string $birth_place = '';

    #[Validate('required|in:m,f')]
    public string $gender = 'm';

    #[Validate('nullable|string|max:255')]
    public string $billing_street = '';

    #[Validate('nullable|string|max:20')]
    public string $billing_zip = '';

    #[Validate('nullable|string|max:255')]
    public string $billing_city = '';

    #[Validate('nullable|string|max:255')]
    public string $billing_country = '';

    #[Validate('boolean')]
    public bool $is_self_profile = false;

    // Reset pagination when search/filter changes
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGenderFilter(): void
    {
        $this->resetPage();
    }

    // Sorting
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    // Reset Filters
    public function resetFilters(): void
    {
        $this->reset(['search', 'genderFilter']);
        $this->resetPage();
    }

    // Create Customer
    public function createCustomer(): void
    {
        $this->validate();

        $user = Auth::user();

        // Quota Check
        if (! $user->hasAvailableQuota('crm', $user->customers()->count())) {
            $this->dispatch('notify', message: __('Quota exceeded! Please purchase additional licenses in the shop.'), type: 'error');
            return;
        }

        // Check for existing self profile if trying to create one
        if ($this->is_self_profile && $user->customers()->where('is_self_profile', true)->exists()) {
            $this->addError('is_self_profile', __('You already have a self profile.'));
            return;
        }

        $user->customers()->create([
            'name' => $this->name,
            'email' => $this->email,
            'notes' => $this->notes,
            'birth_date' => $this->birth_date,
            'birth_time' => $this->birth_time,
            'birth_place' => $this->birth_place,
            'gender' => $this->gender,
            'billing_street' => $this->billing_street,
            'billing_zip' => $this->billing_zip,
            'billing_city' => $this->billing_city,
            'billing_country' => $this->billing_country,
            'is_self_profile' => $this->is_self_profile,
        ]);

        $this->resetForm();
        $this->showCreateModal = false;

        // Flux Toast Notification (optional, wenn Du Toast-System hast)
        $this->dispatch('notify', message: __('Client created successfully!'));
    }

    // Edit Customer
    public function editCustomer(int $customerId): void
    {
        $customer = Auth::user()->customers()->findOrFail($customerId);

        // Security: Doppelte Prüfung
        if ($customer->user_id !== Auth::id()) {
            abort(403);
        }

        $this->editingCustomerId = $customer->id;
        $this->name = $customer->name;
        $this->email = $customer->email ?? '';
        $this->notes = $customer->notes ?? '';
        $this->birth_date = $customer->birth_date?->format('Y-m-d') ?? '';
        $this->birth_time = $customer->birth_time ? \Carbon\Carbon::parse($customer->birth_time)->format('H:i') : '';
        $this->birth_place = $customer->birth_place ?? '';
        $this->gender = $customer->gender ?? 'm';
        $this->billing_street = $customer->billing_street ?? '';
        $this->billing_zip = $customer->billing_zip ?? '';
        $this->billing_city = $customer->billing_city ?? '';
        $this->billing_country = $customer->billing_country ?? '';
        $this->is_self_profile = $customer->is_self_profile ?? false;

        $this->showEditModal = true;
    }

    // Update Customer
    public function updateCustomer(): void
    {
        $this->validate();

        $customer = Auth::user()->customers()->findOrFail($this->editingCustomerId);

        // Security: Doppelte Prüfung
        if ($customer->user_id !== Auth::id()) {
            abort(403);
        }

        // Check for existing self profile if trying to set this one as self
        if ($this->is_self_profile && ! $customer->is_self_profile && Auth::user()->customers()->where('is_self_profile', true)->exists()) {
            $this->addError('is_self_profile', __('You already have a self profile.'));
            return;
        }

        $customer->update([
            'name' => $this->name,
            'email' => $this->email,
            'notes' => $this->notes,
            'birth_date' => $this->birth_date,
            'birth_time' => $this->birth_time,
            'birth_place' => $this->birth_place,
            'gender' => $this->gender,
            'billing_street' => $this->billing_street,
            'billing_zip' => $this->billing_zip,
            'billing_city' => $this->billing_city,
            'billing_country' => $this->billing_country,
            'is_self_profile' => $this->is_self_profile,
        ]);

        $this->resetForm();
        $this->showEditModal = false;

        $this->dispatch('notify', message: __('Client updated successfully!'));
    }

    // Delete Customer
    public function deleteCustomer(int $customerId): void
    {
        $customer = Auth::user()->customers()->findOrFail($customerId);

        // Security: Doppelte Prüfung
        if ($customer->user_id !== Auth::id()) {
            abort(403);
        }

        // Prevent deletion of self profile
        if ($customer->is_self_profile) {
            $this->dispatch('notify', message: __('Cannot delete self profile!'));
            return;
        }

        $customer->delete();

        $this->dispatch('notify', message: __('Client deleted successfully!'));
    }

    // Close Edit Modal
    public function closeEditModal(): void
    {
        $this->resetForm();
        $this->showEditModal = false;
    }

    // Reset Form
    private function resetForm(): void
    {
        $this->reset([
            'editingCustomerId',
            'name',
            'email',
            'notes',
            'birth_date',
            'birth_time',
            'birth_place',
            'gender',
            'billing_street',
            'billing_zip',
            'billing_city',
            'billing_country',
            'is_self_profile',
        ]);

        $this->gender = 'm'; // Reset to default
    }

    // Render
    public function render(): View
    {
        $query = Auth::user()->customers()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->genderFilter, function ($query) {
                $query->where('gender', $this->genderFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.modules.crm.index', [
            'customers' => $query->paginate(10),
        ])->layout('components.layouts.app', ['title' => __('Clients')]);
    }
}
