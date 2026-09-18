# Activity Module

## Overview
Centralized activity logging for BizPOS Pro. Tracks user actions across all modules (create, update, delete, login, status changes) and provides a searchable, filterable audit trail.

## Controllers
- **ActivityController** - Lists and filters activity log entries with pagination, date range, user, and module filters

## Models
- **ActivityLog** - Stores activity records (user, action, module, subject type/ID, description, IP address, timestamps)

## Services
- **ActivityService** - Provides methods to log activities and query/filter the activity log; used by all modules via the LogsActivity trait

## Form Requests
- **ActivityFilterRequest** - Validates activity log filter parameters (search, log_name, event, causer_id, subject_type, date range)
- **ClearActivityRequest** - Validates clear/purge parameters (log_name, older_than_days)

## Routes
- **Activity Log Listing** - View all activity logs with filtering by user, module, action type, and date range

## Settings / Configuration
- No module-specific settings. Logging behavior is controlled by the LogsActivity trait applied to other modules.

## Dependencies
- **Core** - Used as infrastructure by all modules. Any module that uses the `LogsActivity` trait automatically records actions to this module's activity log table.
