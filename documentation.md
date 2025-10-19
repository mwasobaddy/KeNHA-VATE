# KENHA-VATE Documentation

## Project Overview

**KENHA-VATE** is a comprehensive enterprise application for the Kenya National Highways Authority (KeNHA) featuring OTP-based authentication, comprehensive user management, role-based access control, and gamification. The system supports both light and dark modes and uses Laravel Flux components for modern UI/UX.

### Key Features
- 🔐 **OTP-based Authentication** with email verification
- 👥 **Multi-category User Management** (Regular users, KeNHA staff, external collaborators)
- 📝 **Ideas Submission & Collaboration** platform
- 💬 **Interactive Comments & Likes** system
- 🎯 **Gamification** with points and achievements
- 📊 **Dashboard Analytics** and reporting
- 🎨 **Modern UI** with dark/light mode support
- 📱 **Responsive Design** for all devices

### Technology Stack
- **Backend:** Laravel 12+ with PHP 8.3+
- **Frontend:** Laravel Flux components, Alpine.js, Tailwind CSS 4.x
- **Database:** MySQL 8.0+ / PostgreSQL 15+
- **Cache/Queue:** Redis
- **Authentication:** Custom OTP + Laravel Socialite (Google OAuth)

---

## User Journey Flow

### 1. Landing & Authentication

#### First-Time User Experience
```
User visits KENHA-VATE
    ↓
Email Submission → OTP Generation → Email Delivery
    ↓
OTP Verification → Account Creation → Terms Acceptance
    ↓
Profile Setup (Category-based) → Supervisor Approval (if required)
    ↓
Dashboard Access → Full Platform Features
```

#### Returning User Experience
```
User visits KENHA-VATE
    ↓
Email Submission → OTP Generation → Email Delivery
    ↓
OTP Verification → Dashboard Access
```

### 2. Authentication System

#### Primary Authentication: Email + OTP
- **Email Validation:** Accepts any valid email format
- **OTP Generation:** 6-digit cryptographically secure codes
- **Expiration:** 10 minutes from generation
- **Rate Limiting:** Maximum 5 attempts per hour per email
- **Security:** One-time use, invalidated after successful verification

#### Secondary Authentication: Google OAuth
- **Provider:** Google OAuth 2.0
- **Scope:** Basic profile information (email, name)
- **Integration:** Laravel Socialite
- **Fallback:** Available when OTP fails or for convenience

#### Account Security Features
- **Lockout Protection:** 5 failed attempts = 15-minute lockout
- **Audit Logging:** All authentication attempts logged
- **Session Management:** Secure session handling with CSRF protection
- **Password Requirements:** For accounts requiring additional security

### 3. Terms & Conditions Acceptance

#### Mandatory Acceptance Flow
- **Trigger:** Required after first successful login
- **Content:** Comprehensive terms covering usage, data privacy, intellectual property
- **Acceptance Method:** Explicit checkbox + submit button
- **Tracking:** Stored in database with timestamp
- **Enforcement:** Blocks access to main features until accepted

#### Terms Features
- **Version Control:** Terms version tracking for updates
- **Re-acceptance:** Required when terms are updated
- **Legal Compliance:** Covers data protection, user rights, platform usage
- **Accessibility:** Clear language, proper formatting

### 4. Profile Setup & User Categories

#### Three User Categories

##### Category 1: Regular Users (Non-KeNHA)
**Requirements:**
- Basic Information: First name, other names, gender, mobile phone, email
- **No Staff Record:** Only users table populated
- **Access Level:** Limited to public ideas viewing and basic interaction
- **Supervisor Approval:** Not required

##### Category 2: KeNHA Staff (@kenha.co.ke emails)
**Requirements:**
- Basic user information (as above)
- **Staff-specific:** Staff number, job title, department
- **Database:** Both users and staff tables populated
- **Supervisor:** Not required (permanent staff status)
- **Profile Complete:** When department and job title are set

##### Category 3: Other KeNHA Staff (Non-KeNHA emails)
**Requirements:**
- Basic user information (as above)
- **Staff-specific:** Job title, department, employment type
- **Supervisor:** Required - must provide supervisor email for approval
- **Approval Process:** Supervisor receives notification and must approve
- **Database:** Both users and staff tables populated
- **Profile Complete:** When all fields including approved supervisor are set

#### Profile Setup Process
```
Email Domain Check
    ↓
Category Determination
    ↓
Dynamic Form Generation
    ↓
Required Field Validation
    ↓
Supervisor Approval (if Category 3)
    ↓
Profile Completion Status Update
    ↓
Full Platform Access Granted
```

### 5. Dashboard & Main Features

#### Dashboard Overview
- **Welcome Message:** Personalized greeting with user name
- **Quick Stats:** Ideas count, comments count, points earned
- **Recent Activity:** Latest ideas, comments, approvals
- **Navigation:** Easy access to all platform features

#### Main Navigation Areas
- **My Ideas:** Personal idea submissions and drafts
- **Public Ideas:** Collaborative ideas open for contribution
- **Comments:** All user comments and interactions
- **Profile:** Account settings and preferences
- **Admin Panel:** (For administrators) User management, approvals

### 6. Ideas Management System

#### Idea Creation Process
```
Idea Submission Form
    ↓
Field Validation (Client + Server)
    ↓
File Upload Handling (PDF/Documents)
    ↓
Database Storage with Relationships
    ↓
Status Assignment (Draft/Submitted)
    ↓
Notification System Triggers
    ↓
Success Confirmation + Next Steps
```

#### Idea Components

##### Core Idea Fields
- **Title:** Clear, descriptive title (max 255 chars)
- **Abstract:** Brief summary (max 500 chars)
- **Problem Statement:** Detailed problem description
- **Proposed Solution:** Comprehensive solution description
- **Thematic Area:** Categorized under specific focus areas
- **Collaboration Settings:** Open/closed for collaboration
- **Attachments:** PDF documents, supporting materials

##### Advanced Features
- **Team Effort:** Multiple contributors support
- **Cost-Benefit Analysis:** Financial projections
- **Implementation Timeline:** Project phases and milestones
- **Risk Assessment:** Potential challenges and mitigations
- **Conflict of Interest:** Transparency declarations

#### Idea Status Workflow
```
Draft → Submitted → Under Review → Approved/Rejected
    ↑         ↓            ↓            ↓
    └───── Edit ────── Feedback ──── Final ─────┘
```

#### Public Ideas Collaboration
- **Viewing:** Grid layout with filtering and sorting
- **Interaction:** Like/unlike functionality
- **Comments:** Threaded discussion system
- **Filtering:** By thematic area, status, date, popularity
- **Search:** Full-text search across titles and content

### 7. Comments & Interaction System

#### Comment Architecture
- **Threaded Comments:** Parent-child relationship support
- **Rich Text:** Formatted comment content
- **Likes System:** User engagement tracking
- **Moderation:** Admin controls for inappropriate content
- **Notifications:** Real-time updates for interactions

#### Comment Features
- **Real-time Updates:** Livewire-powered dynamic updates
- **Like Functionality:** Database-driven like/unlike with counts
- **Reply System:** Nested comment threads
- **Edit/Delete:** Author controls with time limits
- **Mention System:** @username notifications (future feature)

### 8. Gamification & Points System

#### Points Allocation
- **First Login:** 50 points
- **Daily Login:** 10 points
- **Idea Submission:** 25 points
- **Comment Posted:** 5 points
- **Idea Liked:** 2 points
- **Supervisor Approval:** 15 points

#### Achievement System
- **Milestones:** Point-based achievements
- **Badges:** Visual recognition for contributions
- **Leaderboards:** Community engagement tracking
- **Rewards:** Special recognition for top contributors

### 9. Technical Architecture

#### Application Structure
```
app/
├── Models/           # Eloquent models with relationships
├── Services/         # Business logic layer
├── Http/Controllers/ # Request handling
├── Events/           # Event-driven architecture
├── Listeners/        # Event handlers
├── Jobs/            # Queue-based processing
├── Mail/            # Email templates
└── Providers/       # Service providers

resources/views/     # Blade templates
├── livewire/        # Volt components
├── components/      # Reusable UI components
└── layouts/         # Base templates

database/
├── migrations/      # Database schema
├── seeders/         # Test data
└── factories/       # Model factories
```

#### Key Design Patterns
- **Service Layer Pattern:** Business logic encapsulation
- **Repository Pattern:** Data access abstraction (planned)
- **Observer Pattern:** Event-driven notifications
- **Strategy Pattern:** Authentication methods
- **Factory Pattern:** Dynamic form generation

#### Security Implementation
- **Input Validation:** Laravel Form Requests
- **CSRF Protection:** Automatic token validation
- **SQL Injection Prevention:** Eloquent ORM protection
- **XSS Protection:** Blade templating security
- **Rate Limiting:** Authentication and API endpoints
- **Data Encryption:** Sensitive data protection

### 10. Database Schema

#### Core Tables

##### users
- Basic user information and authentication
- Account status and preferences
- Points and gamification data

##### staff
- Extended profile for KeNHA employees
- Department and supervisor relationships
- Employment type and approval status

##### ideas
- Idea content and metadata
- Status workflow and collaboration settings
- File attachments and relationships

##### comments
- Threaded comment system
- Like functionality and moderation
- User relationships and timestamps

##### departments & directorates
- Organizational structure
- Hierarchical relationships

##### notifications
- User notification system
- Read/unread status tracking

#### Relationship Overview
```
User (1) ──── (1) Staff
   │              │
   ├── (Many) Ideas
   ├── (Many) Comments
   ├── (Many) Likes
   └── (Many) Notifications

Department (1) ──── (Many) Staff
Directorate (1) ──── (Many) Departments

Idea (1) ──── (Many) Comments
Idea (1) ──── (Many) Likes
```

### 11. API & Integration Points

#### Current Endpoints
- **Authentication:** `/login`, `/verify-otp`, `/logout`
- **Profile:** `/profile/setup`, `/profile/update`
- **Ideas:** `/ideas`, `/ideas/{id}`, `/ideas/{id}/comments`
- **Admin:** `/admin/users`, `/admin/approvals`

#### Future API Expansion
- **Mobile App Integration:** RESTful API for mobile applications
- **Third-party Integrations:** External system connections
- **Webhook Support:** Real-time data synchronization
- **GraphQL API:** Flexible data querying (planned)

### 12. Deployment & Environment

#### Environment Configuration
```env
# Application
APP_NAME=KENHA-VATE
APP_ENV=production
APP_DEBUG=false

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=kenhavate

# Cache & Queue
REDIS_HOST=localhost
QUEUE_CONNECTION=redis

# Security
APP_KEY=base64_encoded_key
JWT_SECRET=secure_jwt_secret
```

#### Deployment Checklist
- [ ] Environment variables configured
- [ ] Database migrations run
- [ ] Storage permissions set
- [ ] SSL certificate installed
- [ ] Queue workers configured
- [ ] Cron jobs scheduled
- [ ] Backup system enabled

### 13. Future Development Roadmap

#### Module 2: Enhanced Analytics & Reporting
- **Dashboard Analytics:** Advanced metrics and visualizations
- **Report Generation:** PDF/Excel export capabilities
- **Performance Tracking:** Idea success metrics
- **User Engagement Analytics:** Activity and contribution tracking

#### Module 3: Advanced Collaboration Features
- **Real-time Collaboration:** Live editing and commenting
- **Version Control:** Idea revision history
- **File Management:** Advanced document handling
- **Integration APIs:** External tool connections

#### Module 4: Mobile Application
- **Native Apps:** iOS and Android applications
- **Offline Support:** Local data synchronization
- **Push Notifications:** Real-time updates
- **Camera Integration:** Photo/document capture

#### Module 5: AI-Powered Features
- **Smart Suggestions:** AI-powered idea improvement suggestions
- **Automated Categorization:** Intelligent thematic area assignment
- **Content Analysis:** Sentiment analysis and trend detection
- **Personalized Recommendations:** User-specific content suggestions

#### Module 6: Enterprise Integration
- **SSO Integration:** Corporate authentication systems
- **ERP Integration:** Financial and HR system connections
- **Document Management:** Enterprise document repositories
- **Workflow Automation:** Advanced approval processes

### 14. Testing & Quality Assurance

#### Testing Strategy
- **Unit Tests:** Model and service layer testing
- **Feature Tests:** End-to-end user journey testing
- **Browser Tests:** UI/UX testing with Laravel Dusk
- **API Tests:** Endpoint and integration testing

#### Code Quality Standards
- **PSR-12 Compliance:** PHP coding standards
- **Laravel Conventions:** Framework-specific patterns
- **Security Audits:** Regular security assessments
- **Performance Monitoring:** Response time and resource usage tracking

### 15. Support & Maintenance

#### Documentation Structure
- **User Guides:** End-user documentation
- **API Documentation:** Developer integration guides
- **Admin Manuals:** System administration guides
- **Troubleshooting:** Common issues and solutions

#### Support Channels
- **Help Desk:** Internal support system
- **Knowledge Base:** Self-service documentation
- **Training Materials:** User onboarding resources
- **Community Forums:** User-to-user support

---

## Version Information

**Current Version:** 1.0.0 (Module 1 Complete)
**Last Updated:** October 20, 2025
**Maintained By:** KENHA-VATE Development Team
**Documentation Version:** 1.0.0

---

## Quick Reference

### User Types & Requirements
| User Type | Email Domain | Staff Record | Supervisor | Profile Complete |
|-----------|-------------|--------------|------------|------------------|
| Regular | Any | No | No | Basic info only |
| KeNHA Staff | @kenha.co.ke | Yes | No | + Staff details |
| External Staff | Non-KeNHA | Yes | Yes | + Supervisor approval |

### Key URLs
- **Login:** `/login`
- **Dashboard:** `/dashboard`
- **Public Ideas:** `/ideas/public`
- **Profile Setup:** `/profile/setup`
- **Terms:** `/terms-conditions`

### Support Contacts
- **Technical Support:** tech@kenha.co.ke
- **User Support:** support@kenha.co.ke
- **Administration:** admin@kenha.co.ke

---

*This documentation will be continuously updated as new features are implemented and the platform evolves.*