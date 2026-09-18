# Security Module

## Overview
Manages system security including user administration, role and permission assignment, API key management, and system backups. Built on spatie/laravel-permission for RBAC (Role-Based Access Control).

## Controllers
- **UserController** — CRUD operations for user accounts, including profile management, role assignment, branch assignment, and account activation/deactivation.
- **SecurityController** — Handles API key management, system backup operations, and security-related system actions.

## Models
- **ApiKey** — Stores API keys for external integrations, tracking key name, token hash, permissions scope, last used timestamp, and expiry date.

## Services
- **ApiKeyService** — Business logic for generating, validating, revoking, and rotating API keys for external system integrations.
- **BackupService** — Handles database and file system backups, including creation, scheduling, download, and cleanup of old backups.

## Routes
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/users` | List all users |
| GET | `/users/create` | Create user form |
| POST | `/users` | Store new user |
| GET | `/users/{id}` | View user details |
| GET | `/users/{id}/edit` | Edit user form |
| PUT | `/users/{id}` | Update user |
| DELETE | `/users/{id}` | Delete/deactivate user |
| POST | `/users/{id}/roles` | Assign roles to user |
| GET | `/security/api-keys` | List API keys |
| POST | `/security/api-keys` | Generate new API key |
| DELETE | `/security/api-keys/{id}` | Revoke API key |
| GET | `/security/backups` | List backups |
| POST | `/security/backups` | Create new backup |
| GET | `/security/backups/{id}/download` | Download backup file |

## Settings / Configuration
- Password policy (minimum length, complexity requirements).
- Session timeout duration.
- API key expiry period.
- Backup schedule and retention policy.
- Two-factor authentication settings (if enabled).

## Dependencies
- **Auth** — Laravel's built-in authentication system for login, guards, and session management.
- **Core** — Uses spatie/laravel-permission for role and permission management.
