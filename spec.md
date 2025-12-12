# Feature Specification: Bundle Flow - Document Collection & Control

**Created**: 2025-12-02
**Status**: Draft
**Input**: Bundle Flow is a web service for document collection and control built around checklists using a role-based permission system. Users with BF-CONFIG permission
manage an organization-wide item library, checklist templates, and permission role assignments. Users with appropriate permissions (BF-OWNER) create checklists from templates and can
add library or manual items. A bundle is a list of document IDs with metadata that arrives via integration (representing documents extracted from zip files by an external service). Each bundle has a main document ID (typically representing the email body when the bundle originates from an email). **IMPORTANT: Bundle Flow does not store or handle actual document files. It receives only document IDs and metadata. All actual documents are stored in a dedicated external document storage service.** Each bundle relates to exactly ONE checklist. On bundle
arrival, the system creates an unassigned task in a triage pool; any user with BF-SORT permission can manually claim tasks from the pool. The claiming user selects which
checklist to associate the bundle with and submits for processing. The task then transfers to the checklist responsible (who must have BF-RESPONSIBLE and BF-MANAGE permissions),
who associates bundle items with checklist items (creating document links with validation states: Attached, Valid,
Rejected, Incomplete) or tags them as "Not relevant" (acknowledged but not required for completion). A single task per bundle progresses through three actions requiring different permissions: attach to checklist (BF-SORT), attach items to checklist items (BF-MANAGE), and process/validate links (BF-VALIDATE). The task is completed when a user with BF-VALIDATE permission explicitly marks it complete. Users can transfer tasks to other users at any time. Additionally, users can create manual tasks for reminders (with due dates and escalation) and sending emails (with optional templates). When all applicable
checklist items have valid links (excluding N/A items), the checklist completes automatically. Access is checklist-scoped: users see only checklists they own, are responsible for, or have tasks on. The
system provides UIs to search documents, checklists, and bundles by metadata and text, plus a view of all documents
attached to a checklist. Audit logs can be deleted on demand by system administrators. External document storage failures result
in immediate errors requiring manual retry.

## Clarifications

### Session 2025-12-02

- Q: What distinguishes owner vs responsible roles in terms of permissions? → A: Owner can edit/delete checklist &
  reassign responsible; Responsible can only validate bundle items and bundle item groups
- Q: What is the scope of the item library? → A: Organization-wide library managed by administrators
- Q: Who can create and manage checklist templates? → A: Administrators only
- Q: How do N/A items affect checklist completion? → A: N/A items are excluded from completion calculation entirely
- Q: Can responsibility be shared or delegated? → A: Single responsible, can be reassigned by owner
- Q: Who is the "default user" for initial task assignment? → A: A configurable default user per organization/team
- Q: Can a bundle or its items be associated with multiple checklists? → A: No. ONE bundle relates to exactly ONE checklist. Bundle items cannot be shared across different checklists. Each task (representing one bundle) can only be linked to one checklist.
- Q: When does task reassignment to checklist responsible occur? → A: When the default user explicitly submits/confirms
  the task for processing
- Q: Can a triage user (with BF-SORT) link bundle items to checklist items directly? → A: Yes, if the user also has BF-MANAGE permission; permissions are additive, so a user with both BF-SORT and BF-MANAGE can perform both actions sequentially
- Q: What is a "bundle"? → A: A list of document IDs with metadata that arrives via integration (representing documents extracted from a zip file by an external service). Bundle Flow does not receive or store actual document files. One bundle creates one task linked to exactly one checklist.
- Q: How are bundle contents handled on arrival? → A: An external integration service extracts documents from zip files and sends Bundle Flow a list of document IDs with metadata. Bundle Flow receives this list and creates bundle item records (one per document ID).
- Q: When is a task complete? → A: When a user with BF-VALIDATE permission explicitly marks the task complete after completing all three required actions (attach to checklist, attach items to checklist items, process/validate links)
- Q: Can one bundle item link to multiple checklist items? → A: Yes, one bundle item can link to multiple checklist items within the SAME checklist (e.g., a comprehensive document satisfying multiple requirements). However, bundle items cannot be shared across different checklists.
- Q: Can multiple bundles be received for the same checklist? → A: Yes, multiple bundles/tasks can be linked to the same
  checklist over time
- Q: Should each bundle item have its own status? → A: Yes, each bundle item has its own status (unprocessed,
  associated, not relevant)
- Q: Where do template items come from? → A: All items in checklist templates are from the managed item library
  (templates reference library items, not separate definitions)
- Q: What task categories exist in the system? → A: Bundle Processing tasks (auto-created, one per bundle, progresses through three actions) and Manual tasks ("Create Reminder" with due date, "Send Email" with/without template). There is ONE task per bundle that progresses through actions, not separate task types for each action.
- Q: Are Reminder and Send Email tasks part of Bundle Flow? → A: Yes, manual tasks are part of Bundle Flow and created by checklist owner/responsible
- Q: Who manages email templates? → A: Administrators manage email templates; users select from available templates
- Q: What happens when a reminder due date is reached? → A: System sends notification and escalates to checklist owner
  if not actioned
- Q: Who can create manual tasks (Reminder, Send Email)? → A: Owner and Responsible can create tasks
- Q: Who can reallocate a task? → A: Current assignee, checklist owner, and checklist responsible can reallocate
- Q: How are sent emails and documents stored? → A: All documents (received and sent) stored in dedicated external web service; locally store document metadata and document ID only. Emails with attachments are zipped as bundles by integration service.
- Q: What happens when external document storage is unavailable? → A: Fail immediately and show error; user must retry manually.
- Q: What is the access control model? → A: Checklist-scoped access (users see only checklists they own, are responsible for, or have tasks on).
- Q: What search functionality is required? → A: Metadata search with filters plus text search on names/descriptions.

### Session 2025-12-03

- Q: How do the new bundle item document control statuses (To be controlled, Valid, Rejected, Incomplete) relate to the existing states (pending/validated/invalid)? → A: The new 4-state model replaces the existing 3-state model completely
- Q: How should bundle item group status be managed - direct selection or guided by controls? → A: Control checklists are OPTIONAL. If a control checklist is configured for the checklist item, the user must complete it before setting validation status. If NO controls are configured, the user can set validation status (Valid/Rejected/Incomplete) directly.
- Q: Should checklist item global status be manually set or automatically calculated? → A: Status automatically calculated per defined rules; only "Abandoned" is set manually
- Q: How do "Not relevant" and "managed" statuses relate? → A: "Not relevant" replaces "managed"; single status for acknowledged non-required items
- Q: At what level are control checklists configured and by whom? → A: Controls are configured at the checklist template level by users with BF-CONFIG permission. Each item in the template can have its own control checklist definition.
- Q: At what level are control checklists applied - bundle item group, checklist item, or individual bundle items? → A: Controls are inherited from the template when a checklist is created. When validating, controls apply at the checklist item level (used when validating both individual bundle items and bundle item groups linked to that checklist item).
- Q: Do both individual bundle items and bundle item groups go through the control checklist validation process? → A: Both individual bundle items (not in groups) and bundle item groups use the same control checklist from the linked checklist item
- Q: For checklist item "Received" status (FR-097), does "all related" mean every link must be Valid or just one? → A: At least ONE link to the checklist item must be Valid for "Received" status (updated 2025-12-05: was "ALL links", now "at least one")
- Q: Is validation status tracked at the document link level or at the bundle item/bundle item group level? → A: Validation status is at the bundle item level or bundle item group level, not at the link level
- Q: What is the initial status of bundle items when extracted from a bundle? → A: Bundle items start with "Unprocessed" status and transition to "To be controlled" when linked to checklist items
- Q: What is the initial status of bundle item groups when created? → A: Bundle item groups start with "To be controlled" status when created (already intended for linking)

### Session 2025-12-04

- Q: What is the relationship between bundle items, bundle item groups, and checklist item links? → A: One bundle item can belong to multiple Bundle Item Groups simultaneously. If a bundle item is in any Bundle Item Group, it cannot be directly linked to checklist items (only groups can be linked). If a bundle item has any direct checklist item links, it cannot be added to any Bundle Item Group. The constraint is mutually exclusive and bidirectional.
- Q: When a checklist is created from a template, how are control checklists configured for each checklist item? → A: Controls are defined at the template level by users with BF-CONFIG permission. When a checklist is created from a template, each checklist item inherits the control checklist definition from the corresponding template item.
- Q: How does the "Incomplete" status relate to control checklist completion? → A: Incomplete is for cases where controls cannot yet be fully evaluated (e.g., missing information, pending external review). If controls are evaluated and any are marked unsatisfied, the validation status must be set to Rejected. Distinction: Rejected = evaluated and failed; Incomplete = cannot fully evaluate yet.
- Q: What is the permission model for bundle processing actions? → A: ONE task per bundle progresses through three required actions, each requiring different permissions (BF-SORT for attach to checklist, BF-MANAGE for attach items to checklist items, BF-VALIDATE for process/validate). There is no automatic task splitting. If a user lacks the required permission for the next action, they can transfer the task to another user at any time.
- Q: Can a bundle item be removed from a Bundle Item Group after being added? → A: Bundle items can be removed at any time; removing any item automatically resets the group's validation status to "To be controlled". This ensures validation always matches current composition and requires full re-validation after any composition change.
- Q: Who can create and manage bundle item groups? → A: Only the responsible user can create bundle item groups during the association workflow (bundle item groups are created at the same time as associating items to checklist items).
- Q: How is the bundle item group name/identifier determined when creating a group? → A: Group names are selected from a predefined list of bundle item group types managed by administrators, with the ability to enter free text if the needed group name is not in the predefined list (fallback for missing configuration or unexpected cases).
- Q: Can additional bundle items be added to an existing bundle item group after initial creation? → A: Yes, but only if the group has not been validated yet (status is still "To be controlled"). Once validated (Valid, Rejected, or Incomplete), the group composition is locked and no items can be added.
- Q: When all bundle items are removed from a bundle item group (group becomes empty), what happens to the group and its checklist item links? → A: The group and its checklist item links remain intact; group status is "To be controlled". User can manually delete the empty group or add new items to it.
- Q: When a bundle item group is deleted, what happens to the bundle items that were members of that group? → A: Bundle items are released from the group and their status returns to "Unprocessed". They can then be directly linked to checklist items or added to other groups.
- Q: Who can perform rollback when a bundle is associated with the wrong checklist? → A: Either the default user or the checklist responsible can initiate rollback to unlink the bundle from the wrong checklist.
- Q: When can rollback occur? → A: At any time, even after bundle items have been associated to checklist items; all association work is discarded.
- Q: What happens to the checklist after rollback? → A: The checklist remains but the bundle is unlinked; any bundle item associations to that checklist are removed.
- Q: What happens to the bundle and task after rollback? → A: Bundle items reset to "Unprocessed" status; a new task is created and assigned to the default user; the original task is closed.
- Q: What happens to bundle item groups created during the wrong association? → A: Bundle item groups are preserved and can be reused with the correct checklist; only the checklist item links are removed.
- Q: When can checklists be created - only during bundle triage or also before bundle arrival? → A: Both. Checklists can be created proactively by any user with appropriate permissions (e.g., loan officer initiating credit application) OR created on-demand by the default user during bundle triage.
- Q: When the default user triages a bundle, can they select from existing checklists or only create new ones? → A: Both. The default user can select from existing checklists (created proactively by business users) OR create a new checklist from template if one doesn't exist.
- Q: When the default user creates a new checklist during triage, who becomes the checklist owner? → A: The default user must specify the owner during creation and can assign themselves or another user. The specified user becomes both owner and responsible by default.
- Q: How are parties managed in the system? → A: Parties (suppliers, vendors, etc.) are imported from external CRM systems with ID and Name. Party data is not created manually within Bundle Flow.
- Q: What is the relationship between parties and checklists? → A: A checklist can optionally have multiple parties attached, each with a specific role (e.g., "Primary Supplier", "Co-applicant", "Transporter"). Party tracking is optional - not all checklists require parties. Multiple checklists can exist for different parties in the same business case. **Updated 2025-12-05**: Changed from single party to multiple parties per checklist.
- Q: How are party roles determined? → A: Party roles are selected from a predefined list managed by administrators, with free text fallback for unexpected cases.
- Q: Can parties on a checklist be changed? → A: Yes, parties and their roles can be added, removed, or modified at any time by the checklist owner without restrictions. **Updated 2025-12-05**: Removed bundle-linking constraint - parties are always modifiable.

### Session 2025-12-05

- Q: How should the system handle users with multiple roles? → A: Additive/cumulative - Users can be assigned multiple roles simultaneously and inherit permissions from all assigned roles
- Q: What is the relationship between checklist roles (owner/responsible assignment) and permission roles (BF-OWNER, BF-RESPONSIBLE, etc.)? → A: Prerequisite - User must have the required permission role (BF-OWNER or BF-RESPONSIBLE) before they can be designated as owner/responsible on a checklist
- Q: How should the system enforce that BF-RESPONSIBLE should always have at least BF-MANAGE rights? → A: Validation rule - System validates that any user with BF-RESPONSIBLE also has BF-MANAGE; prevents assigning BF-RESPONSIBLE without BF-MANAGE or removing BF-MANAGE from users with BF-RESPONSIBLE
- Q: Who can assign and manage permission roles? → A: BF-CONFIG only - Only users with BF-CONFIG permission can assign/revoke any permission roles to/from other users
- Q: How are incoming bundle tasks assigned for triage? → A: Manual claim from pool - Tasks are created unassigned and placed in a pool; any user with BF-SORT permission can manually claim tasks from the pool

### Session 2025-12-05 (Afternoon)

- Q: For checklist item status "Received", must ALL links be Valid or is at least one Valid link sufficient? → A: At least ONE link to the checklist item must be Valid for the status to change to "Received". Not all links need to be Valid.
- Q: What is the fundamental assumption about checklist items and functional documents? → A: Each checklist item represents ONE functional document type (e.g., "CIN" is one checklist item, "Contract" is another checklist item). One bundle item or bundle item group satisfying that functional requirement is sufficient.

### Session 2025-12-05 (Status Terminology)

- Q: Should "Attached" replace "To be controlled" or be added as a separate status? → A: Replace "To be controlled" with "Attached" for bundle items in groups linked to checklist items
- Q: Which entities should have the "Attached" status? → A: Both bundle items (in groups) AND bundle item groups (when linked to checklist items)
- Q: When does a bundle item or bundle item group transition to "Attached" status? → A: Bundle item moves to "Attached" when it is linked to a group OR when directly linked to checklist items. Bundle item group moves to "Attached" when it is linked to a checklist item.
- Q: What status should individual bundle items (NOT in any group) have when directly linked to checklist items? → A: "Attached" - same status name for both direct links and group-based links
- Q: When a bundle item is removed from a bundle item group, what happens to the group's status? → A: Reset to "Attached" (still linked to checklist items, needs re-validation)

### Session 2025-12-05 (Control Structure)

- Q: What attributes must each control in a control checklist have? → A: Type (coherency/conformity/etc.), Status (verified/not verified/skipped), Description, Comment (no mandatory flag)
- Q: Should verification method (manual/visual vs automatic) be tracked as a separate control attribute? → A: No - verification method not tracked as separate attribute
- Q: How should control types (coherency, conformity, etc.) be managed? → A: Predefined list managed by BF-CONFIG users, with free text fallback
- Q: What are the allowed control status values and validation logic? → A: Status values are Verified, Not verified, Skipped. Controls are satisfied when status is Verified OR Skipped. If any control has status "Not verified", the bundle item/group validation must be set to Rejected.
- Q: Can all controls be left unevaluated or must they be explicitly marked? → A: All controls must be explicitly marked with one of the three status values (Verified, Not verified, or Skipped) before completing validation
- Q: Are control checklists mandatory or optional? → A: **COMPLETELY OPTIONAL.** Controls can be configured or not depending on customer context. Defining controls at template level is optional. If controls are not configured, validation is performed through direct operator judgment without structured guidance. The validation workflow adapts based on whether controls exist or not.

### Session 2025-12-05 (Party Management)

- Q: At what level are multiple parties associated? → A: Checklist can have many parties (each with a dedicated role like client, supplier, transporter). Individual checklist items can have zero or one party (with an independent role).
- Q: Must checklist item parties be selected from the checklist's associated parties? → A: No - checklist item parties can be completely different from the checklist's associated parties. Checklist items have independent party selection.
- Q: Is party assignment to checklist items mandatory or optional? → A: Optional - checklist items can have a party assigned or remain without party assignment.
- Q: When assigning a party to a checklist item, is the role inherited or independent? → A: Independent - checklist items specify their own role for the assigned party, which can be different from any role defined at the checklist level.
- Q: Can parties and roles be modified after assignment? → A: Always modifiable - parties and roles can be changed at any time at both checklist and checklist item levels without locking constraints.

### Session 2025-12-05 (Configuration Versioning)

- Q: What configuration elements are included in a version's JSON export? → A: Templates, Item Library, Party Roles, Bundle Item Group Types, Control Types (configuration only, not runtime data like checklists/bundles).
- Q: Who has permission to create and manage configuration versions? → A: Only BF-CONFIG users can create, export, and import versions.
- Q: Can multiple configuration versions be active simultaneously? → A: Only one version active at a time; activating new version deactivates all others. New checklists use active version; existing checklists continue using their original version.
- Q: When importing a JSON file to create a new version, does it replace or merge? → A: Replace - new version completely replaces the active version's configuration.
- Q: How are configuration versions identified? → A: Manual name/label only (user-defined, must be unique).

### Session 2025-12-08

- Q: What is the audit log retention policy? → A: No retention period - audit logs can be deleted on demand by users with BF-CONFIG permission.
- Q: Who can delete audit logs? → A: Only users with BF-CONFIG permission can delete audit logs.
- Q: How should the system handle concurrent operations (e.g., two users editing the same checklist or validating the same bundle item)? → A: Optimistic locking - detect conflicts and require user to refresh and retry.
- Q: What is the retention policy for operational data (checklists, bundles, tasks, etc.)? → A: Indefinite retention - all operational data kept until manually deleted by authorized users.
- Q: What file types/formats are supported for bundle items (documents in bundles)? → A: PDF, Office documents (docx/xlsx only, excluding ppt/pptx), images (jpg/png/tiff), text files (txt/csv). Executables and scripts are explicitly blocked. File type restrictions can optionally be configured at the control checklist level.
- Q: How should the system handle file type validation when control checklists specify file type restrictions? → A: Manual verification - file type becomes one of the control checklist items to verify. Validators manually check file type compliance as part of their control checklist evaluation process.
- Q: How should search results be paginated across document, checklist, and bundle searches? → A: Server-side pagination with 50 results per page (configurable page size).

## Permission Model

Bundle Flow uses a role-based permission system with six predefined permission roles. User capabilities are determined by their assigned permission roles, not by checklist relationships (owner/responsible) alone. Users can have multiple roles simultaneously, with permissions being additive/cumulative.

### Predefined Permission Roles

- **BF-CONFIG**: Can perform any system setup including managing item library, checklist templates, email templates, bundle item group types, party roles, control types, configuration versions (create, export, import, activate), assigning/revoking permission roles to/from users, and deleting audit logs. This is the only role that can manage user permissions, configuration versions, and audit log deletion.

- **BF-SORT**: Can associate a bundle to a checklist during triage. Users with this permission can claim unassigned bundle tasks from the triage pool. When a bundle arrives, the task is created unassigned and any user with BF-SORT permission can manually claim it.

- **BF-MANAGE**: Can associate bundle items or bundle item groups to checklist items. This permission is required for processing bundles after triage. Users with BF-RESPONSIBLE must also have BF-MANAGE.

- **BF-VALIDATE**: Can validate association of bundle items or bundle item groups to checklist items by completing control checklists and setting validation status (Valid, Rejected, Incomplete).

- **BF-OWNER**: Can own a checklist, which grants the ability to edit checklist structure, delete the checklist, reassign the responsible user, and manage checklist configuration. A user must have BF-OWNER permission before being designated as owner on any checklist.

- **BF-RESPONSIBLE**: Can be assigned as the responsible user on a checklist, which makes them the default recipient for tasks associated with that checklist. A user must have both BF-RESPONSIBLE and BF-MANAGE permissions before being designated as responsible on any checklist. The system enforces that BF-RESPONSIBLE users always have BF-MANAGE.

### Permission Rules

1. **Additive Permissions**: Users can be assigned multiple permission roles simultaneously. A user inherits permissions from all assigned roles.

2. **Prerequisite for Checklist Relationships**:
    - To be designated as checklist owner, user must have BF-OWNER permission
    - To be designated as checklist responsible, user must have both BF-RESPONSIBLE and BF-MANAGE permissions
    - Checklist relationship assignment (owner/responsible) does not automatically grant permissions; permissions must be assigned separately via BF-CONFIG users

3. **Role Management**: Only users with BF-CONFIG permission can assign or revoke permission roles. No other role has permission management capabilities.

4. **Task Progression and Transfer**: A single task per bundle progresses through multiple actions (attach to checklist, attach items to checklist items, process/validate links). Each action requires different permissions (BF-SORT, BF-MANAGE, BF-VALIDATE). Users can transfer the task to another user at any time if they lack the required permission for the next action.

## User Scenarios & Testing *(mandatory)*

### User Story 0 - Bundle Arrival and Task Creation (Priority: P0)

As the system, when a bundle (zip file containing multiple documents) arrives via integration, I need to automatically
extract the documents, create an unassigned task with all bundle items attached, and place it in the triage pool where
any user with BF-SORT permission can claim it. This ensures no incoming bundle is lost and all documents enter a triage workflow.

**Why this priority**: This is the entry point for all documents into the system. Without automatic task creation and
bundle extraction, documents cannot enter the checklist workflow.

**Independent Test**: Can be fully tested by simulating bundle arrival and verifying an unassigned task is created with all
extracted bundle items and placed in the triage pool visible to users with BF-SORT permission.

**Acceptance Scenarios**:

1. **Given** a bundle (zip file) arrives in the system, **When** the system processes the incoming bundle, **Then** the
   system extracts all documents and creates a new unassigned task with all bundle items attached and places it in the triage pool.
2. **Given** a bundle contains 5 documents, **When** the system extracts the bundle, **Then** all 5 documents appear as
   individual bundle items within the task, each with status "unprocessed".
3. **Given** a task is created for an incoming bundle, **When** any user with BF-SORT permission views the triage pool, **Then** they
   can see the new unassigned task with all extracted bundle items ready for claiming.
4. **Given** an unassigned task is in the triage pool, **When** a user with BF-SORT permission claims the task, **Then** the task is assigned to that user and removed from the unassigned pool.

---

### User Story 0a - Triage Bundle to Checklist (Priority: P0)

As a user with BF-SORT permission, when I claim a task from the triage pool, I need to either select an existing checklist or create a new
checklist from template to associate with the bundle, then complete the first action (attach to checklist). When creating a new checklist,
I must specify who will be the owner (can be myself or another user). After completing this action, the task continues to the next action (attach items to checklist items), which I can perform myself if I have BF-MANAGE permission, or transfer to another user.

**Why this priority**: This is the triage step that associates bundles with the correct checklist. Without this, bundles cannot
progress through the workflow. Supports both proactive workflows (checklist created before bundle arrival) and
on-demand workflows (checklist created during triage).

**Independent Test**: Can be fully tested by a user with BF-SORT permission claiming a task, selecting a checklist, completing the attach-to-checklist action, then either continuing to the next action or transferring the task.

**Acceptance Scenarios**:

1. **Given** I am a user with BF-SORT permission who has claimed a task containing a bundle, **When** I view available options, **Then** I can see a list of existing checklists to select from OR the option to create a new checklist from template.
2. **Given** I am a user with BF-SORT permission selecting from existing checklists, **When** I select an existing checklist (e.g., created proactively by a loan officer for a credit application), **Then** the bundle is linked to that existing checklist.
3. **Given** I am a user with BF-SORT permission and no suitable checklist exists, **When** I choose to create a new checklist from template, **Then** I must select a template, specify the owner (can be myself or another user who will manage this checklist), optionally attach one or more parties with roles if known, and the system creates the checklist with the specified user as both owner and responsible.
4. **Given** I have associated a bundle with a checklist (existing or newly created), **When** I complete the attach-to-checklist action, **Then** the task progresses to the next action (attach items to checklist items) and remains assigned to me if I have BF-MANAGE permission, or I can transfer it to another user.
5. **Given** the attach-to-checklist action is complete and I have BF-MANAGE permission, **When** I continue to the next action, **Then** I can associate bundle items with checklist items or tag them as "Not relevant".
   5a. **Given** the attach-to-checklist action is complete and I lack BF-MANAGE permission, **When** I transfer the task to a user with BF-MANAGE permission, **Then** that user receives the task and can perform the next action.
6. **Given** I am a user with BF-SORT permission but without BF-MANAGE permission, **When** I try to associate bundle items with checklist items directly, **Then** the system prevents this action (only the attach-to-checklist action is available without BF-MANAGE).
   6a. **Given** I am a user with both BF-SORT and BF-MANAGE permissions, **When** I complete the attach-to-checklist action, **Then** I can continue directly to associating bundle items with checklist items without needing to transfer the task.
7. **Given** I am a user with BF-SORT permission and realize I selected the wrong checklist, **When** I initiate rollback before submitting the task, **Then** the bundle is unlinked from the wrong checklist and I can select a different checklist or create a new one.
8. **Given** the task was submitted and the responsible has started processing, **When** either I (the triage user) or the responsible initiates rollback, **Then** the original task is closed, all bundle items reset to "Unprocessed", bundle item groups are preserved but unlinked, a new unassigned task is created and placed back in the triage pool.
9. **Given** rollback has occurred, **When** a user with BF-SORT permission views the triage pool, **Then** they can see the rolled-back task with all bundle items in "Unprocessed" status and any previously created bundle item groups available for reuse.

---

### User Story 0b - Process Bundle Items as Responsible (Priority: P0)

As the checklist responsible, when I receive a task with a bundle linked to my checklist, I need to process each bundle
item by either associating it directly with specific checklist item(s), grouping multiple bundle items into bundle item groups and then associating the group with checklist item(s), or tagging it as "Not relevant" if it doesn't
fit any item but has been reviewed. I can then mark the task as complete when all bundle items are processed.

**Why this priority**: This is where bundle items get properly categorized for checklist completion or marked as handled
without item linkage.

**Independent Test**: Can be fully tested by the responsible user associating bundle items to checklist items or marking
them as not relevant, then completing the task.

**Acceptance Scenarios**:

1. **Given** I am the checklist responsible with a task containing a bundle, **When** I view the task, **Then** I can
   see all bundle items with their current status (unprocessed, associated, not relevant) and the list of checklist items available to associate them with.
2. **Given** I am processing a bundle item, **When** I associate the bundle item directly with one or more checklist items,
   **Then** document links are created with "Attached" state and the bundle item status changes to
   "Attached".
   2a. **Given** I am processing multiple related bundle items, **When** I create a bundle item group (e.g., grouping "CIN recto" and "CIN verso" into "CIN" group) and associate the group with one or more checklist items,
   **Then** the bundle item group is created with "Attached" status, group-to-checklist links are created, and the grouped bundle items can no longer be directly associated to checklist items.
3. **Given** I am processing a bundle item that doesn't fit any checklist item, **When** I tag the bundle item as
   "Not relevant", **Then** the bundle item is marked as acknowledged/processed and its status changes to "not relevant".
4. **Given** a bundle item is tagged as "Not relevant", **When** I view the checklist, **Then** the not relevant document appears
   in a separate section (not blocking completion) but remains associated with the checklist for audit purposes.
5. **Given** bundle items or bundle item groups are linked to checklist items, **When** I view the checklist, **Then** I can see which checklist items have documents attached with their current validation state.
6. **Given** all bundle items in a task have been processed (associated or not relevant), **When** I mark the task as
   complete, **Then** the task status changes to "completed".
7. **Given** some bundle items are still "unprocessed", **When** I attempt to mark the task as complete, **Then** the
   system warns me about unprocessed items (but may allow completion with confirmation).

---

### User Story 0c - Validate Bundle Items and Bundle Item Groups (Priority: P0)

As a responsible user or reviewer, I need to validate whether a bundle item or bundle item group satisfies the checklist item
requirements. **Control checklists are OPTIONAL** - validation approach depends on customer context and configuration. If a control
checklist is configured for the checklist item, I must evaluate it by marking each control with one of three status values: Verified
(check passed), Not verified (check failed), or Skipped (not applicable). All controls must be explicitly marked before I can set
the final validation status. If any control is marked "Not verified", the validation status must be set to Rejected. If all controls
are either Verified or Skipped, I can set the validation status to Valid or Incomplete. **If no control checklist is configured**, I
perform validation through direct operator judgment and set the validation status (Valid/Rejected/Incomplete) directly without any
structured guidance. The validation status applies to the bundle item or bundle item group itself, and when linked to multiple
checklist items, the same validation status applies to all those links.

**Why this priority**: Validation is what determines checklist completion. This quality control step ensures only proper
documents are accepted.

**Independent Test**: Can be fully tested by taking a bundle item or bundle item group, marking all controls in its control
checklist with status values, and setting its validation status based on control results. Delivers value by enabling quality
control on received documents.

**Acceptance Scenarios**:

1. **Given** a bundle item or bundle item group is linked to a checklist item with a control checklist configured, **When** the responsible user marks all controls as either "Verified" or "Skipped", and sets the validation status to "Valid", **Then** the bundle item or bundle item group status changes to "Valid" and all checklist items it is linked to show as satisfied.
2. **Given** a bundle item or bundle item group is linked to a checklist item with controls configured, **When** the responsible user marks at least one control as "Not verified", **Then** the system enforces that validation status must be set to "Rejected" (user cannot set it to Valid), and the rejection reason is recorded and visible.
3. **Given** a bundle item or bundle item group is linked to a checklist item with controls configured, **When** the responsible user marks some controls as "Verified" and others as "Skipped" (none as "Not verified"), **Then** the user can set validation status to Valid or Incomplete.
4. **Given** a bundle item or bundle item group is linked to a checklist item with NO control checklist configured (controls are optional), **When** the responsible user validates the document, **Then** the user performs validation through their own judgment and can set validation status to Valid, Rejected, or Incomplete directly without any structured control evaluation or guidance.
5. **Given** a bundle item or bundle item group was previously marked as Rejected, **When** a new bundle item or group is linked to the same checklist item and validated as Valid, **Then** the new item/group validation is independent while the previous Rejected item/group remains in history.
6. **Given** a bundle item or bundle item group is linked to multiple checklist items, **When** I set its validation status to Valid, **Then** the same Valid status applies to all checklist items it is linked to (validation is at the bundle item/group level, not per link).
7. **Given** a bundle item or bundle item group has a control checklist with 5 controls, **When** the responsible user attempts to set validation status, **Then** the system requires all 5 controls to be explicitly marked with a status (Verified, Not verified, or Skipped) before allowing validation completion.

---

### User Story 0d - Complete Checklist When All Items Validated (Priority: P0)

As a checklist owner, I need the system to automatically mark a checklist as completed when all applicable items have
at least one bundle item or bundle item group with Valid validation status. Items marked as N/A are excluded from this calculation.

**Why this priority**: Automatic completion tracking is the outcome of the workflow. While important, it depends on all
previous stories being functional.

**Independent Test**: Can be fully tested by creating a checklist, marking some items as N/A, linking and validating
bundle items or bundle item groups for remaining items, and verifying the checklist status changes to completed.

**Acceptance Scenarios**:

1. **Given** a checklist with 3 required items where 2 have at least one bundle item or bundle item group with Valid status and 1 is marked N/A, **When** the second required item receives a bundle item or bundle item group with Valid validation status, **Then** the checklist status automatically changes to "completed".
2. **Given** a completed checklist, **When** a previously Valid bundle item or bundle item group validation status is changed to Rejected, **Then** the checklist status reverts to "in progress".
3. **Given** a checklist with all applicable items having at least one Valid bundle item or bundle item group, **When** I view the checklist, **Then** I can see the completion timestamp and all valid bundle items and bundle item groups.

---

### User Story 1 - Create and Configure Checklist (Priority: P1)

As any user with appropriate permissions, I need to create a checklist from a template to define all required documents
and controls for a specific workflow (e.g., loan application, compliance review, onboarding process). I can create the
checklist proactively at any business point (e.g., when initiating a credit application, before documents arrive) or the
default user can create it on-demand during bundle triage. When creating the checklist, I can optionally attach multiple
parties (supplier, vendor, applicant, client, transporter, etc.), each with a dedicated role to track document sources. The
template provides default items, and I can customize by adding items from the organization library or creating manual items.
I can also mark default items as "N/A" if not applicable. Additionally, I can optionally assign a party with a role to
individual checklist items for item-specific party tracking.

**Why this priority**: Without a checklist definition, no document collection workflow can exist. This is the
foundational capability that enables all other features. Supports both proactive creation (business-driven) and on-demand
creation (triage-driven) workflows.

**Independent Test**: Can be fully tested by creating a checklist from a template, adding library/manual items, marking
a default item as N/A, and verifying all items are persisted correctly. Delivers value by allowing bundle owners to
define their document requirements.

**Acceptance Scenarios**:

1. **Given** I am a user with appropriate permissions (e.g., loan officer, compliance officer), **When** I create a new checklist proactively by selecting a template (e.g., at credit application initiation, before documents arrive), **Then** the checklist is created with all default items from the template, and I am set as both owner and responsible.
   1a. **Given** I am creating a new checklist proactively, **When** I optionally attach multiple parties from the imported party list, each with a specific role (e.g., Party: "ABC Supplier", Role: "Primary Supplier"; Party: "XYZ Transport", Role: "Transporter"), **Then** all parties and their roles are associated with the checklist for tracking document sources.
   1b. **Given** I am creating or editing a checklist, **When** I optionally assign a party with a role to individual checklist items (e.g., Item "Invoice" → Party: "ABC Supplier", Role: "Client"), **Then** the item-level party assignment is saved independently from checklist-level parties.
2. **Given** I am the default user creating a checklist during bundle triage, **When** I select a template and specify an owner (can be myself or another user), **Then** the checklist is created with all default items, and the specified user is set as both owner and responsible.
   2a. **Given** I am the default user creating a checklist, **When** I optionally attach one or more parties with roles if I can identify document sources, **Then** the parties and roles are associated with the checklist.
   2b. **Given** I am the checklist owner, **When** I add, remove, or modify parties and their roles at the checklist or checklist item level, **Then** the party information is updated immediately without restrictions (parties are always modifiable).
3. **Given** I have a checklist with default items, **When** I add an item from the organization library, **Then** the
   library item is added to the checklist as a non-default item.
4. **Given** I have a checklist, **When** I create a manual item with a name and description, **Then** the manual item
   is added to the checklist as a non-default item.
5. **Given** I have a checklist with a default item, **When** I mark that item as "N/A", **Then** the item shows as not
   applicable and is excluded from completion calculation.
6. **Given** I have a checklist with a default item marked N/A, **When** I attempt to delete that item, **Then** the
   system prevents deletion (default items cannot be deleted).
7. **Given** I have a checklist with a manually added item (library or manual), **When** I delete that item, **Then**
   the item is removed from the checklist.

---

### User Story 1a - Manage Checklist Templates (Priority: P1)

As a user with BF-CONFIG permission, I need to create and manage checklist templates that define default items for specific workflows.
Templates are composed by selecting items from the organization-wide item library. These templates serve as the starting
point when users create checklists.

**Why this priority**: Templates are required before users can create checklists. This is foundational
infrastructure.

**Independent Test**: Can be fully tested by a user with BF-CONFIG permission creating a template by selecting items from the library.
Delivers value by standardizing document requirements across the organization.

**Acceptance Scenarios**:

1. **Given** I am a user with BF-CONFIG permission, **When** I create a new checklist template, **Then** I can select items from the
   item library to include as default items in the template.
2. **Given** I am a user with BF-CONFIG permission creating a template, **When** I select library items, **Then** those items become the
   default items for checklists created from this template.
3. **Given** I am a user with BF-CONFIG permission, **When** I edit an existing template to add or remove library items, **Then** the
   changes are saved (existing checklists created from this template are not affected).
4. **Given** I am a user without BF-CONFIG permission, **When** I attempt to create or edit a template, **Then** the
   system denies access.

---

### User Story 1b - Manage Item Library (Priority: P1)

As a user with BF-CONFIG permission, I need to manage an organization-wide item library that users can use to add pre-defined
items to their checklists.

**Why this priority**: The item library provides standardized items beyond template defaults, enabling consistency
across checklists.

**Independent Test**: Can be fully tested by a user with BF-CONFIG permission adding items to the library and a checklist owner selecting
from it.

**Acceptance Scenarios**:

1. **Given** I am a user with BF-CONFIG permission, **When** I add a new item to the organization library, **Then** the item becomes
   available for all users to add to their checklists.
2. **Given** I am a checklist owner creating a checklist, **When** I browse the item library, **Then** I can see all
   available library items and add selected ones to my checklist.

---

### User Story 1ba - Define Control Checklists for Template Items (Priority: P1)

As a user with BF-CONFIG permission, I need to optionally define control checklists for each item in a checklist template to provide
structured validation guidance. For each control, I specify the Type (from predefined list or free text), Description (what
verification is required), and optionally a Comment. When users create checklists from the template, these control definitions are
inherited by the corresponding checklist items and used to guide validation of bundle items or bundle item groups. **Control
checklists are completely OPTIONAL** - template items can have no controls configured, in which case validation will be performed
through direct operator judgment without structured guidance.

**Why this priority**: Control checklists enable standardized quality validation across all checklists created from a template,
ensuring consistent document verification criteria organization-wide. However, they remain optional to accommodate different customer
contexts and validation approaches.

**Independent Test**: Can be fully tested by a BF-CONFIG user defining controls for a template item (selecting control types,
adding descriptions), creating a checklist from that template, and verifying the controls are inherited and available during
bundle item validation.

**Acceptance Scenarios**:

1. **Given** I am a user with BF-CONFIG permission editing a checklist template, **When** I define a control checklist for a template item, **Then** I can add multiple controls, each with a Type (selected from predefined control types or free text), Description, and optional Comment.
2. **Given** I am a user with BF-CONFIG permission defining a control, **When** I select the control Type, **Then** I can choose from the predefined list of control types (e.g., Coherency, Conformity, Authenticity) OR enter free text if the needed type is not available.
3. **Given** I am a user with BF-CONFIG permission, **When** I create a checklist template item without defining any controls, **Then** the template item is valid and validation will be performed through operator judgment without structured guidance (control checklists are completely optional).
4. **Given** a template item has a control checklist defined, **When** a user creates a checklist from that template, **Then** the control checklist is copied to the corresponding checklist item and will be used to guide bundle item/group validation.
   4a. **Given** a template item has NO control checklist defined, **When** a user creates a checklist from that template and validates bundle items, **Then** the operator can set validation status (Valid/Rejected/Incomplete) directly based on their own judgment without any structured control evaluation.
5. **Given** I am a user with BF-CONFIG permission, **When** I edit or delete controls in a template, **Then** the changes are saved (existing checklists created from this template are not affected; only new checklists inherit the updated controls).
6. **Given** I am a user without BF-CONFIG permission, **When** I attempt to define controls for template items, **Then** the system denies access.

---

### User Story 1c - Manage Bundle Item Group Types (Priority: P1)

As a user with BF-CONFIG permission, I need to manage a predefined list of bundle item group types (e.g., "CIN", "Passport", "Contract")
that users can select from when creating bundle item groups. This standardizes grouping naming across the organization
while allowing free text entry as a fallback for unexpected cases.

**Why this priority**: Standardized bundle item group types enable consistency in document grouping and improve
searchability and reporting.

**Independent Test**: Can be fully tested by a user with BF-CONFIG permission adding a group type to the library and a user with BF-MANAGE permission
selecting it when creating a bundle item group.

**Acceptance Scenarios**:

1. **Given** I am a user with BF-CONFIG permission, **When** I add a new bundle item group type (e.g., "CIN", "Passport") to the predefined list, **Then** the group type becomes available for users to select when creating bundle item groups.
2. **Given** I am a user with BF-CONFIG permission, **When** I edit or delete an existing bundle item group type, **Then** the changes are saved (existing bundle item groups using that type are not affected).
3. **Given** I am a user with BF-MANAGE permission creating a bundle item group, **When** I view available group type options, **Then** I can see the predefined list of bundle item group types to select from OR enter free text if the needed type is not available.
4. **Given** I am a user without BF-CONFIG permission, **When** I attempt to manage the bundle item group types list, **Then** the system denies access.

---

### User Story 1d - Manage Party Roles (Priority: P1)

As a user with BF-CONFIG permission, I need to manage a predefined list of party roles (e.g., "Primary Supplier", "Co-applicant",
"Guarantor", "Employer") that users can select from when attaching parties to checklists. This standardizes role naming
across the organization while allowing free text entry as a fallback for unexpected cases.

**Why this priority**: Standardized party roles enable consistency in tracking document sources and improve filtering
and reporting by party type.

**Independent Test**: Can be fully tested by a user with BF-CONFIG permission adding a party role to the list and a user selecting it
when creating a checklist with a party.

**Acceptance Scenarios**:

1. **Given** I am a user with BF-CONFIG permission, **When** I add a new party role (e.g., "Primary Supplier", "Guarantor") to the predefined list, **Then** the role becomes available for users to select when attaching parties to checklists.
2. **Given** I am a user with BF-CONFIG permission, **When** I edit or delete an existing party role, **Then** the changes are saved (existing checklists using that role are not affected).
3. **Given** I am creating a checklist and attaching a party, **When** I view available party role options, **Then** I can see the predefined list of party roles to select from OR enter free text if the needed role is not available.
4. **Given** I am a user without BF-CONFIG permission, **When** I attempt to manage the party roles list, **Then** the system denies access.

---

### User Story 1da - Manage Control Types (Priority: P1)

As a user with BF-CONFIG permission, I need to manage a predefined list of control types (e.g., "Coherency", "Conformity", "Authenticity", "Format") that users can select from when defining controls in checklist templates. This standardizes control classification across the organization while allowing free text entry as a fallback for unexpected cases.

**Why this priority**: Standardized control types enable consistency in control definition and improve organization and reporting of validation criteria.

**Independent Test**: Can be fully tested by a user with BF-CONFIG permission adding a control type to the list and another BF-CONFIG user selecting it when defining controls for a checklist template item.

**Acceptance Scenarios**:

1. **Given** I am a user with BF-CONFIG permission, **When** I add a new control type (e.g., "Coherency", "Authenticity") to the predefined list, **Then** the control type becomes available for users to select when defining controls for checklist template items.
2. **Given** I am a user with BF-CONFIG permission, **When** I edit or delete an existing control type, **Then** the changes are saved (existing controls using that type are not affected).
3. **Given** I am a user with BF-CONFIG permission defining controls for a checklist template item, **When** I view available control type options, **Then** I can see the predefined list of control types to select from OR enter free text if the needed type is not available.
4. **Given** I am a user without BF-CONFIG permission, **When** I attempt to manage the control types list, **Then** the system denies access.

---

### User Story 1db - Manage Configuration Versions (Priority: P1)

As a user with BF-CONFIG permission, I need to create, export, and import configuration versions to manage system configuration across environments (dev, test, production) and maintain configuration history. A configuration version captures all configuration elements (templates, item library, party roles, bundle item group types, control types) as a JSON file. Only one version can be active at a time - when I activate a new version, all previous versions become inactive. New checklists created after activation use the active version's configuration, while existing checklists continue using their original version. This ensures configuration consistency for new workflows while preserving ongoing work.

**Why this priority**: Configuration versioning enables controlled deployment across environments, configuration testing before production release, and the ability to maintain different configuration sets for different business contexts. Critical for enterprise deployments and change management.

**Independent Test**: Can be fully tested by a BF-CONFIG user creating a version, exporting it to JSON, importing the JSON in another environment, activating it, and verifying new checklists use the new configuration while existing checklists use their original version.

**Acceptance Scenarios**:

1. **Given** I am a user with BF-CONFIG permission, **When** I create a new configuration version with a unique name (e.g., "Production Q1 2025"), **Then** the system captures all current configuration (templates, item library, party roles, bundle item group types, control types) and creates a new inactive version.
2. **Given** I have created a configuration version, **When** I export it, **Then** the system generates a JSON file containing all configuration elements from that version.
3. **Given** I am a user with BF-CONFIG permission in a target environment, **When** I import a JSON configuration file and specify a unique version name, **Then** the system creates a new inactive version with the imported configuration.
4. **Given** I have multiple inactive versions, **When** I activate a specific version, **Then** that version becomes the single active version, all other versions become inactive, and all new checklists created afterward use the active version's configuration.
5. **Given** a new version is activated, **When** I view existing checklists created before activation, **Then** those checklists continue using their original version's configuration (version immutability for existing workflows).
6. **Given** I activate a new version, **When** the import completely replaces the active configuration, **Then** new templates, items, roles, and types from the imported version are available, and configuration not present in the import is no longer available for new checklists.
7. **Given** I am a user without BF-CONFIG permission, **When** I attempt to create, export, import, or activate configuration versions, **Then** the system denies access.

---

### User Story 1e - Manage User Permission Roles (Priority: P1)

As a user with BF-CONFIG permission, I need to assign and revoke permission roles (BF-CONFIG, BF-SORT, BF-MANAGE, BF-VALIDATE, BF-OWNER, BF-RESPONSIBLE) to users in the system. Users can have multiple roles simultaneously with additive permissions. The system enforces validation rules such as requiring BF-MANAGE when assigning BF-RESPONSIBLE, and validates permission prerequisites before allowing users to be designated as checklist owner or responsible.

**Why this priority**: Permission management is foundational for all access control in the system. Without proper role assignment, users cannot perform their designated functions.

**Independent Test**: Can be fully tested by a BF-CONFIG user assigning multiple roles to a user, attempting to assign BF-RESPONSIBLE without BF-MANAGE (should fail), and verifying permission prerequisites are enforced.

**Acceptance Scenarios**:

1. **Given** I am a user with BF-CONFIG permission, **When** I assign one or more permission roles to a user, **Then** the user inherits permissions from all assigned roles (additive/cumulative).
2. **Given** I am a user with BF-CONFIG permission, **When** I attempt to assign BF-RESPONSIBLE to a user without BF-MANAGE, **Then** the system prevents the assignment and displays an error message.
3. **Given** I am a user with BF-CONFIG permission, **When** I attempt to remove BF-MANAGE from a user who has BF-RESPONSIBLE, **Then** the system prevents the removal and displays an error message.
4. **Given** a user lacks BF-OWNER permission, **When** attempting to designate them as checklist owner, **Then** the system prevents the assignment and requires BF-OWNER permission first.
5. **Given** a user lacks BF-RESPONSIBLE or BF-MANAGE permission, **When** attempting to designate them as checklist responsible, **Then** the system prevents the assignment and requires both permissions first.
6. **Given** I am a user without BF-CONFIG permission, **When** I attempt to assign or revoke any permission roles, **Then** the system denies access.
7. **Given** I am a user with BF-CONFIG permission, **When** I view a user's profile, **Then** I can see all currently assigned permission roles for that user.

---

### User Story 2 - Reassign Checklist Responsible (Priority: P2)

As a checklist owner, I need to reassign the responsible user for a checklist when workload changes or the original
responsible is unavailable.

**Why this priority**: Operational flexibility - enables continuity when personnel changes occur.

**Independent Test**: Can be fully tested by owner reassigning responsible and verifying the new responsible can
validate bundle items and bundle item groups.

**Acceptance Scenarios**:

1. **Given** I am the checklist owner, **When** I reassign the responsible to another user, **Then** the new user
   becomes the responsible and can validate bundle items and bundle item groups.
2. **Given** I am the checklist responsible (but not owner), **When** I attempt to reassign the responsible, **Then**
   the system denies the action.

---

### User Story 3 - Reallocate Task to Another User (Priority: P3)

As a task assignee, checklist owner, or checklist responsible, I need to reallocate a task to another user when workload
needs to be distributed or the current assignee is unavailable.

**Why this priority**: Operational flexibility - allows task delegation without changing checklist responsibility.

**Independent Test**: Can be fully tested by reallocating a task and verifying the new assignee receives it.

**Acceptance Scenarios**:

1. **Given** I am the current task assignee, **When** I reallocate the task to another user, **Then** the task is
   assigned to the new user and removed from my task list.
2. **Given** I am the checklist owner or responsible, **When** I reallocate a task assigned to someone else, **Then**
   the task is reassigned to the new user.
3. **Given** I am a user with no relationship to the checklist, **When** I attempt to reallocate a task, **Then** the
   system denies the action.

---

### User Story 4 - Create Reminder Task (Priority: P4)

As a checklist owner or responsible, I need to create reminder tasks with due dates to track follow-up actions related
to the checklist. When the due date is reached, the system notifies the assignee and escalates to the owner if not
actioned.

**Why this priority**: Enables proactive workflow management and deadline tracking.

**Independent Test**: Can be fully tested by creating a reminder, waiting for due date, and verifying notifications and
escalation.

**Acceptance Scenarios**:

1. **Given** I am the checklist owner or responsible, **When** I create a reminder task with a description and due date,
   **Then** the reminder is created and assigned to a specified user.
2. **Given** a reminder task reaches its due date, **When** the assignee has not actioned it, **Then** the system sends
   a notification to the assignee.
3. **Given** a reminder notification was sent, **When** the assignee still does not action it within a grace period,
   **Then** the system escalates by notifying the checklist owner.
4. **Given** I am neither owner nor responsible, **When** I attempt to create a reminder task, **Then** the system
   denies the action.

---

### User Story 5 - Create Send Email Task (Priority: P5)

As a checklist owner or responsible, I need to create email tasks to communicate with stakeholders about the checklist.
I can compose a custom email or select from administrator-managed email templates.

**Why this priority**: Enables structured communication within the document collection workflow.

**Independent Test**: Can be fully tested by creating an email task with and without a template.

**Acceptance Scenarios**:

1. **Given** I am the checklist owner or responsible, **When** I create a send email task, **Then** I can either compose
   a custom email or select from available email templates.
2. **Given** I select an email template, **When** I create the task, **Then** the template content is pre-filled and I
   can customize before sending.
3. **Given** I compose a custom email (no template), **When** I create the task, **Then** I can enter subject, body, and
   recipients manually.
4. **Given** I am neither owner nor responsible, **When** I attempt to create a send email task, **Then** the system
   denies the action.

---

### User Story 5a - Manage Email Templates (Priority: P5)

As a user with BF-CONFIG permission, I need to manage email templates that users can select when creating send email tasks.

**Why this priority**: Provides standardized communication options for common scenarios.

**Independent Test**: Can be fully tested by a user with BF-CONFIG permission creating a template and a user selecting it.

**Acceptance Scenarios**:

1. **Given** I am a user with BF-CONFIG permission, **When** I create an email template with name, subject, and body, **Then** the
   template is available for users to select when creating send email tasks.
2. **Given** I am a user with BF-CONFIG permission, **When** I edit or delete an existing email template, **Then** the changes are
   saved (pending tasks using the template are not affected).
3. **Given** I am a user without BF-CONFIG permission, **When** I attempt to create or edit email templates, **Then** the system denies
   access.

---

### Edge Cases

- What happens when a bundle item is deleted after being linked to a checklist item? The link should be marked as
  "document removed" and the item reverts to unsatisfied.
- What happens when a checklist item is removed from an active checklist? Default items cannot be deleted (only marked
  N/A); manually added items with existing links cannot be removed until links are cleared.
- How does the system handle duplicate bundle item links to the same checklist item? Multiple bundle items can be linked
  to the same checklist item, but only one needs to be validated for the item to be satisfied.
- What happens when a user tries to validate a bundle item they did not upload? The responsible user can validate
  documents regardless of who uploaded them.
- What happens when an item is changed from N/A back to required? The item becomes part of the completion calculation
  again and must have a validated link for checklist completion.
- What happens if a task is submitted to a checklist with no responsible assigned? The task remains with the current user until a responsible is assigned to the checklist or the task is transferred to another user.
- What happens to pending tasks when the checklist responsible is reassigned? Existing tasks assigned to the previous
  responsible are reassigned to the new responsible.
- What happens if a triage user tries to submit a task without selecting a checklist? The system prevents submission
  until a checklist is selected.
- What happens if the integration service reports extraction failures for some documents? Bundle Flow creates bundle item records with "extraction failed" status based on the metadata received and alerts the user; other valid document IDs are processed normally.
- What happens if multiple bundles arrive for the same checklist? Each bundle creates a separate task; the checklist
  accumulates document links from all bundles over time.
- What happens if a user with BF-VALIDATE permission marks a task complete with unprocessed bundle items? System warns about unprocessed items but allows completion with explicit confirmation (user decision).
- What happens if a reminder is never actioned even after escalation? The reminder remains open; system may send periodic
  re-escalation notifications.
- What happens if an email template is deleted while tasks reference it? Pending tasks retain a copy of the template
  content at creation time.
- What happens if a task is reallocated while in progress? The new assignee receives the task in its current state; no
  data is lost.
- What happens if the checklist owner is the same as the reminder assignee during escalation? System still logs the
  escalation for audit purposes but does not send duplicate notification.
- What happens if a user tries to add a bundle item to a validated bundle item group? System prevents the operation and displays an error; user must first remove an item (triggering status reset to "Attached") before adding new items.
- What happens to a bundle item group when all its items are removed? The group remains with status "Attached" and can be deleted or have new items added to it.
- What happens to bundle items when their bundle item group is deleted? All bundle items are released from the group and their status resets to "Unprocessed"; they can then be directly linked to checklist items or added to other groups.
- What happens to checklist item links when a bundle item group is deleted? The links between the deleted group and checklist items are removed; the checklist item status recalculates based on remaining links.
- What happens when a bundle is associated with the wrong checklist and needs to be corrected? Either the default user or checklist responsible can initiate rollback at any time; the original task is closed, bundle items reset to "Unprocessed", bundle item groups are preserved but unlinked, and a new task is created for the default user to select the correct checklist.
- What happens to work already done by the responsible if rollback occurs after they started processing? All associations and validation work is discarded; bundle items reset to "Unprocessed" and bundle item groups are unlinked but preserved for reuse.
- What happens to the checklist after rollback? The checklist remains intact; only the bundle linkage and item associations are removed. The checklist can be used for future bundles.
- Can rollback occur if the checklist has multiple bundles? Yes, rollback only affects the specific bundle being rolled back; other bundles linked to the same checklist are unaffected.
- What happens if the default user creates a checklist during triage and specifies a non-existent user as owner? System validates the specified owner exists before allowing checklist creation.
- Can a checklist exist without any bundles linked to it? Yes, checklists created proactively (e.g., for credit application) exist before any bundles arrive and remain valid with zero bundles.
- What happens if the specified owner (when default user creates checklist) is unavailable or inactive? System validates the owner is active before allowing checklist creation; if inactive, default user must select a different owner.
- Can the default user select from ALL existing checklists or only specific ones? System may filter available checklists based on business rules (e.g., checklist status, workflow type, organizational scope).
- What happens if a party referenced by a checklist is deleted/deactivated in the external CRM? The checklist retains the party ID and name as they were at import time; system may flag stale party references.
- Can multiple checklists reference the same party? Yes, a business case may have multiple checklists for different document sets from the same party (e.g., Supplier A provides both technical docs and financial docs in separate checklists).
- What happens if party import fails? System continues to function; users can create checklists without party attachment; administrator is notified of import failure.
- Can a checklist party be removed (set to null) after it was set? Yes, owner can remove party before any bundles are associated, same rules as changing party.
- What happens when filtering/searching checklists by party if some checklists have no party? Checklists without party are excluded from party-specific filters; included in "all checklists" view.

## Requirements *(mandatory)*

### Functional Requirements

**Bundle Arrival & Task Creation:**

- **FR-028**: System MUST automatically create a task when a bundle (list of document IDs with metadata) arrives via integration.
- **FR-028a**: System MUST NOT receive, store, or handle actual document files. Bundle Flow receives only document IDs and metadata from the integration service.
- **FR-029**: System MUST receive document IDs and metadata for documents that have been extracted from zip files by an external integration service.
- **FR-030**: System MUST create a bundle item record for each document ID with initial status "Unprocessed", storing the document ID and metadata locally.
- **FR-030a**: System MUST automatically transition bundle item status from "Unprocessed" to "Attached" when the bundle item is linked to a checklist item or added to a bundle item group.
- **FR-031**: System MUST attach all bundle item records (document IDs with metadata) to the automatically created task.
- **FR-032**: System MUST create new bundle tasks in an unassigned state and place them in the triage pool.
- **FR-033**: System MUST allow any user with BF-SORT permission to view all unassigned tasks in the triage pool.
- **FR-033a**: System MUST allow any user with BF-SORT permission to manually claim unassigned tasks from the triage pool.
- **FR-034**: System MUST assign a claimed task to the claiming user and remove it from the unassigned pool upon claim.
- **FR-035**: System MUST handle cases where the integration service indicates document extraction failures in the bundle metadata, marking those bundle items with appropriate error status and continuing with valid items.
- **FR-035a**: System MUST support the following file types for bundle items: PDF (.pdf), Office documents (.docx, .xlsx), images (.jpg, .png, .tiff), and text files (.txt, .csv). PowerPoint files (.ppt, .pptx) are explicitly excluded.
- **FR-035b**: System MUST explicitly block executable files and scripts (e.g., .exe, .bat, .sh, .js, .vbs) from being processed as bundle items for security reasons.
- **FR-035c**: System MUST allow users with BF-CONFIG permission to optionally configure file type restrictions at the control checklist level (e.g., specific checklist items can require only PDF or only images). File type compliance is verified manually by validators as part of the control checklist evaluation process.

**Bundle Triage (BF-SORT Users):**

- **FR-036**: System MUST allow users with BF-SORT permission to either select an existing checklist OR create a new checklist from template to associate with a bundle.
- **FR-036a**: System MUST display a list of existing checklists available for selection during triage.
- **FR-036b**: System MUST require the triage user to specify the owner when creating a new checklist during triage (can be themselves or another user).
- **FR-036c**: System MUST set the specified user as both owner and responsible when the triage user creates a new checklist.
- **FR-037**: System MUST prevent users with only BF-SORT permission from performing actions beyond attaching bundle to checklist (cannot associate bundle items to checklist items without BF-MANAGE).
- **FR-038**: System MUST mark the attach-to-checklist action as complete when the triage user confirms checklist selection.
- **FR-039**: System MUST progress the task to the next action (attach items to checklist items) after the attach-to-checklist action is complete, keeping the task assigned to the same user unless transferred.
- **FR-040**: System MUST enforce that ONE bundle (and its task) can only be linked to exactly ONE checklist. Bundle items cannot be shared across different checklists.
- **FR-041**: System MUST allow multiple different bundles/tasks to be linked to the same checklist over time (e.g., receiving multiple document batches for the same loan application).

**Bundle-Checklist Rollback (Error Correction):**

- **FR-041a**: System MUST allow the triage user (user with BF-SORT who claimed the task) or the checklist responsible to initiate rollback to unlink a bundle from a checklist.
- **FR-041b**: System MUST allow rollback at any time, even after bundle items have been associated to checklist items.
- **FR-041c**: System MUST close the original task when rollback is initiated.
- **FR-041d**: System MUST create a new unassigned task and place it back in the triage pool when rollback occurs.
- **FR-041e**: System MUST reset all bundle items to "Unprocessed" status when rollback occurs.
- **FR-041f**: System MUST remove all bundle item associations and bundle item group associations to the checklist when rollback occurs.
- **FR-041g**: System MUST preserve bundle item groups (not delete them) when rollback occurs, making them available for reuse with the correct checklist.
- **FR-041h**: System MUST keep the checklist intact (not delete it) when rollback occurs; only the bundle linkage and item associations are removed.
- **FR-041i**: System MUST record rollback events in the audit log including who initiated the rollback and when.

**Bundle Item Processing (Responsible User):**

- **FR-042**: System MUST allow the checklist responsible to associate bundle items with one or more checklist items.
- **FR-043**: System MUST allow the checklist responsible to tag bundle items as "Not relevant" without item association.
- **FR-044**: System MUST treat "Not relevant" bundle items as acknowledged/processed but not required for checklist
  completion.
- **FR-045**: System MUST display "Not relevant" bundle items in a separate section of the checklist for audit purposes.
- **FR-046**: System MUST track each bundle item's validation status independently (Unprocessed, Attached, Valid, Rejected, Incomplete, Not relevant).
- **FR-047**: System MUST allow the responsible user to explicitly mark a task as complete.
- **FR-048**: System MUST warn (but allow with confirmation) when marking a task complete with unprocessed bundle items.

**Checklist Creation & Configuration:**

- **FR-001**: System MUST allow any user with appropriate permissions to create checklists proactively by selecting from available templates (e.g., loan officer creating checklist when initiating credit application, before documents arrive).
- **FR-001a**: System MUST allow the default user to create checklists on-demand during bundle triage by selecting from available templates.
- **FR-002**: System MUST populate new checklists with all default items defined in the selected template.
- **FR-003**: System MUST set the creating user as both owner and responsible by default when creating checklists proactively (not during triage).
- **FR-003a**: System MUST set the user specified by the default user as both owner and responsible when checklists are created during triage.
- **FR-004**: System MUST allow checklist owners to add items from the organization-wide item library to their checklists.
- **FR-005**: System MUST allow checklist owners to create manual items with name and description.
- **FR-006**: System MUST allow checklist owners to mark default items as "N/A" (not applicable).
- **FR-007**: System MUST prevent deletion of default items (from template); only N/A marking is allowed.
- **FR-008**: System MUST allow deletion of manually added items (library or manual) that have no existing document
  links.

**Party Management:**

- **FR-008a**: System MUST import parties from external CRM systems with ID and Name fields only.
- **FR-008b**: System MUST allow users to optionally attach one or more parties with their associated attachment roles to a checklist when creating the checklist (both proactive creation and triage creation).
- **FR-008c**: System MUST require a party role when attaching a party to a checklist.
- **FR-008d**: System MUST allow party role selection from a predefined list managed by users with BF-CONFIG permission, with free text fallback.
- **FR-008e**: System MUST allow checklist owners to change parties (add, remove, or modify party/role) before any bundles are associated with the checklist.
- **FR-008f**: System MUST prevent changing parties on a checklist once bundles are associated to maintain data integrity.
- **FR-008g**: System MUST display all attached parties information (party names and roles) on the checklist for users with access.
- **FR-008h**: System MUST allow users with BF-CONFIG permission to manage the predefined list of party roles (create, edit, delete).
- **FR-008i**: System MUST NOT allow manual creation of parties within Bundle Flow (parties are imported only).

**Template & Library Management (BF-CONFIG Permission):**

- **FR-009**: System MUST allow users with BF-CONFIG permission to create, edit, and delete checklist templates.
- **FR-010**: System MUST allow users with BF-CONFIG permission to compose templates by selecting items from the item library (templates
  reference library items, not separate definitions).
- **FR-011**: System MUST allow users with BF-CONFIG permission to manage the organization-wide item library.
- **FR-011a**: System MUST allow users with BF-CONFIG permission to define control checklists as part of each item library entry.
- **FR-012**: System MUST restrict template and library management to users with BF-CONFIG permission only.
- **FR-049**: System MUST require all template items to exist in the item library (no standalone template items).
- **FR-088**: System MUST allow bundle owners to define control checklists when creating manual checklist items (not from library).
- **FR-089**: System MUST apply no control checklist validation if a manual checklist item was created without defining controls (validation allowed without completing control checklist).

**Document Linking:**

- **FR-013**: System MUST allow the checklist responsible to create links between bundle items (or bundle item groups) and checklist items.
- **FR-014**: System MUST allow one bundle item to be linked to multiple checklist items (the bundle item's single validation status applies to all links).
- **FR-015**: System MUST track validation states for bundle items and bundle item groups: Attached, Valid, Rejected (with reason), Incomplete.
- **FR-016**: System MUST support multiple bundle items linked to the same checklist item; any one Valid bundle item (or bundle item group) satisfies the checklist item requirement for completion purposes.

**Validation:**

- **FR-017**: System MUST allow the responsible user to set bundle item or bundle item group validation status to Valid, Rejected (with reason), or Incomplete after completing the control checklist.
- **FR-018**: System MUST record rejection reasons for Rejected bundle items and bundle item groups.
- **FR-019**: System MUST maintain a single validation status per bundle item or bundle item group; when a bundle item is linked to multiple checklist items, the same validation status applies to all those links.

**Concurrency Control:**

- **FR-019a**: System MUST implement optimistic locking for all concurrent operations on shared entities (checklists, bundle items, bundle item groups, tasks, validation status changes).
- **FR-019b**: System MUST detect conflicts when two users attempt to modify the same entity simultaneously.
- **FR-019c**: System MUST require users to refresh their view and retry their operation when a conflict is detected.
- **FR-019d**: System MUST display a clear error message indicating which user made the conflicting change and when.

**Bundle Item Groups:**

- **FR-075**: System MUST allow the checklist responsible to manually create Bundle Item Groups by grouping multiple bundle items during the association workflow.
- **FR-075a**: System MUST assign initial status "Attached" to bundle item groups when they are linked to checklist items.
- **FR-075b**: System MUST allow one bundle item to belong to multiple Bundle Item Groups simultaneously.
- **FR-075c**: System MUST allow the responsible user to name bundle item groups by selecting from a predefined list of bundle item group types.
- **FR-075d**: System MUST allow the responsible user to enter free text for the bundle item group name if the needed name is not available in the predefined list.
- **FR-075e**: System MUST allow users with BF-CONFIG permission to manage the list of predefined bundle item group types (create, edit, delete).
- **FR-076**: System MUST make grouping optional; users can associate bundle items directly to checklist items OR group them first.
- **FR-077**: System MUST prevent bundle items that are in any Bundle Item Group from being directly associated to checklist items (only the groups can be associated).
- **FR-077a**: System MUST prevent bundle items that have any direct checklist item links from being added to any Bundle Item Group.
- **FR-077b**: System MUST block the operation when a user attempts to add a bundle item with existing checklist item links to a Bundle Item Group (user must unlink first).
- **FR-077c**: System MUST block the operation when a user attempts to link a bundle item that belongs to any Bundle Item Group directly to a checklist item (user must remove from all groups first).
- **FR-078**: System MUST allow bundle item groups to be associated with checklist items.
- **FR-079**: System MUST maintain independent validation status for each bundle item group (Attached, Valid, Rejected, Incomplete).
- **FR-080**: System MUST NOT automatically inherit or calculate bundle item group status from its contained items.
- **FR-080a**: System MUST allow users to remove bundle items from Bundle Item Groups at any time regardless of current group validation status.
- **FR-080b**: System MUST automatically reset the Bundle Item Group's validation status to "Attached" immediately when any bundle item is removed from the group.
- **FR-080c**: System MUST require full re-validation (completing control checklist again) after any bundle item is removed from a group before a new validation status can be set.
- **FR-080d**: System MUST preserve previous validation history in audit logs when group composition changes and status is reset.
- **FR-080e**: System MUST allow users to add bundle items to a Bundle Item Group only if the group's validation status is "Attached".
- **FR-080f**: System MUST prevent adding bundle items to a Bundle Item Group that has been validated (status is Valid, Rejected, or Incomplete).
- **FR-080g**: System MUST retain empty Bundle Item Groups (when all items are removed) along with their checklist item links; groups are not automatically deleted.
- **FR-080h**: System MUST set empty Bundle Item Group status to "Attached" when the last item is removed.
- **FR-080i**: System MUST allow users to manually delete Bundle Item Groups (including empty groups).
- **FR-080j**: System MUST allow users to add new bundle items to empty Bundle Item Groups (subject to the "Attached" status requirement in FR-080e).
- **FR-080k**: System MUST release all bundle items from a Bundle Item Group when the group is deleted.
- **FR-080l**: System MUST reset the status of released bundle items to "Unprocessed" when their Bundle Item Group is deleted.
- **FR-080m**: System MUST allow released bundle items (after group deletion) to be directly linked to checklist items or added to other Bundle Item Groups.
- **FR-081**: System MUST allow users with BF-CONFIG permission to **optionally** define control checklists for each item in a checklist template. Defining controls is not mandatory and depends on customer context and validation requirements.
- **FR-081a**: When a checklist is created from a template, the system MUST copy the control checklist definitions from the template items to the corresponding checklist items (if controls were defined; otherwise the checklist item has no controls).
- **FR-082**: **Control checklists are COMPLETELY OPTIONAL.** Validation can be performed with or without controls depending on customer context and configuration. If a control checklist is configured for the checklist item (inherited from the template), the system presents it to guide validation when a user validates bundle items or bundle item groups linked to that checklist item.
- **FR-082a**: If NO control checklist is configured for the checklist item (controls are optional), the system MUST allow users to perform validation through direct operator judgment and set the validation status (Valid/Rejected/Incomplete) directly without any structured control evaluation or guidance.
- **FR-083**: System MUST display the control checklist (inherited from the template) when a user validates either a bundle item group or an individual bundle item linked to that checklist item, ONLY if a control checklist is configured. If no controls are configured, the system presents a direct validation interface without structured guidance.
- **FR-084**: System MUST allow users to mark each control with one of three status values: Verified, Not verified, or Skipped (ONLY when controls are configured; controls are optional).
- **FR-084a**: System MUST require all controls in a control checklist to be explicitly marked with a status (Verified, Not verified, or Skipped) before validation can be completed (ONLY applies when controls are configured).
- **FR-084b**: System MUST consider a control as satisfied when its status is either Verified OR Skipped (ONLY applies when controls are configured).
- **FR-084c**: System MUST treat a control as unsatisfied when its status is Not verified (ONLY applies when controls are configured).
- **FR-085**: System MUST require users to set the validation status (Valid/Rejected/Incomplete) after reviewing the control checklist (if configured) or through direct operator judgment (if no controls configured) for both bundle item groups and individual bundle items. Controls are optional - validation workflow adapts based on configuration.
- **FR-086**: System MUST enforce that if any control is marked "Not verified", the validation status must be set to Rejected (ONLY applies when controls are configured; controls are optional - if no controls exist, user sets status through direct judgment).
- **FR-086a**: System MUST allow users to set validation status to Incomplete when controls cannot yet be fully evaluated (e.g., missing information, pending external review, awaiting clarification) OR when validation without controls requires additional information.
- **FR-086b**: System MUST treat Incomplete status as distinct from Rejected: Incomplete indicates validation is pending further information; Rejected indicates validation was completed and failed. Applies whether using controls or direct operator judgment.
- **FR-087a**: System MUST record validation metadata (who, when, rejection reason) for bundle item groups same as bundle items.

**Control Structure & Attributes:**

**IMPORTANT: Controls are COMPLETELY OPTIONAL.** All requirements in this section apply ONLY when controls are configured. Customers can choose to use controls for structured validation guidance or perform validation through direct operator judgment without controls.

- **FR-087b**: System MUST define each control in a control checklist with the following attributes: Type, Status, Description, Comment (ONLY when controls are configured).
- **FR-087c**: System MUST allow control Type to be selected from a predefined list of control types (e.g., coherency, conformity, authenticity, format) managed by users with BF-CONFIG permission, with free text fallback for unexpected cases.
- **FR-087d**: System MUST allow users with BF-CONFIG permission to manage the list of predefined control types (create, edit, delete).
- **FR-087e**: System MUST track control Status with one of three values: Verified, Not verified, Skipped.
- **FR-087f**: System MUST allow each control to have a Description field explaining what verification is required.
- **FR-087g**: System MUST allow each control to have a Comment field for additional notes or context during validation.
- **FR-087h**: System MUST NOT track verification method (manual/visual vs automatic) as a separate control attribute.

**Completion Tracking:**

- **FR-020**: System MUST automatically update checklist status to "completed" when all applicable items (excluding N/A)
  have at least one Valid link (from either a bundle item or a bundle item group).
- **FR-021**: System MUST automatically revert checklist status from "completed" to "in progress" if any Valid link
  (bundle item or bundle item group) is changed to Rejected or Incomplete.
- **FR-022**: System MUST exclude N/A items from completion calculation entirely.
- **FR-023**: System MUST display checklist completion progress (e.g., "3 of 5 items valid", excluding N/A items
  from count).

**Checklist Item Global Status:**

- **FR-094**: System MUST track a global status for each checklist item: Not received, In progress, Received, Abandoned.
- **FR-095**: System MUST automatically set checklist item global status to "Not received" when no links (bundle items or bundle item groups) exist for the item.
- **FR-096**: System MUST automatically update checklist item global status to "In progress" when at least one link exists but NO linked bundle items/groups have Valid status (all are in "Attached", "Rejected", or "Incomplete" status).
- **FR-097**: System MUST automatically update checklist item global status to "Received" when AT LEAST ONE linked bundle item or bundle item group (associated with that checklist item) has Valid validation status. **Note**: Each checklist item represents one functional document type (e.g., "CIN", "Contract"); one valid document satisfying that requirement is sufficient.
- **FR-098**: System MUST allow users to manually set checklist item global status to "Abandoned" at any time.
- **FR-099**: System MUST NOT allow manual override of automatically calculated statuses (Not received, In progress, Received); only "Abandoned" can be set manually.
- **FR-100**: System MUST display the global status prominently for each checklist item in the UI.

**Ownership & Responsibility:**

- **FR-024**: System MUST allow checklist owners to edit checklist structure, delete the checklist, and reassign the
  responsible user.
- **FR-025**: System MUST restrict the responsible user to validation and bundle item processing operations only (cannot
  edit checklist structure or reassign).
- **FR-026**: System MUST maintain only one responsible user per checklist at any time.

**Audit & History:**

- **FR-027**: System MUST maintain an audit trail of all bundle item and bundle item group validation status changes including who made the change and when.

**Task Management:**

- **FR-050**: System MUST support two task categories: Bundle Processing tasks (auto-created) and Manual tasks ("Create Reminder", "Send Email").
- **FR-051**: System MUST automatically create ONE Bundle Processing task per bundle when it arrives, placed in the triage pool.
- **FR-052**: A Bundle Processing task MUST progress through three required actions: (1) attach bundle to checklist (requires BF-SORT), (2) attach bundle items to checklist items (requires BF-MANAGE), (3) process/validate links (requires BF-VALIDATE).
- **FR-052a**: System MUST allow only users with BF-VALIDATE permission to mark a Bundle Processing task as complete.
- **FR-052b**: System MUST mark a Bundle Processing task as complete only when a user with BF-VALIDATE permission explicitly marks it complete (not automatic).
- **FR-053**: System MUST allow checklist owner and responsible to create manual tasks (Reminder, Send Email).
- **FR-054**: System MUST allow task transfer to another user at any time by current assignee, checklist owner, or checklist responsible.
- **FR-055**: System MUST prevent users with no checklist relationship from transferring tasks.

**Task Actions and Permissions:**

- **FR-090**: System MUST enforce permission requirements for each action in a Bundle Processing task: BF-SORT for attaching to checklist, BF-MANAGE for attaching items to checklist items, BF-VALIDATE for processing/validating links.
- **FR-091**: System MUST display only available actions in the task UI based on the current user's permissions.
- **FR-092**: System MUST allow users to transfer the task to another user at any time if they lack the required permission for the next action.
- **FR-093**: System MUST prevent users without the required permission from performing an action (e.g., user without BF-VALIDATE cannot validate links).

**Reminder Tasks:**

- **FR-056**: System MUST allow reminder tasks to have a description, due date, and assignee.
- **FR-057**: System MUST send notification to assignee when reminder due date is reached.
- **FR-058**: System MUST escalate to checklist owner if reminder is not actioned within grace period after notification.

**Email Tasks:**

- **FR-059**: System MUST allow email tasks to be composed manually or using an email template.
- **FR-060**: System MUST allow users with BF-CONFIG permission to create, edit, and delete email templates.
- **FR-061**: System MUST restrict email template management to users with BF-CONFIG permission only.
- **FR-062**: System MUST allow users to customize template content before sending.

**Document Storage & Integration:**

- **FR-063**: System MUST store all documents (received and sent) in a dedicated external document storage web service.
- **FR-064**: System MUST store only document metadata and document ID locally (not the document content itself).
- **FR-065**: System MUST fail immediately with an error when the external document storage service is unavailable; users must retry manually.
- **FR-066**: Sent emails with attachments MUST be zipped as bundles by the integration service before storage.

**Access Control:**

- **FR-067**: System MUST enforce checklist-scoped access control: users can only see checklists they own, are responsible for, or have tasks assigned on.
- **FR-068**: System MUST prevent users from viewing or accessing documents/bundles attached to checklists they have no relationship with.

**Audit & Retention:**

- **FR-069**: System MUST allow users with BF-CONFIG permission to delete audit logs on demand.
- **FR-069a**: System MUST NOT enforce automatic retention periods for audit logs; logs persist until manually deleted by BF-CONFIG users.
- **FR-069b**: System MUST retain all operational data (checklists, bundles, tasks, bundle items, templates, etc.) indefinitely until manually deleted by authorized users.
- **FR-069c**: System MUST NOT implement automatic purging or archival of operational data based on time periods or status.

**Permission Management:**

- **FR-101**: System MUST support six predefined permission roles: BF-CONFIG, BF-SORT, BF-MANAGE, BF-VALIDATE, BF-OWNER, BF-RESPONSIBLE.
- **FR-102**: System MUST allow users to be assigned multiple permission roles simultaneously.
- **FR-103**: System MUST apply permissions additively/cumulatively when a user has multiple roles (user inherits permissions from all assigned roles).
- **FR-104**: System MUST restrict permission role assignment and revocation to users with BF-CONFIG permission only.
- **FR-105**: System MUST validate that users have BF-OWNER permission before allowing them to be designated as checklist owner.
- **FR-106**: System MUST validate that users have both BF-RESPONSIBLE and BF-MANAGE permissions before allowing them to be designated as checklist responsible.
- **FR-107**: System MUST prevent assigning BF-RESPONSIBLE permission to a user who lacks BF-MANAGE permission.
- **FR-108**: System MUST prevent removing BF-MANAGE permission from a user who has BF-RESPONSIBLE permission.
- **FR-109**: System MUST allow any user with BF-SORT permission to claim tasks from the triage pool (no configuration required).
- **FR-110**: System MUST record permission role assignments and revocations in the audit log including who made the change and when.
- **FR-111**: BF-CONFIG permission MUST grant ability to manage all system configuration including item library, templates, email templates, bundle item group types, party roles, control types, configuration versions (create, export, import, activate), user permission roles, and audit log deletion.
- **FR-112**: BF-SORT permission MUST grant ability to claim bundle tasks from the triage pool and associate bundles to checklists during triage.
- **FR-113**: BF-MANAGE permission MUST grant ability to associate bundle items or bundle item groups to checklist items.
- **FR-114**: BF-VALIDATE permission MUST grant ability to complete control checklists and set validation status on bundle items or bundle item groups.
- **FR-115**: BF-OWNER permission MUST grant ability to be designated as checklist owner (enabling edit checklist structure, delete checklist, reassign responsible).
- **FR-116**: BF-RESPONSIBLE permission MUST grant ability to be designated as checklist responsible (receiving tasks associated with checklist), and MUST always be accompanied by BF-MANAGE permission.

**Search Functionality:**

- **FR-071**: System MUST provide UI to search documents by metadata fields (name, date, status, checklist) and text search on document names/descriptions.
- **FR-072**: System MUST provide UI to search checklists by metadata fields (name, status, owner, responsible, date) and text search on checklist names.
- **FR-073**: System MUST provide UI to search bundles by metadata fields (date, status, checklist, source) and text search on bundle names.
- **FR-074**: System MUST provide UI to view all documents attached to a checklist with filtering and sorting capabilities.
- **FR-074a**: System MUST implement server-side pagination for all search results (documents, checklists, bundles) with a default page size of 50 results per page, with configurable page size.

### Key Entities

- **Item Library**: An organization-wide collection of pre-defined items managed by users with BF-CONFIG permission. Items in the library serve as the single source of truth for all checklist item definitions. Both templates and users reference items from this library.
- **Checklist Template**: A reusable template created by users with BF-CONFIG permission that defines default items for a specific
  workflow type. Has a name, description, and references to items from the Item Library (templates do not have their own
  item definitions; they select from the library). **Control checklists are COMPLETELY OPTIONAL** - each template item can optionally have a control checklist definition configured by users with BF-CONFIG permission, or can have no controls at all depending on customer context and validation requirements. When a checklist is created from the template, control checklist definitions (if present) are copied to the corresponding checklist items; if no controls were defined, the checklist items have no controls and validation occurs through direct operator judgment.
- **Party**: An external entity (supplier, vendor, applicant, etc.) tracked for document collection purposes. Has an ID and Name imported from external CRM systems. Parties are not created manually within Bundle Flow. Multiple checklists can reference the same party (e.g., different checklists for different document sets from the same supplier).
- **Party Role**: A predefined role type that describes a party's relationship to a checklist or checklist item (e.g., "Primary Supplier", "Co-applicant", "Guarantor", "Employer", "Client", "Transporter"). Managed by users with BF-CONFIG permission with free text fallback for unexpected cases. Used when attaching parties to checklists or checklist items.
- **Checklist**: A named collection of required document items for a specific workflow instance. Created from a template
  either proactively by any user with appropriate permissions (e.g., loan officer creating checklist when initiating credit application, before documents arrive) OR on-demand by the default user during bundle triage (who must specify the owner). Has a status (draft, active, completed), creation date, owner, and responsible. Has an immutable reference to the configuration version that was active at creation time - this ensures the checklist always uses the same configuration (templates, items, roles, types) throughout its lifecycle, even if new versions are activated later. Optionally has multiple parties attached, each with a specific role to track document sources (e.g., Party: "ABC Supplier", Role: "Primary Supplier"; Party: "XYZ Transport", Role: "Transporter"). Parties are optional and can be added, removed, or modified by the owner at any time without restrictions. Contains multiple checklist items. Can exist without any bundles (proactive creation) or receive multiple bundles/tasks over time.
- **Checklist Item**: A single requirement within a checklist. **Each checklist item represents ONE functional document type** (e.g., "CIN" is one checklist item, "Contract" is another checklist item). References an item from the Item Library (for default and library-added items) or is manually created with name and description. Has item type (default from template, from library, manual), status (required, N/A), and a global status (Not received, In progress, Received, Abandoned). Optionally has one party with a specific role for item-specific party tracking (e.g., Item "Invoice" → Party: "ABC Corp", Role: "Client"). The item-level party and role can be different from the checklist's parties and are always modifiable. Global status is automatically calculated: "Received" when at least one linked bundle item or bundle item group has Valid status (one valid document satisfying the functional requirement is sufficient). **Control checklists are COMPLETELY OPTIONAL** - for items coming from a template (default items), the control checklist is inherited from the template item definition when the checklist is created (if controls were defined in template; otherwise no controls). For manually created items (not from template), control checklist can be defined at creation time or left empty (validation occurs through direct operator judgment without structured guidance if empty). Controls are optional and depend on customer context and validation requirements. Default items (from template) cannot be deleted. Belongs to one checklist.
- **Bundle**: A list of document IDs with metadata that arrives via integration. **IMPORTANT: Bundle Flow does not receive, store, or handle actual document files.** An external integration service extracts documents from zip files and sends Bundle Flow only the document IDs and metadata. One bundle creates one task and relates to exactly ONE checklist. Bundle items cannot be shared across different checklists. Each bundle has a "main document ID" which typically represents the email body when the bundle originates from an email. The integration service handles zipping of sent emails with attachments and extracts the documents before sending IDs to Bundle Flow.
- **Bundle Item**: A record representing an individual document ID with metadata received from the integration service (documents are extracted from zip files by the external service before sending to Bundle Flow). Has a validation status (Unprocessed, Attached, Valid, Rejected, Incomplete, Not relevant). Initial status is "Unprocessed" when received; transitions to "Attached" when linked to checklist items OR when added to a bundle item group. The validation status applies to all checklist items the bundle item is linked to. Can be linked to one or more checklist items within the SAME checklist (bundle items cannot be shared across different checklists) OR can belong to multiple Bundle Item Groups, but not both simultaneously (mutually exclusive constraint). Items with "Not relevant" status MUST NOT be linked to checklist items. If a bundle item has any direct checklist item links, it cannot be added to any Bundle Item Group; if it belongs to any Bundle Item Group, it cannot be directly linked to checklist items. Validation status (except Unprocessed and Attached) is set by user: if a control checklist is configured for the linked checklist item(s), the user completes the controls before setting status; if NO controls are configured, the user sets validation status directly. **Control checklists are OPTIONAL.** Validation metadata (who validated, when, rejection reason) is recorded. Belongs to one bundle/task. Document content is stored in an external document storage service; locally only metadata and document ID are stored.
- **Bundle Item Group**: A user-created collection of multiple bundle items treated as a single document entity. Created by users with BF-MANAGE permission during association workflow. Has a name selected from a list of predefined bundle item group types (managed by users with BF-CONFIG permission), with fallback to free text entry if the needed name is not in the list. One bundle item can belong to multiple Bundle Item Groups simultaneously. Has its own independent validation status (Attached, Valid, Rejected, Incomplete) that does not automatically inherit from contained items. Transitions to "Attached" status when the group is linked to checklist items. Bundle items can be added to the group only while status is "Attached"; once validated, no items can be added (composition is locked). Bundle items can be removed at any time, which automatically resets status to "Attached" and requires re-validation. Validation status is set by user: if a control checklist is configured for the linked checklist item, the user completes the controls before setting status; if NO controls are configured, the user sets validation status directly. **Control checklists are OPTIONAL.** Can be associated with checklist items. Bundle items within any group cannot be individually associated to checklist items (only the groups can be associated). Validation metadata (who validated, when, rejection reason) is recorded same as bundle items.
- **Bundle Item Group Type**: A predefined group name/category managed by users with BF-CONFIG permission to standardize bundle item grouping. Examples: "CIN" (for identity card recto/verso), "Passport", "Contract". Users select from this list when creating bundle item groups, with fallback to free text entry if the needed type is not configured.
- **Control Type**: A predefined control category managed by users with BF-CONFIG permission to standardize control classification. Examples: "Coherency", "Conformity", "Authenticity", "Format". Users select from this list when defining controls in checklist templates, with fallback to free text entry if the needed type is not configured.
- **Control**: An individual verification item within an **optional** control checklist that provides structured guidance when validating bundle items or bundle item groups. **Controls are COMPLETELY OPTIONAL** - they can be defined at the checklist template level by users with BF-CONFIG permission and inherited by checklist items when a checklist is created from a template, or omitted entirely based on customer context. If configured, has four attributes: Type (selected from predefined control types with free text fallback), Status (Verified, Not verified, Skipped), Description (explains what verification is required), and Comment (additional notes or context during validation). When controls are configured, all controls must be explicitly marked with a status before validation can be completed. A control is satisfied when status is Verified OR Skipped; unsatisfied when status is Not verified. If any control is marked "Not verified", the bundle item or bundle item group validation status must be set to Rejected. **If no controls are configured**, validation is performed through direct operator judgment without structured guidance. Verification method (manual/visual vs automatic) is not tracked as a separate attribute.
- **Task**: A work item associated with a checklist. Has a category (Bundle Processing or Manual), assignee (can be null for unassigned), status, and creation timestamp. Task categories include:
    - **Bundle Processing**: Auto-created when bundle arrives (one per bundle). Initially unassigned and placed in triage pool; any user with BF-SORT permission can claim it. Progresses through three required actions: (1) attach bundle to checklist (requires BF-SORT), (2) attach bundle items to checklist items (requires BF-MANAGE), (3) process/validate links (requires BF-VALIDATE). Task is complete when a user with BF-VALIDATE permission explicitly marks it complete.
    - **Manual Tasks**: Manually created by owner/responsible for specific purposes:
        - **Create Reminder**: Has due date; triggers notification and escalation.
        - **Send Email**: Can use email template or custom composition.
          Tasks can be transferred to another user at any time by current assignee, checklist owner, or checklist responsible. A task can only be associated with one checklist.
- **Email Template**: A reusable email template managed by users with BF-CONFIG permission. Has name, subject, and body. Users can select
  templates when creating Send Email tasks and customize before sending.
- **Document Link**: The association between a bundle item (or bundle item group) and a checklist item. Does not have its own validation state; the validation status comes from the bundle item or bundle item group itself. Records when the link was created and by whom. One bundle item can have multiple document links to different checklist items, with the bundle item's single validation status applying to all links.
- **Triage Pool**: An unassigned task queue where incoming bundle tasks are placed upon arrival. Any user with BF-SORT permission can view and manually claim tasks from this pool. Once claimed, the task is assigned to the claiming user and removed from the pool.
- **Configuration Version**: A snapshot of all system configuration at a specific point in time, managed by users with BF-CONFIG permission. Has a unique name (user-defined), status (active/inactive), creation timestamp, and creator. Contains a complete JSON export of: checklist templates (with control definitions), item library, party roles, bundle item group types, and control types. Only one version can be active at any time - activating a new version automatically deactivates all others. New checklists created after a version is activated use that version's configuration (immutable reference); existing checklists continue using their original version. Versions enable configuration deployment across environments, testing before production, and configuration history tracking. When importing a JSON file to create a new version, it completely replaces the active version's configuration. Versions are identified solely by their unique name (no auto-increment numbering).

## Assumptions

- Bundle arrival mechanism (integration receiving zip files) already exists or will be implemented separately; this
  feature handles what happens after arrival.
- User authentication and authorization are handled by existing system infrastructure.
- **ONE bundle relates to exactly ONE checklist.** Bundle items cannot be shared across different checklists.
- A bundle item can satisfy multiple checklist items **within the same checklist** if needed (e.g., a comprehensive report satisfying both "financial statement" and "audit confirmation" items on the same checklist).
- The rejection reason for invalid links is free-text to allow flexibility in explaining issues.
- Changes to templates do not retroactively affect checklists already created from those templates.
- The item library is shared across all templates and checklists in the organization.
- Any user with BF-SORT permission can claim tasks from the triage pool; no specific configuration is required.
- **Bundle Flow does NOT receive, store, or handle actual document files.** An external integration service extracts documents from zip files and sends Bundle Flow only document IDs with metadata. All actual document content is stored in a dedicated external document storage web service.
- The integration service handles zipping of sent emails with attachments, extracting documents, and sending document IDs to Bundle Flow.
- External document storage service availability is not guaranteed; operations requiring document access fail immediately when unavailable (no queuing or retry logic in Bundle Flow).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can create a checklist from a template with up to 50 items in under 2 minutes.
- **SC-002**: Users can add items from the library or create manual items in under 30 seconds each.
- **SC-003**: Users can link a bundle item to a checklist item in under 30 seconds.
- **SC-004**: Checklist completion status updates within 2 seconds of the final validation.
- **SC-005**: 95% of users can correctly identify checklist completion progress on first viewing.
- **SC-006**: Document collection workflows that previously required manual tracking spreadsheets can be fully managed
  within the system.
- **SC-007**: Audit trail provides complete history of all validation decisions for compliance review.
- **SC-008**: Users can process a standard bundle (10 documents, 1 checklist) in under 15 minutes.
- **SC-009**: Owner can reassign responsible user in under 1 minute.
- **SC-010**: Default user can triage and submit a bundle task in under 1 minute.
- **SC-011**: 100% of incoming bundles are automatically extracted and assigned to the default user within 10 seconds of
  arrival.
- **SC-012**: Task reassignment to checklist responsible occurs within 2 seconds of default user submission.
- **SC-013**: Bundle extraction handles bundles with up to 100 documents without performance degradation.
- **SC-014**: 95% of users can correctly identify unprocessed bundle items at a glance.
- **SC-015**: Users can create a reminder task in under 30 seconds.
- **SC-016**: Users can create an email task (with template selection) in under 1 minute.
- **SC-017**: Reminder notifications are sent within 1 minute of due date being reached.
- **SC-018**: Escalation to owner occurs within 24 hours of unactioned reminder notification.
- **SC-019**: Task transfer completes within 2 seconds and immediately reflects in both users' task lists.
- **SC-020**: Task action progression (from attach-to-checklist to attach-items-to-items action) occurs immediately when the previous action is completed.