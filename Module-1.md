# KENHAVATE - Module 1: Authentication & User Management

## Overview
KENHAVATE is an OTP-based authentication system built on Laravel 12+ with support for both email/OTP and Google OAuth authentication. The system includes comprehensive user management, role-based access control, and an integrated gamification system.

---

## 1. Authentication System

### 1.1 Authentication Methods
- **Email + OTP Authentication** (Primary)
- **Google OAuth Authentication** (Secondary)
- **Account Merging**: Users can link both authentication methods to a single account

### 1.2 Unified Login/Register Form
A single form handles both login and registration flows:

#### First-Time Users (Registration Flow)
1. User enters email address
2. System checks if email exists in database
3. If new user:
   - Send OTP to email
   - Verify OTP
   - Redirect to profile completion form
   - Set account status to `active`
   - Award 50 points for first login

#### Returning Users (Login Flow)
1. User enters email address
2. System checks account status:
   - **Active**: Send OTP → Verify → Terms & Conditions → Dashboard
   - **Banned**: Redirect to banned page with support contact
   - **Disabled**: Redirect to disabled page with account review request
3. Award points for successful login (once per session)
4. Log activity in audit table

### 1.3 Rate Limiting
- Maximum 5 login attempts per email address
- Implement exponential backoff or temporary lockout after failed attempts
- Display clear error messages indicating remaining attempts

---

## 2. User Profile Management

### 2.1 Required Fields (All Users)
- **Email** (primary identifier, verified via Laravel's built-in email verification)
- **First Name**
- **Other Names** (Middle/Last name)
- **Password** (for critical operations like data deletion)
- **Gender**
- **Mobile Phone** (with validation)

### 2.2 KeNHA Staff Users (@kenha.co.ke email)
When email domain is `@kenha.co.ke`, collect additional fields:

- **Staff Number** (unique identifier)
- **Personal Email** (requires separate verification before database commit)
- **Job Title/Designation**
- **Department** (linked to directorate)
- **Directorate** (linked to region)
- **Region**
- **Employment Type** (e.g., Permanent, Contract, Intern)

### 2.3 Non-@kenha.co.ke KeNHA Staff
For users claiming to be KeNHA staff without official email:

**Form Fields:**
- Standard user fields (see 2.1)
- **Employment Type**
- **Supervisor's Email** (must be @kenha.co.ke)

**Verification Workflow:**
1. User submits registration with supervisor email
2. System checks if supervisor exists in database
3. Send notifications:
   - **If supervisor exists**: In-app notification + email notification
   - **If supervisor doesn't exist**: Email notification only
4. Supervisor receives approval request with user details
5. Supervisor can:
   - **Accept**: User account activated, granted staff permissions
   - **Reject**: User notified, account remains basic user status
6. Until approved, user has limited access (basic user role only)

---

## 3. Organizational Hierarchy

### 3.1 Structure
```
Region
  └── Directorate
       └── Department
            └── User
```

### 3.2 Relationships
- Every user is linked to a **Department**
- Each Department belongs to a **Directorate**
- Each Directorate belongs to a **Region**

### 3.3 Database Considerations
- Ensure referential integrity with foreign keys
- Create migration order: Regions → Directorates → Departments → Users
- Consider soft deletes for organizational units

---

## 4. Terms and Conditions

### 4.1 Flow
After profile completion, redirect users to Terms & Conditions page.

### 4.2 Acceptance Tracking
- **Accept**: Increment acceptance counter in database → Redirect to dashboard
- **Decline**: Log user out immediately
- Store timestamp of each acceptance for compliance tracking

### 4.3 Database Fields
- `terms_accepted_count` (integer)
- `last_terms_accepted_at` (timestamp)
- `current_terms_version` (string) - for future T&C updates

---

## 5. Gamification & Points System

### 5.1 Point Awards
- **First Login**: 50 points (one-time bonus)
- **Subsequent Logins**: Configurable points (once per session)

### 5.2 Implementation
- Create `user_points` table or column in users table
- Create `point_transactions` table for audit trail
- Award module should be service-based for reusability

### 5.3 Future Considerations
- Point redemption system
- Leaderboards
- Achievement badges
- Point expiration policies

---

## 6. Notification System

### 6.1 Types
- **In-App Notifications** (persistent, stored in database)
- **Email Notifications** (for external communications)

### 6.2 Categories
- **Success** messages (green theme)
- **Error** messages (red theme)
- **Info** messages (blue theme)
- **Warning** messages (yellow theme)

### 6.3 Requirements
- Create reusable notification Blade component
- Support for Flux icons (Laravel 12)
- Persistent storage in `notifications` table
- Mark as read/unread functionality
- Auto-dismiss option for temporary messages
- Dark mode and light mode support

### 6.4 Integration Points
- Login/logout events
- Profile updates
- Supervisor approval requests
- System announcements
- Point awards

---

## 7. Database Schema

### 7.1 Users Table (Authentication)
```
- id (primary key)
- email (unique, indexed)
- email_verified_at
- google_id (nullable, for OAuth)
- account_status (enum: active, banned, disabled)
- terms_accepted_count (default: 0)
- last_terms_accepted_at (nullable)
- current_terms_version (nullable)
- points (default: 0)
- remember_token
- timestamps
- soft_deletes
```

### 7.2 Staff Table (Extended Profile)
```
- id (primary key)
- user_id (foreign key → users.id)
- first_name
- other_names
- password_hash (for critical operations)
- gender (enum)
- mobile_phone
- staff_number (nullable, unique for KeNHA staff)
- personal_email (nullable, for non-@kenha staff)
- personal_email_verified_at (nullable)
- job_title (nullable)
- department_id (foreign key → departments.id)
- employment_type (nullable)
- supervisor_id (nullable, foreign key → users.id)
- supervisor_approved_at (nullable)
- timestamps
- soft_deletes
```

### 7.3 Organizational Tables
**Regions:**
```
- id
- name
- code (unique)
- is_active (boolean)
- timestamps
```

**Directorates:**
```
- id
- region_id (foreign key)
- name
- code (unique)
- is_active (boolean)
- timestamps
```

**Departments:**
```
- id
- directorate_id (foreign key)
- name
- code (unique)
- is_active (boolean)
- timestamps
```

### 7.4 Audit Log Table
```
- id
- user_id (foreign key, nullable)
- event_type (string: login, logout, profile_update, etc.)
- ip_address
- user_agent
- metadata (json)
- timestamps
```

### 7.5 Notifications Table
```
- id
- user_id (foreign key)
- type (string)
- title
- message (text)
- action_url (nullable)
- read_at (nullable)
- timestamps
```

### 7.6 Point Transactions Table
```
- id
- user_id (foreign key)
- points
- transaction_type (enum: earned, redeemed, adjusted)
- description
- reference_type (nullable, polymorphic)
- reference_id (nullable, polymorphic)
- timestamps
```

---

## 8. Role-Based Access Control (RBAC)

### 8.1 Default Roles
- **User** (default role for all new accounts)
- Additional roles to be defined in future modules

### 8.2 Permissions Structure
Route-based permissions for:
- **Users** (view, create, edit, delete, manage)
- **Staff** (view, create, edit, delete, approve, manage)
- **Regions** (view, create, edit, delete, manage)
- **Directorates** (view, create, edit, delete, manage)
- **Departments** (view, create, edit, delete, manage)
- **Roles** (view, create, edit, delete, assign, manage)

### 8.3 Implementation
- Use Laravel's built-in Gate or Policy system
- Consider Spatie Laravel Permission package for robust RBAC
- Apply middleware to route groups for access control

---

## 9. UI/UX Requirements

### 9.1 Theme Support
- **Dark Mode** (default preference detection)
- **Light Mode**
- Theme toggle component in navigation
- Persist user preference in database or local storage

### 9.2 Responsive Design
- Mobile-first approach
- Support for tablets and desktops
- Touch-friendly interactive elements

### 9.3 Accessibility
- WCAG 2.1 Level AA compliance
- Proper color contrast ratios for both themes
- Keyboard navigation support
- Screen reader compatibility

---

## 10. Service Layer Architecture

### 10.1 Recommended Services
- [x] **AuthenticationService**: Handle OTP generation, verification, Google OAuth
- [x] **NotificationService**: Centralized notification dispatching
- [x] **AuditService**: Log user activities and system events
- [x] **PointService**: Manage point awards and transactions
- [x] **UserService**: User CRUD and profile management
- [x] **StaffService**: Staff-specific operations and supervisor approvals

### 10.2 Benefits
- Single responsibility principle
- Reusable business logic
- Easier testing and maintenance
- Cleaner controllers

---

## 11. Security Considerations

### 11.1 Data Protection
- Hash critical operation passwords with bcrypt
- Encrypt sensitive personal information
- Use HTTPS for all communications
- Implement CSRF protection (Laravel default)

### 11.2 Email Verification
- Leverage Laravel 12's built-in email verification
- Verify personal emails separately for non-@kenha staff
- Use signed URLs for verification links

### 11.3 OTP Security
- OTP expiration time (5-10 minutes recommended)
- Single-use OTPs (invalidate after verification)
- Secure random generation
- Rate limiting on OTP requests

### 11.4 Account Security
- Password requirements for critical operations
- Account lockout after multiple failed attempts
- Audit trail for all security events
- Two-factor authentication readiness

---

## 12. Error Handling & User Feedback

### 12.1 User-Facing Errors
- Banned Account: "Your account has been suspended. Please contact support at [email]"
- Disabled Account: "Your account is currently disabled. Click here to request an account review."
- Failed Login: "Invalid credentials. You have X attempts remaining."
- Rate Limited: "Too many login attempts. Please try again in X minutes."

### 12.2 Validation Messages
- Clear, actionable error messages
- Field-specific validation feedback
- Highlight invalid fields in forms

---

## 13. Testing Strategy

### 13.1 Unit Tests
- Service layer methods
- Helper functions
- Model relationships

### 13.2 Feature Tests
- Authentication flows
- Registration flows
- Profile completion
- Supervisor approval workflow
- Point awarding system

### 13.3 Browser Tests (Dusk)
- End-to-end user journeys
- Form submissions
- Multi-step processes

---

## 14. Additional Overlooked Considerations

### 14.1 Email Verification
- What happens if verification email expires?
- Resend verification email functionality
- Handle bounced emails

### 14.2 Data Validation
- Phone number format validation (international format support)
- Staff number format validation
- Email domain whitelist for @kenha.co.ke

### 14.3 Concurrency
- Handle simultaneous supervisor approvals
- Prevent duplicate point awards
- Lock mechanisms for critical updates

### 14.4 User Experience
- Loading states for async operations
- Progress indicators for multi-step registration
- Confirmation dialogs for critical actions

### 14.5 Maintenance & Monitoring
- Failed login monitoring and alerts
- OTP delivery monitoring
- User activity dashboards
- System health checks

### 14.6 Data Migration
- Plan for existing user data import
- Staff data synchronization with HR systems
- Organizational hierarchy updates

### 14.7 Internationalization (Future)
- Language selection (English/Swahili)
- Localized date/time formats
- Currency formatting if needed

---

## 15. Implementation Phases

### Phase 1: Core Authentication
- [x] Database migrations
- [ ] Basic login/register form (Volt components)
- [ ] OTP system
- [ ] Email verification
- [ ] Rate limiting

### Phase 2: Profile Management
- [x] Profile completion backend
- [x] Staff verification workflow
- [x] Organizational hierarchy setup
- [ ] Profile completion UI (Volt components)
- [ ] Organizational hierarchy UI (Volt components)

### Phase 3: Authorization & Security
- [x] Role-based access control
- [x] Permissions system
- [ ] Account status management
- [ ] Controllers (if needed for Volt components)

### Phase 4: Gamification & Notifications
- [x] Points system
- [x] Notification framework
- [ ] Terms & Conditions flow (Volt components)
- [ ] Notification UI components

### Phase 5: Polish & Testing
- [ ] Dark/light mode implementation
- [ ] Comprehensive testing
- [ ] Performance optimization
- [ ] Documentation

---

## 17. Implementation Status

### ✅ **Completed (Backend Foundation)**
- **Database Schema**: All tables created with proper relationships and constraints
- **Models**: Eloquent models with relationships, scopes, and business logic
- **Services**: Complete service layer following architectural patterns
- **Events & Listeners**: Event-driven architecture for gamification and notifications
- **Configuration**: Comprehensive config file with all required settings
- **Architecture**: Service layer pattern, PSR-12 compliance, strict typing

### 🔄 **Next Phase: Frontend Development**
- **Volt Components**: Login/register forms, profile completion, terms acceptance
- **Notification System**: UI components for in-app notifications
- **Dashboard**: User dashboard with points display and recent activity
- **Supervisor Interface**: Approval workflow for staff applications

### 📋 **Architecture Decisions**
- **Volt Components**: Primary UI framework (no traditional controllers needed)
- **Service Layer**: All business logic encapsulated in services
- **Event-Driven**: Gamification and notifications use Laravel events
- **Soft Deletes**: Implemented on critical tables for data integrity
- **Audit Trail**: Comprehensive logging of all user activities

---

1. ✅ Review and approve this specification
2. ✅ Set up development environment
3. ✅ Create database migrations
4. ✅ Implement core authentication (backend)
5. ✅ Build service layer
6. [ ] Develop UI components (Volt components)
7. ✅ Integrate notification system (backend)
8. [ ] Implement testing suite
9. [ ] Deploy to staging environment
10. [ ] User acceptance testing

**Controller Decision**: Since we're using Volt components for the frontend, traditional controllers may not be necessary. Volt components can handle the logic directly by calling services. Controllers will only be created if needed for complex routing or API endpoints.

---

## Appendix: Questions to Resolve

1. Should supervisor approval be required immediately or can users have limited access pending approval?
2. What's the point-to-reward conversion ratio for the gamification system?
3. Are there specific email templates designed for notifications?
4. What's the policy for updating Terms & Conditions? Do users need to re-accept?
5. Should there be an admin dashboard for managing bans/disables?
6. What's the data retention policy for audit logs?
7. Are there integration requirements with existing KeNHA systems?