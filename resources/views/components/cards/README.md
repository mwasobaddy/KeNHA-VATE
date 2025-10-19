# Cards Components

A comprehensive card-based layout system for the KENHAVATE application, providing flexible and reusable components for displaying content in card format.

## Overview

The cards component system mirrors the functionality of the table components but provides a card-based layout alternative. It's designed to work seamlessly with Livewire and follows the same patterns for consistency.

## Available Components

### Core Components

#### `card.blade.php`
The main card container component with hover and selection states.

**Props:**
- `selected` (boolean): Whether the card is selected
- `hoverable` (boolean, default: true): Enable hover effects
- `clickable` (boolean, default: false): Make the card clickable
- `class` (string): Additional CSS classes

**Usage:**
```blade
<x-cards.card :selected="$isSelected" wire:click="selectCard">
    <!-- Card content -->
</x-cards.card>
```

#### `card-grid.blade.php`
Responsive grid container for multiple cards.

**Props:**
- `loading` (boolean): Show loading state
- `empty` (boolean): Show empty state
- `empty-title` (string): Empty state title
- `empty-description` (string): Empty state description
- `empty-action` (string): Empty state action button text
- `empty-action-wire-click` (string): Wire click for empty state action
- `columns` (string, default: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'): Grid column classes
- `gap` (string, default: 'gap-6'): Gap between cards

**Usage:**
```blade
<x-cards.card-grid
    :loading="$loading"
    :empty="!$items->count()"
    empty-title="No items found"
    empty-description="Get started by creating your first item."
    empty-action="Create Item"
    :empty-action-wire-click="'openCreateModal'"
>
    @foreach($items as $item)
        <x-cards.card>
            <!-- Card content -->
        </x-cards.card>
    @endforeach
</x-cards.card-grid>
```

#### `card-header.blade.php`
Card header with title, subtitle, avatar, badge, meta information, and actions.

**Props:**
- `title` (string): Main title
- `subtitle` (string): Subtitle text
- `avatar` (array|string): Avatar image or default icon
- `badge` (array): Badge configuration
- `meta` (array): Meta information items
- `actions` (array): Action buttons
- `class` (string): Additional CSS classes

**Usage:**
```blade
<x-cards.card-header
    title="John Doe"
    subtitle="Software Engineer"
    :avatar="['src' => '/avatar.jpg', 'alt' => 'John Doe']"
    :badge="['text' => 'Active', 'variant' => 'success']"
    :meta="[
        ['icon' => 'mail', 'text' => 'john@example.com'],
        ['icon' => 'phone', 'text' => '+1234567890']
    ]"
    :actions="[
        ['text' => 'Edit', 'variant' => 'ghost', 'wireClick' => 'editUser'],
        ['text' => 'Delete', 'variant' => 'danger', 'wireClick' => 'deleteUser', 'confirm' => 'Are you sure?']
    ]"
/>
```

#### `card-body.blade.php`
Main content area of the card.

**Props:**
- `content` (string): Content HTML (alternative to slot)
- `class` (string): Additional CSS classes

**Usage:**
```blade
<x-cards.card-body>
    <p>Your card content here.</p>
</x-cards.card-body>

{{-- Or with content prop --}}
<x-cards.card-body content="<p>Content from prop</p>" />
```

#### `card-footer.blade.php`
Card footer with meta information and actions.

**Props:**
- `actions` (array): Action buttons
- `meta` (array): Meta information items
- `class` (string): Additional CSS classes

**Usage:**
```blade
<x-cards.card-footer
    :meta="[
        ['icon' => 'calendar', 'text' => 'Created 2 days ago'],
        ['icon' => 'user', 'text' => 'By John Doe']
    ]"
    :actions="[
        ['text' => 'View', 'variant' => 'primary', 'href' => '/view'],
        ['text' => 'Edit', 'variant' => 'secondary', 'wireClick' => 'edit']
    ]"
/>
```

#### `card-stats.blade.php`
Statistics/metrics display component.

**Props:**
- `stats` (array): Array of stat configurations
- `columns` (int, default: 2): Number of columns
- `class` (string): Additional CSS classes

**Stat Configuration:**
```php
[
    'value' => '1,234',           // The main value
    'label' => 'Total Users',     // Description label
    'icon' => 'users',            // Flux icon name
    'change' => '+12%',           // Change indicator
    'changeType' => 'positive',   // positive, negative, neutral
    'variant' => 'primary'        // primary, success, warning, danger
]
```

**Usage:**
```blade
<x-cards.card-stats
    :columns="3"
    :stats="[
        [
            'value' => '1,234',
            'label' => 'Total Users',
            'icon' => 'users',
            'change' => '+12%',
            'changeType' => 'positive',
            'variant' => 'primary'
        ]
    ]"
/>
```

#### `card-actions.blade.php`
Action buttons component (used internally by other components).

**Props:**
- `actions` (array): Array of action configurations
- `justify` (string): Flex justify class
- `class` (string): Additional CSS classes

**Action Configuration:**
```php
[
    'text' => 'Edit',                    // Button text
    'variant' => 'primary',              // primary, secondary, danger, warning, success, ghost
    'size' => 'sm',                      // xs, sm, md, lg
    'wireClick' => 'editItem',           // Livewire click handler
    'href' => '/edit',                   // Link URL
    'confirm' => 'Are you sure?',        // Confirmation message
    'icon' => 'edit',                    // Flux icon name
    'disabled' => false                  // Disable button
]
```

### Shared Components

The following components are shared with the table system:

#### `bulk-actions.blade.php`
Bulk action controls for selected items.

#### `delete-modal.blade.php`
Confirmation modal for delete operations.

#### `empty-state.blade.php`
Empty state display when no items exist.

#### `filters.blade.php`
Filter controls for data filtering.

#### `pagination.blade.php`
Pagination controls for large datasets.

## Action Configuration

Actions support the following properties:

- `text` (string): Button text
- `variant` (string): Color variant (primary, secondary, danger, warning, success, ghost)
- `size` (string): Button size (xs, sm, md, lg)
- `wireClick` (string): Livewire click handler
- `href` (string): Link URL (creates `<a>` tag)
- `confirm` (string): Confirmation message (browser confirm or wire:confirm)
- `icon` (string): Flux icon name
- `disabled` (boolean): Disable the button

## Badge Configuration

Badges support:

- `text` (string): Badge text
- `variant` (string): Color variant (success, warning, danger, default)

## Meta Configuration

Meta items support:

- `text` (string): Display text
- `icon` (string): Flux icon name

## Avatar Configuration

Avatars support:

- `src` (string): Image URL
- `alt` (string): Alt text
- `class` (string): Additional CSS classes

## Complete Example

```blade
{{-- User Management Cards --}}
<x-cards.card-grid
    :loading="$loading"
    :empty="!$users->count()"
    empty-title="No users found"
    empty-description="Get started by adding your first user."
    empty-action="Add User"
    :empty-action-wire-click="'openCreateModal'"
>
    @foreach($users as $user)
        <x-cards.card
            :wire:key="'user-' . $user->id"
            :selected="$selectedUsers->contains($user->id)"
            wire:click="toggleUserSelection({{ $user->id }})"
        >
            <x-cards.card-header
                :title="$user->name"
                :subtitle="$user->email"
                :badge="[
                    'text' => $user->account_status,
                    'variant' => $user->account_status === 'active' ? 'success' : 'warning'
                ]"
                :meta="[
                    ['icon' => 'calendar', 'text' => $user->created_at->format('M j, Y')],
                    ['icon' => 'star', 'text' => $user->points . ' points']
                ]"
                :actions="[
                    ['text' => 'Edit', 'variant' => 'ghost', 'wireClick' => 'editUser(' . $user->id . ')'],
                    ['text' => 'Delete', 'variant' => 'danger', 'wireClick' => 'deleteUser(' . $user->id . ')', 'confirm' => 'Are you sure?']
                ]"
            />

            <x-cards.card-body>
                @if($user->staff)
                    <div class="text-sm text-[#9B9EA4] dark:text-zinc-400">
                        <p><strong>Department:</strong> {{ $user->staff->department->name ?? 'N/A' }}</p>
                        <p><strong>Position:</strong> {{ $user->staff->job_title ?? 'N/A' }}</p>
                    </div>
                @else
                    <p class="text-sm text-[#9B9EA4] dark:text-zinc-400">Regular user account</p>
                @endif
            </x-cards.card-body>

            <x-cards.card-footer
                :actions="[
                    ['text' => 'View Profile', 'variant' => 'primary', 'href' => route('users.show', $user->id)],
                    ['text' => 'Message', 'variant' => 'secondary', 'wireClick' => 'messageUser(' . $user->id . ')']
                ]"
            />
        </x-cards.card>
    @endforeach
</x-cards.card-grid>
```

## Integration with Livewire

The cards components are designed to work seamlessly with Livewire:

```php
class UserManagement extends Component
{
    public $selectedUsers = [];
    public $loading = false;
    public $filters = [];

    public function toggleUserSelection($userId)
    {
        if (in_array($userId, $this->selectedUsers)) {
            $this->selectedUsers = array_diff($this->selectedUsers, [$userId]);
        } else {
            $this->selectedUsers[] = $userId;
        }
    }

    public function bulkDelete()
    {
        // Handle bulk delete
    }

    public function render()
    {
        return view('livewire.user-management', [
            'users' => User::paginate(12)
        ]);
    }
}
```

## Styling

The components use:
- Tailwind CSS for styling
- Flux icons for iconography
- Dark mode support
- Responsive design
- Consistent color scheme with the application

## File Structure

```
resources/views/components/cards/
├── card.blade.php              # Main card component
├── card-grid.blade.php         # Grid container
├── card-header.blade.php       # Card header
├── card-body.blade.php         # Card body
├── card-footer.blade.php       # Card footer
├── card-stats.blade.php        # Statistics display
├── card-actions.blade.php      # Action buttons
├── bulk-actions.blade.php      # Shared from table/
├── delete-modal.blade.php      # Shared from table/
├── empty-state.blade.php       # Shared from table/
├── filters.blade.php           # Shared from table/
├── pagination.blade.php        # Shared from table/
├── examples.blade.php          # Usage examples
└── README.md                   # This documentation
```

## Notes

- All components support dark mode
- Components are fully responsive
- Shared components maintain the same API as table components
- Components use consistent naming and prop patterns
- All components support Livewire integration