---
allowed-tools: Bash(git add:*), Bash(git status:*), Bash(git commit:*)
description: Create a git commit in plan mode
---

## Context

- Current git status: `git status`
- Current git diff (staged and unstaged changes): `git diff HEAD`
- Current branch: `git branch --show-current`
- Recent commits: `git log --oneline -10`

## Your task

**IMPORTANT: You are in PLAN MODE. Do NOT execute any git commands until I explicitly approve your plan.**

1. **Analysis Phase** (READ ONLY):
    - Review the git status and diff output above
    - Identify all changed files and their purposes
    - Categorize changes (features, fixes, refactors, docs, etc.)

2. **Planning Phase**:
    - Create a structured plan for the commit including:
        - Which files to stage (if not already staged)
        - Rationale for grouping these changes together
        - Any files that should be excluded or committed separately

3. **Validation Phase**:
    - Present your complete plan to me
    - Wait for my explicit approval before proceeding
    - Ask if I want to modify anything

4. **Execution Phase** (ONLY after approval):
    - Execute the git commands as planned
    - Confirm the commit was created successfully

**DO NOT use any write/execute tools until step 4. Focus only on analysis and planning first.**

# Commit Message Formatter

When formatting commit messages, follow this structure:

1. **First line (header)**: `BRANCH_NAME: Summary of changes`
    - Start with the branch name (e.g., SDXPST123). If branch is main, omit this part 
    - Add a colon and space
    - Write a concise summary of the changes
    - Keep it under 72 characters if possible

2. **Blank line**: Separate header from body

3. **Body**: Detailed description
    - Use bullet points (dashes) for multiple changes
    - Each point should be clear and specific
    - Include WHY changes were made, not just WHAT
    - Mention impact, benefits, or risk level if relevant

4. **Optional footer**: Additional context
    - Add a concluding sentence summarizing overall impact
    - Note any testing performed or compatibility concerns

Don't mention Claude code

- No mention such as 🤖 Generated with [Claude Code](https://claude.com/claude-code)
- or Co-Authored-By: Claude <noreply@anthropic.com>"

## Format Template

```
BRANCH_NAME: Category: Brief summary of main change

- First specific change or improvement
- Second specific change or improvement
- Third specific change or improvement
- Additional details about implementation

Overall impact statement and any additional notes.
All changes are [risk-level] improvements following [standards/practices].
```

## Examples

### Example 1: Refactoring

```
SDXPST123: Refactor: Improve Gradle build configuration best practices

- Remove deprecated JCenter repository (shutdown since 2021)
- Consolidate duplicate Maven Central declarations to single mavenCentral()
- Use version catalog for Jackson dependencies in meta2code-plugin
- Document snakeyaml resolution strategy (JAR vs AAR artifact selection)
- Standardize duplicates strategy to EXCLUDE across all modules

These changes improve maintainability, remove deprecated dependencies,
and ensure consistent configuration patterns across the build system.
All changes are low-risk improvements following Gradle best practices.
```

### Example 2: Feature Addition

```
SDXPST456: Add support for multi-tenant database isolation

- Implement tenant-aware JPA repository base classes
- Add TenantContext for request-scoped tenant identification
- Configure Hibernate filters for automatic tenant filtering
- Add integration tests for tenant data isolation

This change enables secure multi-tenant data separation at the database level.
Changes include comprehensive test coverage and backward compatibility.
```

### Example 3: Bug Fix

```
SDXPST789: Fix NPE in user authentication flow

- Add null checks in AuthenticationService.validateToken()
- Handle missing tenant context gracefully
- Add defensive programming for edge cases
- Include unit tests for null scenarios

This resolves production errors occurring during token validation.
The fix is backward compatible and includes regression tests.
```

## Common Categories

Use these category prefixes in the summary when appropriate:

- **Add**: New feature or capability
- **Fix**: Bug fix or correction
- **Refactor**: Code restructuring without behavior change
- **Update**: Modification to existing functionality
- **Remove**: Deletion of code or features
- **Optimize**: Performance improvements
- **Document**: Documentation changes
- **Test**: Test additions or modifications
- **Build**: Build system or dependency changes
- **Security**: Security-related changes

## Best Practices

1. Write in imperative mood ("Add feature" not "Added feature")
2. Be specific and concrete
3. Explain the "why" behind changes
4. Note any breaking changes clearly
5. Keep the summary line focused and concise
6. Use bullet points for clarity in the body
7. Always include the branch name at the start
