# KENHAVATE - GitHub Copilot Instructions

## Project Overview
KENHAVATE is a Laravel 12+ enterprise application for Kenya National Highways Authority (KeNHA) featuring OTP-based authentication, comprehensive user management, role-based access control, and gamification. The system supports both light and dark modes and uses Laravel Flux components.

---

## Technology Stack

### Core Framework
- **Laravel**: 12+ (latest stable)
- **PHP**: 8.3+
- **Database**: MySQL 8.0+ / PostgreSQL 15+
- **Cache**: Redis
- **Queue**: Redis

### Frontend
- **UI Framework**: Laravel Flux components (primary)
- **Styling**: Tailwind CSS 4.x
- **Icons**: Flux icons (preferred), Heroicons (fallback)
- **JavaScript**: Alpine.js (for interactivity)
- **Build Tool**: Vite

### Authentication
- **Primary**: Email + OTP (custom implementation)
- **Secondary**: Laravel Socialite (Google OAuth)
- **Email Verification**: Laravel built-in

### Additional Packages (to be installed)
- `spatie/laravel-permission` - Role and permission management
- `spatie/laravel-activitylog` - Audit logging (optional, or custom implementation)
- `laravel/socialite` - Google OAuth integration
- `pragmarx/google2fa-laravel` - For future 2FA implementation

---

## Code Style & Conventions

### PHP Standards
- Follow PSR-12 coding standards
- Use strict types: `declare(strict_types=1);`
- Type hints for all method parameters and return types
- Use null coalescing operator `??` over ternary when appropriate
- PHP 8.3 features are encouraged (readonly properties, enums, etc.)

### Laravel Conventions
- Use Service Layer pattern for business logic
- Keep controllers thin (max 7 methods recommended)
- Use Form Requests for validation
- Resource classes for API responses
- Eloquent ORM for database interactions
- Use Laravel's built-in features before external packages

### Naming Conventions

**Database:**
- Tables: plural snake_case (e.g., `user_points`, `staff_approvals`)
- Columns: snake_case (e.g., `first_name`, `supervisor_id`)
- Foreign keys: `{singular_table}_id` (e.g., `user_id`, `department_id`)
- Pivot tables: alphabetically ordered (e.g., `role_user`, not `user_role`)
- Indexes: `{table}_{column}_index`
- Unique constraints: `{table}_{column}_unique`

**PHP Classes:**
- Models: Singular PascalCase (e.g., `User`, `Staff`, `Department`)
- Controllers: PascalCase with Controller suffix (e.g., `AuthController`, `StaffController`)
- Services: PascalCase with Service suffix (e.g., `AuthenticationService`, `NotificationService`)
- Requests: PascalCase with Request suffix (e.g., `LoginRequest`, `StoreStaffRequest`)
- Middleware: PascalCase (e.g., `CheckAccountStatus`, `EnsureEmailVerified`)
- Jobs: PascalCase, verb-based (e.g., `SendOtpEmail`, `AwardLoginPoints`)
- Events: PascalCase, past tense (e.g., `UserLoggedIn`, `ProfileCompleted`)
- Listeners: PascalCase, verb-based (e.g., `AwardFirstLoginPoints`, `SendWelcomeNotification`)

**Variables & Methods:**
- Variables: camelCase (e.g., `$userId`, `$accountStatus`)
- Methods: camelCase, verb-based (e.g., `sendOtp()`, `verifyEmail()`, `awardPoints()`)
- Constants: SCREAMING_SNAKE_CASE (e.g., `FIRST_LOGIN_POINTS`, `OTP_EXPIRY_MINUTES`)
- Config keys: snake_case (e.g., `config('kenhavate.otp_length')`)

**Routes:**
- Use kebab-case for URL segments (e.g., `/staff-approvals`, `/terms-conditions`)
- Group related routes with prefixes
- Name routes using dot notation (e.g., `staff.approvals.index`, `auth.otp.verify`)

---

## Architectural Patterns

### Service Layer Pattern
**Purpose**: Encapsulate business logic separate from controllers

**Structure:**
```php
// app/Services/AuthenticationService.php
namespace App\Services;

class AuthenticationService
{
    public function sendOtp(string $email): bool
    {
        // Business logic here
    }
    
    public function verifyOtp(string $email, string $code): bool
    {
        // Business logic here
    }
}
```

**Usage in Controllers:**
```php
public function __construct(
    private readonly AuthenticationService $authService
) {}

public function sendOtp(Request $request)
{
    $this->authService->sendOtp($request->email);
    return response()->json(['message' => 'OTP sent']);
}
```

### Repository Pattern (Optional)
Consider for complex data access, but Eloquent models are acceptable for standard CRUD.

### Action Classes (Optional)
For single-responsibility operations:
```php
// app/Actions/AwardPointsAction.php
class AwardPointsAction
{
    public function execute(User $user, int $points, string $reason): void
    {
        // Point awarding logic
    }
}
```

---

## Security Best Practices

### Authentication & Authorization
- Always validate user permissions before sensitive operations
- Use Laravel's Gate and Policy systems
- Never trust client-side data
- Implement rate limiting on authentication endpoints
- Use signed URLs for sensitive actions

### Data Protection
- Hash passwords with bcrypt (Laravel default)
- Encrypt sensitive data in database using Laravel's encryption
- Use prepared statements (Eloquent handles this)
- Sanitize user input (use validation)
- Implement CSRF protection (enabled by default)

### OTP Security
- Generate cryptographically secure random OTPs
- Set expiration time (5-10 minutes)
- Invalidate OTP after single use
- Rate limit OTP requests (max 3 per hour per email)
- Use constant-time comparison for OTP verification

**Example OTP Generation:**
```php
use Illuminate\Support\Str;

$otp = Str::random(6); // or use random_int(100000, 999999) for numeric
$hashedOtp = Hash::make($otp);
$expiresAt = now()->addMinutes(10);
```

### Account Security
- Log all authentication attempts (success and failure)
- Implement account lockout after 5 failed attempts
- Send email notifications for suspicious activities
- Support password requirements for critical operations

---

## Database Guidelines

### Migrations
- Always include `up()` and `down()` methods
- Use descriptive migration names
- Add indexes for foreign keys and frequently queried columns
- Include comments for complex fields
- Use enums for fixed-value columns

**Example Migration:**
```php
Schema::create('staff', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('first_name');
    $table->string('other_names');
    $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say']);
    $table->string('mobile_phone')->unique();
    $table->string('staff_number')->nullable()->unique();
    $table->foreignId('department_id')->constrained();
    $table->enum('employment_type', ['permanent', 'contract', 'intern'])->nullable();
    $table->foreignId('supervisor_id')->nullable()->constrained('users');
    $table->timestamp('supervisor_approved_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['user_id', 'supervisor_id']);
});
```

### Model Relationships
- Define all relationships explicitly
- Use eager loading to prevent N+1 queries
- Add docblocks for IDE support

**Example Model:**
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'first_name', 'other_names', 'gender',
        'mobile_phone', 'staff_number', 'department_id',
        'employment_type', 'supervisor_id',
    ];

    protected $casts = [
        'supervisor_approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
```

### Query Optimization
- Use `select()` to limit columns
- Eager load relationships: `with()`, `load()`
- Use `chunk()` for large datasets
- Add database indexes for frequently queried columns
- Use query scopes for reusable query logic

---

## Frontend Guidelines

### Blade Components
- Create reusable components in `resources/views/components/`
- Use Flux components as primary UI building blocks
- Support both light and dark mode

**Example Notification Component:**
```blade
<!-- resources/views/components/notification.blade.php -->
@props([
    'type' => 'info', // success, error, info, warning
    'title' => null,
    'message' => null,
    'dismissible' => true,
    'icon' => null
])

<div {{ $attributes->merge(['class' => 'notification notification-' . $type]) }}
     x-data="{ show: true }"
     x-show="show"
     x-transition>
    @if($icon)
        <flux:icon :name="$icon" class="w-5 h-5" />
    @endif
    
    <div>
        @if($title)
            <h4 class="font-semibold">{{ $title }}</h4>
        @endif
        <p>{{ $message ?? $slot }}</p>
    </div>
    
    @if($dismissible)
        <button @click="show = false" class="close-btn">
            <flux:icon name="x" class="w-4 h-4" />
        </button>
    @endif
</div>
```

### Dark Mode Implementation
- Use Tailwind's dark mode classes: `dark:bg-gray-800`
- Store user preference in database or local storage
- Provide toggle component in navigation
- Ensure proper contrast ratios for accessibility

**Tailwind Config:**
```javascript
// tailwind.config.js
export default {
  darkMode: 'class', // Enable dark mode with class strategy
  theme: {
    extend: {
      colors: {
        // Custom color palette for both modes
      }
    }
  }
}
```

### Alpine.js Usage
- Use for simple interactivity (toggles, modals, dropdowns)
- Keep logic minimal; move complex operations to backend
- Initialize components with `x-data`

**Example:**
```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open" x-transition>Content</div>
</div>
```

---

## Testing Guidelines

### Test Structure
- **Feature Tests**: Test HTTP endpoints and user workflows
- **Unit Tests**: Test service methods and helper functions
- **Browser Tests (Dusk)**: Test end-to-end user journeys

### Test Naming
- Use descriptive method names: `test_user_can_login_with_valid_otp()`
- Group related tests with `@group` annotation

### Database Testing
- Use `RefreshDatabase` trait for isolated tests
- Use factories for test data creation
- Use `assertDatabaseHas()` and `assertDatabaseMissing()`

**Example Feature Test:**
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_receives_otp_on_login_attempt(): void
    {
        $user = User::factory()->create(['account_status' => 'active']);

        $response = $this->post(route('auth.send-otp'), [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'OTP sent successfully']);
        // Assert OTP was stored in cache or database
    }

    public function test_banned_user_cannot_login(): void
    {
        $user = User::factory()->create(['account_status' => 'banned']);

        $response = $this->post(route('auth.send-otp'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('account.banned'));
    }
}
```

---

## Error Handling & Logging

### Exception Handling
- Use custom exceptions for domain-specific errors
- Log exceptions appropriately (info, warning, error, critical)
- Return user-friendly error messages

**Custom Exception Example:**
```php
<?php

namespace App\Exceptions;

use Exception;

class InvalidOtpException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'message' => 'Invalid or expired OTP. Please request a new one.',
        ], 422);
    }
}
```

### Logging
- Use Laravel's logging facade: `Log::info()`, `Log::error()`
- Log important events: authentication, profile changes, supervisor approvals
- Include context in logs: user ID, IP address, user agent

**Example:**
```php
use Illuminate\Support\Facades\Log;

Log::info('User logged in', [
    'user_id' => $user->id,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);
```

---

## Notification System

### Implementation Strategy
- Store notifications in database for persistence
- Support in-app and email notifications
- Use Laravel's Notification system

**Notification Service:**
```php
<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\CustomNotification;

class NotificationService
{
    public function notify(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null
    ): void {
        // Store in database
        $user->notifications()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
        ]);

        // Optionally send email
        if ($this->shouldSendEmail($type)) {
            $user->notify(new CustomNotification($title, $message));
        }
    }

    public function markAsRead(int $notificationId): void
    {
        Notification::findOrFail($notificationId)
            ->update(['read_at' => now()]);
    }
}
```

---

## Audit Logging

### What to Log
- Authentication events (login, logout, failed attempts)
- Profile updates
- Permission changes
- Critical operations (account deletion, data exports)
- Supervisor approvals/rejections

### Audit Service
```php
<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    public function log(
        string $eventType,
        ?int $userId = null,
        ?array $metadata = null,
        ?Request $request = null
    ): void {
        $request = $request ?? request();

        AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'event_type' => $eventType,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
```

---

## Points/Gamification System

### Point Service
```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\PointTransaction;

class PointService
{
    public function awardPoints(
        User $user,
        int $points,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {
        // Create transaction record
        PointTransaction::create([
            'user_id' => $user->id,
            'points' => $points,
            'transaction_type' => 'earned',
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);

        // Update user's total points
        $user->increment('points', $points);

        // Send notification
        app(NotificationService::class)->notify(
            $user,
            'success',
            'Points Awarded',
            "You earned {$points} points for {$description}"
        );
    }

    public function getFirstLoginPoints(): int
    {
        return config('kenhavate.points.first_login', 50);
    }

    public function hasReceivedFirstLoginBonus(User $user): bool
    {
        return PointTransaction::where('user_id', $user->id)
            ->where('description', 'First login bonus')
            ->exists();
    }
}
```

---

## Configuration Files

### Create Custom Config: config/kenhavate.php
```php
<?php

return [
    'otp' => [
        'length' => env('OTP_LENGTH', 6),
        'expiry_minutes' => env('OTP_EXPIRY_MINUTES', 10),
        'max_attempts' => env('OTP_MAX_ATTEMPTS', 5),
    ],

    'points' => [
        'first_login' => env('POINTS_FIRST_LOGIN', 50),
        'daily_login' => env('POINTS_DAILY_LOGIN', 10),
    ],

    'rate_limiting' => [
        'login_attempts' => env('LOGIN_MAX_ATTEMPTS', 5),
        'lockout_minutes' => env('LOGIN_LOCKOUT_MINUTES', 15),
    ],

    'email_domains' => [
        'kenha' => ['kenha.co.ke'],
    ],

    'account_status' => [
        'active' => 'active',
        'banned' => 'banned',
        'disabled' => 'disabled',
    ],

    'employment_types' => [
        'permanent' => 'Permanent',
        'contract' => 'Contract',
        'intern' => 'Intern',
        'consultant' => 'Consultant',
    ],

    'gender_options' => [
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other',
        'prefer_not_to_say' => 'Prefer not to say',
    ],
];
```

---

## Route Organization

### Structure Routes by Domain
```php
// routes/web.php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\GoogleAuthController;

// Public Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/send-otp', [OtpController::class, 'send'])->name('auth.otp.send');
    Route::post('/verify-otp', [OtpController::class, 'verify'])->name('auth.otp.verify');
    
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

// Protected Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Profile Setup (first-time users)
    Route::middleware('check.profile.completion')->group(function () {
        Route::get('/profile/setup', [ProfileController::class, 'showSetup'])->name('profile.setup');
        Route::post('/profile/setup', [ProfileController::class, 'completeSetup'])->name('profile.complete');
    });
    
    // Terms and Conditions
    Route::get('/terms-conditions', [TermsController::class, 'show'])->name('terms.show');
    Route::post('/terms-conditions/accept', [TermsController::class, 'accept'])->name('terms.accept');
    
    // Dashboard (requires accepted terms)
    Route::middleware('check.terms.accepted')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
    
    // Staff Management
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/approvals', [StaffApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{staff}/approve', [StaffApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{staff}/reject', [StaffApprovalController::class, 'reject'])->name('approvals.reject');
    });
});

// Account Status Pages
Route::get('/account/banned', [AccountController::class, 'banned'])->name('account.banned');
Route::get('/account/disabled', [AccountController::class, 'disabled'])->name('account.disabled');
Route::post('/account/review-request', [AccountController::class, 'requestReview'])->name('account.review');
```

---

## Middleware Guidelines

### Custom Middleware Examples

**CheckAccountStatus.php:**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAccountStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        return match ($user->account_status) {
            'banned' => redirect()->route('account.banned'),
            'disabled' => redirect()->route('account.disabled'),
            'active' => $next($request),
            default => abort(403, 'Invalid account status'),
        };
    }
}
```

**CheckProfileCompletion.php:**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckProfileCompletion
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user->staff || !$user->staff->isProfileComplete()) {
            return redirect()->route('profile.setup');
        }

        return $next($request);
    }
}
```

**CheckTermsAccepted.php:**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTermsAccepted
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user->terms_accepted_count === 0) {
            return redirect()->route('terms.show');
        }

        return $next($request);
    }
}
```

---

## Validation Rules

### Form Request Examples

**LoginRequest.php:**
```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
        ];
    }
}
```

**CompleteProfileRequest.php:**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $isKenhaEmail = str_ends_with($user->email, '@kenha.co.ke');

        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'other_names' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'gender' => ['required', Rule::in(array_keys(config('kenhavate.gender_options')))],
            'mobile_phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/', 'unique:staff,mobile_phone'],
        ];

        if ($isKenhaEmail) {
            $rules['staff_number'] = ['required', 'string', 'unique:staff,staff_number'];
            $rules['personal_email'] = ['nullable', 'email', 'different:email'];
            $rules['job_title'] = ['required', 'string', 'max:150'];
            $rules['department_id'] = ['required', 'exists:departments,id'];
            $rules['employment_type'] = ['required', Rule::in(array_keys(config('kenhavate.employment_types')))];
        } else {
            $rules['is_kenha_staff'] = ['required', 'boolean'];
            if ($this->boolean('is_kenha_staff')) {
                $rules['employment_type'] = ['required', Rule::in(array_keys(config('kenhavate.employment_types')))];
                $rules['supervisor_email'] = [
                    'required',
                    'email',
                    'different:email',
                    'regex:/@kenha\.co\.ke$/',
                ];
            }
        }

        return $rules;
    }
}
```

---

## Event & Listener Pattern

### Events
```php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public bool $isFirstLogin = false
    ) {}
}

class ProfileCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user) {}
}

class SupervisorApprovalRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $staff,
        public User $supervisor
    ) {}
}
```

### Listeners
```php
<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Services\AuditService;
use App\Services\PointService;

class HandleUserLogin
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly PointService $pointService
    ) {}

    public function handle(UserLoggedIn $event): void
    {
        // Log the event
        $this->auditService->log('user_logged_in', $event->user->id);

        // Award points for first login
        if ($event->isFirstLogin && !$this->pointService->hasReceivedFirstLoginBonus($event->user)) {
            $this->pointService->awardPoints(
                $event->user,
                $this->pointService->getFirstLoginPoints(),
                'First login bonus'
            );
        }
    }
}
```

### Register in EventServiceProvider
```php
protected $listen = [
    UserLoggedIn::class => [
        HandleUserLogin::class,
    ],
    ProfileCompleted::class => [
        SendWelcomeNotification::class,
    ],
    SupervisorApprovalRequested::class => [
        NotifySupervisor::class,
    ],
];
```

---

## Queue & Job Guidelines

### Job Examples

**SendOtpEmail.php:**
```php
<?php

namespace App\Jobs;

use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOtpEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly string $email,
        private readonly string $otp
    ) {}

    public function handle(): void
    {
        Mail::to($this->email)->send(new OtpMail($this->otp));
    }

    public function failed(\Throwable $exception): void
    {
        // Log failure
        \Log::error('Failed to send OTP email', [
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

**Usage:**
```php
SendOtpEmail::dispatch($user->email, $otp)->onQueue('emails');
```

---

## API Responses (If Applicable)

### Resource Classes
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'account_status' => $this->account_status,
            'points' => $this->points,
            'staff' => new StaffResource($this->whenLoaded('staff')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
```

### Consistent Response Format
```php
// Success Response
return response()->json([
    'success' => true,
    'message' => 'Operation completed successfully',
    'data' => $data,
], 200);

// Error Response
return response()->json([
    'success' => false,
    'message' => 'Operation failed',
    'errors' => $errors,
], 422);
```

---

## Performance Optimization

### Database Optimization
- Use `select()` to limit columns
- Eager load relationships to avoid N+1
- Use database indexing
- Implement query caching for static data

### Caching Strategy
```php
use Illuminate\Support\Facades\Cache;

// Cache regions, directorates, departments (rarely change)
$regions = Cache::remember('regions', 3600, function () {
    return Region::with('directorates.departments')->get();
});

// Cache user permissions
$permissions = Cache::remember("user.{$userId}.permissions", 3600, function () use ($userId) {
    return User::find($userId)->getAllPermissions();
});
```

### Queue Long-Running Tasks
- Email sending
- Point calculations
- Report generation
- Bulk operations

---

## Accessibility Requirements

### WCAG 2.1 Level AA Compliance
- Minimum contrast ratio: 4.5:1 for normal text, 3:1 for large text
- All interactive elements keyboard accessible
- Proper ARIA labels and roles
- Form labels associated with inputs
- Skip navigation links
- Focus indicators visible

### Implementation
```blade
<!-- Good accessibility example -->
<label for="email" class="block text-sm font-medium">
    Email Address <span class="text-red-500">*</span>
</label>
<input
    type="email"
    id="email"
    name="email"
    aria-required="true"
    aria-describedby="email-error"
    class="form-input focus:ring-2 focus:ring-blue-500"
/>
@error('email')
    <p id="email-error" class="text-red-500 text-sm mt-1" role="alert">
        {{ $message }}
    </p>
@enderror
```

---

## Documentation Standards

### Code Comments
- Use PHPDoc blocks for classes and methods
- Explain "why" not "what" in inline comments
- Document complex algorithms
- Keep comments up-to-date

**Example:**
```php
/**
 * Send OTP to user's email address.
 *
 * This method generates a secure OTP, stores it in cache with expiration,
 * and dispatches an email job to send it to the user.
 *
 * @param string $email The user's email address
 * @return bool True if OTP was sent successfully
 * @throws \App\Exceptions\RateLimitExceededException
 */
public function sendOtp(string $email): bool
{
    // Implementation
}
```

### README Files
- Include setup instructions
- Document environment variables
- List dependencies and versions
- Provide examples for common operations

---

## Environment Variables

### Required .env Variables
```env
# Application
APP_NAME=KENHAVATE
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kenhavate
DB_USERNAME=root
DB_PASSWORD=

# Cache & Queue
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@kenha.co.ke
MAIL_FROM_NAME="${APP_NAME}"

# Google OAuth
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# OTP Configuration
OTP_LENGTH=6
OTP_EXPIRY_MINUTES=10
OTP_MAX_ATTEMPTS=5

# Rate Limiting
LOGIN_MAX_ATTEMPTS=5
LOGIN_LOCKOUT_MINUTES=15

# Points System
POINTS_FIRST_LOGIN=50
POINTS_DAILY_LOGIN=10
```

---

## Git Workflow

### Branch Naming
- `feature/short-description` - New features
- `bugfix/issue-number-description` - Bug fixes
- `hotfix/critical-issue` - Production hotfixes
- `refactor/component-name` - Code refactoring

### Commit Messages
Follow conventional commits format:
```
type(scope): subject

body (optional)

footer (optional)
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples:**
```
feat(auth): implement OTP verification
fix(staff): resolve supervisor approval email bug
docs(readme): update installation instructions
```

---

## Common Pitfalls to Avoid

### Security
- ❌ Never store OTPs in plain text
- ❌ Don't trust client-side validation alone
- ❌ Avoid exposing sensitive data in API responses
- ❌ Don't use predictable IDs for sensitive resources

### Performance
- ❌ Avoid N+1 queries (use eager loading)
- ❌ Don't query in loops
- ❌ Avoid loading entire collections when you need only a few fields
- ❌ Don't forget to index foreign keys

### Code Quality
- ❌ Don't put business logic in controllers
- ❌ Avoid fat models (use services)
- ❌ Don't repeat yourself (DRY principle)
- ❌ Avoid magic numbers (use constants or config)

---

## Code Review Checklist

### Before Submitting PR
- [ ] All tests pass
- [ ] Code follows PSR-12 standards
- [ ] No sensitive data in commits
- [ ] Documentation updated
- [ ] Migrations are reversible
- [ ] No console errors in browser
- [ ] Responsive design tested
- [ ] Dark mode works correctly
- [ ] Accessibility checked
- [ ] Security vulnerabilities addressed

---

## Resources & References

### Official Documentation
- Laravel 12: https://laravel.com/docs/12.x
- Laravel Flux: https://flux.laravel.com
- Tailwind CSS: https://tailwindcss.com
- Alpine.js: https://alpinejs.dev

### Packages
- Spatie Permission: https://spatie.be/docs/laravel-permission
- Laravel Socialite: https://laravel.com/docs/socialite

### Best Practices
- Laravel Best Practices: https://github.com/alexeymezenin/laravel-best-practices
- PHP The Right Way: https://phptherightway.com

---

## Copilot Usage Tips

### When Working with Copilot

1. **Be Specific**: Provide context about what you're building
   - Good: "Create a service method to send OTP email with 10-minute expiration"
   - Bad: "Make email function"

2. **Use Comments**: Write descriptive comments before code blocks
   ```php
   // Generate a 6-digit OTP, hash it, and store in cache for 10 minutes
   ```

3. **Request Patterns**: Ask for specific architectural patterns
   - "Create a service class following the service layer pattern"
   - "Generate a form request with validation for staff registration"

4. **Iterate**: Review and refine Copilot suggestions
   - Check for security issues
   - Verify business logic
   - Ensure code style consistency

5. **Context Files**: Keep related files open for better suggestions
   - Open related models, controllers, and services
   - Keep configuration files visible

---

## Module-Specific Reminders

### Module 1: Authentication & User Management

#### 👥 User Categories Now Handled Correctly
**Category 1: Regular Users (Not Staff)**  
✅ Requirements: username, first_name, other_names, gender, mobile_phone, email  
✅ Database: Only users table populated  
✅ Middleware: Allows access without staff records  
✅ Profile Complete: When basic info is filled  

**Category 2: KeNHA Staff (@kenha.co.ke email)**  
✅ Requirements: Basic user info + staff_number, job_title, department_id  
✅ Database: Both users and staff tables populated  
✅ Supervisor: Not required (permanent staff)  
✅ Profile Complete: When department and job_title are set  

**Category 3: Other KeNHA Staff (Non-KeNHA email)**  
✅ Requirements: Basic user info + job_title, department_id, employment_type, supervisor_email  
✅ Database: Both users and staff tables populated  
✅ Supervisor: Required and must be approved  
✅ Profile Complete: When all fields including supervisor are set  

#### Additional Reminders
- Always check account status before sending OTP
- Implement rate limiting on all auth endpoints
- Log all authentication attempts
- Award points only once per session
- Verify supervisor email domain before sending approval requests
- Handle edge cases (expired OTP, simultaneous logins, etc.)
- Ensure terms acceptance is tracked properly
- Test both KeNHA staff and non-KeNHA staff flows

---

## Future Modules Preparation

As the system grows, we'll extend these instructions to cover:
- Module 2: Dashboard & Analytics
- Module 3: Content Management
- Module 4: Reporting System
- Module 5: API Development
- Module 6: Mobile App Integration

Each module will build upon this foundation while maintaining consistency in:
- Code style and architecture
- Security practices
- Testing approach
- Documentation standards

---

## Version Control

**Current Version**: 1.0.0 (Module 1)
**Last Updated**: October 2025
**Maintained By**: KENHAVATE Development Team

---

## Questions or Improvements?

If you encounter scenarios not covered in these instructions or have suggestions for improvements, document them and they will be incorporated in future updates.