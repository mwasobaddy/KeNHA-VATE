<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class DashboardUsersTable extends Component
{
    use WithPagination;

    // Search and filters
    public string $search = '';
    public string $status = '';
    public string $role = '';

    // Updating state trackers
    public bool $updatingSearch = false;
    public bool $updatingStatus = false;
    public bool $updatingRole = false;

    // Sorting
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Bulk actions
    public array $selectedUsers = [];
    public bool $selectAll = false;

    // Per page
    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'role' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->updatingSearch = true;
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->updatingSearch = false;
    }

    public function updatingStatus()
    {
        $this->updatingStatus = true;
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->updatingStatus = false;
    }

    public function updatingRole()
    {
        $this->updatingRole = true;
        $this->resetPage();
    }

    public function updatedRole()
    {
        $this->updatingRole = false;
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedUsers = $this->getUsersQuery()->pluck('id')->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    public function clearSelection()
    {
        $this->selectedUsers = [];
        $this->selectAll = false;
    }

    public function deleteSelected()
    {
        User::whereIn('id', $this->selectedUsers)->delete();
        $this->clearSelection();
        $this->dispatch('showSuccess', 'Selected users deleted successfully.', 'Users Deleted');
    }

    public function exportSelected()
    {
        // For now, just show a success message
        $this->dispatch('showInfo', 'Export functionality will be implemented soon.', 'Coming Soon');
    }

    public function toggleUserStatus($userId)
    {
        $user = User::find($userId);
        if ($user) {
            $user->account_status = $user->account_status === 'active' ? 'inactive' : 'active';
            $user->save();

            $status = $user->account_status === 'active' ? 'activated' : 'deactivated';
            $this->dispatch('showSuccess', "User has been {$status} successfully.", 'Status Updated');
        }
    }

    public function viewUser($userId)
    {
        // For now, just show an info message
        $this->dispatch('showInfo', 'User details view will be implemented soon.', 'Coming Soon');
    }

    public function editUser($userId)
    {
        // For now, just show an info message
        $this->dispatch('showInfo', 'User editing will be implemented soon.', 'Coming Soon');
    }

    public function deleteUser($userId)
    {
        User::find($userId)?->delete();
        $this->dispatch('showSuccess', 'User deleted successfully.', 'User Deleted');
    }

    private function getUsersQuery()
    {
        return User::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('other_names', 'like', '%' . $this->search . '%')
                      ->orWhere('username', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function ($query) {
                $query->where('account_status', $this->status);
            })
            ->orderBy($this->sortField, $this->sortDirection);
    }

    public function getUsersProperty()
    {
        return $this->getUsersQuery()->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.dashboard-users-table', [
            'users' => $this->users,
        ]);
    }
}