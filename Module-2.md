# KENHAVATE - Module 2: Idea Revision & Collaboration Framework

## Overview
Module 2 implements a comprehensive revision and collaboration system for ideas. Unlike the admin review workflow, this collaboration is **user-initiated** - idea authors can enable collaboration to allow others to suggest edits to their idea files. Authors maintain full control to accept, reject, or rollback changes.

## Key Features
- **User-Controlled Collaboration**: Authors decide if collaboration is enabled
- **Revision Management**: Track all changes with full audit trail
- **Author Control**: Accept/reject/rollback suggested edits
- **Gamification**: Points for collaboration activities
- **Notifications**: Real-time updates for all participants
- **Audit Logging**: Complete activity tracking

## Architecture Overview

### Database Schema
```
ideas (enhanced)
├── current_revision_number
├── collaboration_enabled
├── collaboration_deadline
└── review_stage

idea_revisions (new)
├── idea_id, revision_number
├── changed_fields (JSON)
├── change_summary
├── created_by_user_id
├── revision_type ('author', 'collaborator', 'rollback')
└── status ('pending', 'accepted', 'rejected')

idea_collaborators (new)
├── idea_id, user_id
├── permission_level ('suggest', 'edit')
├── invited_by_user_id
├── status ('pending', 'active', 'removed')
└── invited_at, accepted_at

idea_collaboration_requests (new)
├── idea_id, collaborator_user_id
├── request_message
├── status ('pending', 'accepted', 'declined')
└── response_at, response_message
```

### Services Architecture
- **RevisionService**: Manages revision creation, acceptance, rejection
- **CollaborationService**: Handles collaborator invitations and permissions
- **AuditService**: Logs all collaboration and revision activities
- **NotificationService**: Sends notifications and emails
- **PointService**: Awards gamification points

---

## Implementation Roadmap

### Phase 1: Database Foundation
#### 1.1 Create Idea Revisions Table
- [ ] Create migration: `2025_10_20_000001_create_idea_revisions_table.php`
- [ ] Fields: id, idea_id, revision_number, changed_fields (JSON), change_summary, created_by_user_id, revision_type, status, created_at
- [ ] Indexes: idea_id, created_by_user_id, status
- [ ] Foreign key constraints

#### 1.2 Create Idea Collaborators Table
- [ ] Create migration: `2025_10_20_000002_create_idea_collaborators_table.php`
- [ ] Fields: id, idea_id, user_id, permission_level, invited_by_user_id, status, invited_at, accepted_at, removed_at
- [ ] Unique constraint: idea_id + user_id
- [ ] Indexes: idea_id, user_id, status

#### 1.3 Create Collaboration Requests Table
- [ ] Create migration: `2025_10_20_000003_create_idea_collaboration_requests_table.php`
- [ ] Fields: id, idea_id, collaborator_user_id, request_message, status, requested_at, response_at, response_message
- [ ] Indexes: idea_id, collaborator_user_id, status

#### 1.4 Enhance Ideas Table
- [ ] Create migration: `2025_10_20_000004_enhance_ideas_table_for_collaboration.php`
- [ ] Add columns: current_revision_number (INT, DEFAULT 1), collaboration_enabled (BOOLEAN, DEFAULT FALSE), collaboration_deadline (DATE, NULL)
- [ ] Add indexes for performance

### Phase 2: Models & Relationships
#### 2.1 IdeaRevision Model
- [ ] Create `app/Models/IdeaRevision.php`
- [ ] Relationships: belongsTo(Idea), belongsTo(User::class, 'created_by_user_id')
- [ ] Scopes: pending(), accepted(), rejected(), byType()
- [ ] Methods: accept(), reject(), getChangedFields()

#### 2.2 IdeaCollaborator Model
- [ ] Create `app/Models/IdeaCollaborator.php`
- [ ] Relationships: belongsTo(Idea), belongsTo(User), belongsTo(User::class, 'invited_by_user_id')
- [ ] Scopes: active(), pending(), byPermission()
- [ ] Methods: acceptInvitation(), removeCollaborator()

#### 2.3 IdeaCollaborationRequest Model
- [ ] Create `app/Models/IdeaCollaborationRequest.php`
- [ ] Relationships: belongsTo(Idea), belongsTo(User::class, 'collaborator_user_id')
- [ ] Scopes: pending(), accepted(), declined()
- [ ] Methods: accept(), decline()

#### 2.4 Enhance Idea Model
- [ ] Add relationships: hasMany(IdeaRevision), hasMany(IdeaCollaborator), hasMany(IdeaCollaborationRequest)
- [ ] Add methods: enableCollaboration(), disableCollaboration(), createRevision(), acceptRevision(), rollbackToRevision()
- [ ] Add scopes: collaborative(), withRevisions()

### Phase 3: Core Services
#### 3.1 RevisionService
- [ ] Create `app/Services/RevisionService.php`
- [ ] Methods:
  - [ ] `createRevision(Idea $idea, array $changes, string $summary, User $user, string $type = 'author')`
  - [ ] `acceptRevision(IdeaRevision $revision, User $author)`
  - [ ] `rejectRevision(IdeaRevision $revision, User $author, string $reason)`
  - [ ] `rollbackToRevision(Idea $idea, int $revisionNumber, User $author)`
  - [ ] `getRevisionHistory(Idea $idea, int $limit = 50)`
  - [ ] `compareRevisions(IdeaRevision $revision1, IdeaRevision $revision2)`

#### 3.2 CollaborationService
- [ ] Create `app/Services/CollaborationService.php`
- [ ] Methods:
  - [ ] `inviteCollaborator(Idea $idea, User $collaborator, string $permission, User $inviter)`
  - [ ] `requestCollaboration(Idea $idea, User $requester, string $message)`
  - [ ] `acceptCollaborationRequest(IdeaCollaborationRequest $request, User $author)`
  - [ ] `removeCollaborator(Idea $idea, User $collaborator, User $remover)`
  - [ ] `canUserEdit(Idea $idea, User $user): bool`
  - [ ] `getActiveCollaborators(Idea $idea)`

#### 3.3 Enhance PointService
- [ ] Add collaboration-related methods:
  - [ ] `awardCollaborationPoints(User $user, string $action)`
  - [ ] `getCollaborationPointsConfig(): array`
- [ ] Add to `config/kenhavate.php`:
  ```php
  'collaboration_points' => [
      'invite_collaborator' => env('POINTS_INVITE_COLLABORATOR', 5),
      'accept_invitation' => env('POINTS_ACCEPT_INVITATION', 10),
      'suggest_revision' => env('POINTS_SUGGEST_REVISION', 15),
      'revision_accepted' => env('POINTS_REVISION_ACCEPTED', 25),
      'collaboration_request' => env('POINTS_COLLABORATION_REQUEST', 3),
  ]
  ```

#### 3.4 Enhance NotificationService
- [ ] Add collaboration notification methods:
  - [ ] `collaborationInvite(User $collaborator, Idea $idea, User $inviter)`
  - [ ] `collaborationRequest(User $author, Idea $idea, User $requester, string $message)`
  - [ ] `revisionSuggested(User $author, Idea $idea, IdeaRevision $revision)`
  - [ ] `revisionAccepted(User $collaborator, Idea $idea, IdeaRevision $revision)`
  - [ ] `revisionRejected(User $collaborator, Idea $idea, IdeaRevision $revision, string $reason)`
  - [ ] `collaboratorRemoved(User $removedUser, Idea $idea, User $remover)`

### Phase 4: Controllers & API
#### 4.1 CollaborationController
- [ ] Create `app/Http/Controllers/CollaborationController.php`
- [ ] Methods:
  - [ ] `inviteCollaborator(Request $request, Idea $idea)`
  - [ ] `requestCollaboration(Request $request, Idea $idea)`
  - [ ] `respondToRequest(Request $request, IdeaCollaborationRequest $collaborationRequest)`
  - [ ] `removeCollaborator(Request $request, Idea $idea, User $collaborator)`
  - [ ] `toggleCollaboration(Request $request, Idea $idea)`

#### 4.2 RevisionController
- [ ] Create `app/Http/Controllers/RevisionController.php`
- [ ] Methods:
  - [ ] `createRevision(Request $request, Idea $idea)`
  - [ ] `acceptRevision(Request $request, IdeaRevision $revision)`
  - [ ] `rejectRevision(Request $request, IdeaRevision $revision)`
  - [ ] `rollbackRevision(Request $request, Idea $idea)`
  - [ ] `getRevisionHistory(Idea $idea)`
  - [ ] `compareRevisions(IdeaRevision $revision1, IdeaRevision $revision2)`

#### 4.3 Enhance IdeaController
- [ ] Add collaboration endpoints:
  - [ ] `GET /ideas/{idea}/collaborators`
  - [ ] `GET /ideas/{idea}/revisions`
  - [ ] `POST /ideas/{idea}/collaboration/toggle`

### Phase 5: Frontend Implementation
#### 5.1 Livewire Components
- [ ] `CollaborationManager` - Manage collaborators and permissions
- [ ] `RevisionHistory` - Display revision timeline
- [ ] `RevisionComparison` - Side-by-side revision comparison
- [ ] `CollaborationRequests` - Handle incoming collaboration requests

#### 5.2 Blade Views
- [ ] `resources/views/livewire/ideas/collaboration-manager.blade.php`
- [ ] `resources/views/livewire/ideas/revision-history.blade.php`
- [ ] `resources/views/livewire/ideas/revision-comparison.blade.php`
- [ ] `resources/views/components/revision-badge.blade.php`
- [ ] `resources/views/components/collaborator-avatar.blade.php`

#### 5.3 JavaScript Enhancements
- [ ] Real-time collaboration status updates
- [ ] Revision diff highlighting
- [ ] Drag-and-drop collaborator management

### Phase 6: UI/UX Features
#### 6.1 Idea Detail Page Enhancements
- [ ] Collaboration toggle button
- [ ] Collaborator list with avatars
- [ ] Revision history sidebar
- [ ] "Suggest Edit" button for collaborators

#### 6.2 Collaboration Dashboard
- [ ] My collaborations (ideas I'm collaborating on)
- [ ] Collaboration requests (pending invitations)
- [ ] Recent collaboration activity

#### 6.3 Revision Management UI
- [ ] Revision timeline with expandable details
- [ ] Accept/Reject/Compare actions
- [ ] Rollback confirmation dialogs
- [ ] Change summary display

### Phase 7: Audit & Security
#### 7.1 Audit Trail Implementation
- [ ] All collaboration actions logged via AuditService:
  - [ ] `collaboration_invite_sent`
  - [ ] `collaboration_invite_accepted`
  - [ ] `collaboration_request_sent`
  - [ ] `revision_created`
  - [ ] `revision_accepted`
  - [ ] `revision_rejected`
  - [ ] `collaborator_removed`
  - [ ] `collaboration_enabled`
  - [ ] `collaboration_disabled`

#### 7.2 Security Measures
- [ ] Author-only actions: enable/disable collaboration, accept/reject revisions
- [ ] Collaborator permissions: view, suggest, edit (based on permission level)
- [ ] Revision integrity: prevent unauthorized modifications
- [ ] Rate limiting: collaboration requests and revision submissions

### Phase 8: Testing & Validation
#### 8.1 Unit Tests
- [ ] RevisionService tests
- [ ] CollaborationService tests
- [ ] Model relationship tests

#### 8.2 Feature Tests
- [ ] Collaboration workflow tests
- [ ] Revision management tests
- [ ] Permission validation tests

#### 8.3 Integration Tests
- [ ] Full collaboration cycle tests
- [ ] Audit logging verification
- [ ] Notification delivery tests

### Phase 9: Documentation & Deployment
#### 9.1 User Documentation
- [ ] How to enable collaboration
- [ ] Managing collaborators
- [ ] Working with revisions
- [ ] Best practices for collaboration

#### 9.2 API Documentation
- [ ] Collaboration endpoints
- [ ] Revision management endpoints
- [ ] Webhook documentation (if applicable)

#### 9.3 Deployment Checklist
- [ ] Database migrations
- [ ] Configuration updates
- [ ] Permission updates
- [ ] Email template deployment

---

## Success Metrics
- [ ] Users can successfully invite collaborators
- [ ] Collaborators can suggest revisions
- [ ] Authors can accept/reject/rollback revisions
- [ ] All actions are properly audited
- [ ] Users receive appropriate notifications
- [ ] Gamification points are awarded correctly
- [ ] Performance remains optimal with revision history

## Risk Mitigation
- **Data Integrity**: Comprehensive validation and transaction handling
- **Performance**: Efficient queries with proper indexing
- **Security**: Strict permission checks and audit trails
- **User Experience**: Clear UI feedback and error handling
- **Scalability**: Lightweight revision storage strategy

## Future Enhancements
- Real-time collaborative editing
- Revision comments and discussions
- Advanced diff visualization
- Collaboration analytics
- Integration with external collaboration tools

---

*Module 2 focuses on empowering users through controlled collaboration while maintaining data integrity and comprehensive audit trails.*</content>
<parameter name="filePath">/Users/app/Desktop/Laravel/KeNHA-VATE/Module-2.md
