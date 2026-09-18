# Core Module

## Overview
Base infrastructure module for BizPOS Pro. Provides the master layout, shared partials (sidebar, header, footer), reusable Blade components, and PWA service worker registration. All other modules depend on Core.

## Controllers
- **CoreController** - Handles core infrastructure routes (if any)

## Models
- None. Core is intentionally an infrastructure-only module and does not define domain models.

## Services
- None. Core provides shared layouts and components, not business logic. Business logic resides in feature modules.

## Form Requests
- None. Core is an infrastructure module and does not handle user input directly. Form Requests are defined in their respective feature modules.

## Routes
- None directly. Provides the master layout (`layouts/master.blade.php`) and shared partials consumed by all other modules.

## Contains
- **Master Layout** - `app.blade.php` with vendor CSS/JS loading, CSRF meta tag, flash messages, and script/style stacks
- **Sidebar Partial** - Navigation sidebar with active state detection via `request()->routeIs()`
- **Header Partial** - Top header bar with user menu and branch selector
- **Footer Partial** - Page footer
- **Shared Blade Components** - Reusable UI blocks (stat cards, filter bars, badges, etc.)
- **PWA Service Worker Registration** - Registers `sw.js` and `sw-pos.js` for offline support and push notifications

## Settings / Configuration
- Theme (light/dark mode) stored in session
- Sidebar menu structure and grouping

## Dependencies
- None. This is the base module. All other modules depend on Core for layouts, navigation, and shared UI components.
