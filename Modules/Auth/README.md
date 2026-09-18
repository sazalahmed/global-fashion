# Auth Module

## Overview
Handles user authentication for BizPOS Pro including login, logout, registration, and password reset. Uses Laravel's built-in authentication with bcrypt hashing and session-based security.

## Controllers
- **AuthController** - Login, logout, registration, and password reset actions

## Services
- **AuthService** - Handles authentication logic: login, logout, OTP send/verify, password reset

## Form Requests
- **LoginRequest** - Validates email, password, remember
- **SendOtpRequest** - Validates OTP send and verify (conditional OTP validation)
- **ResetPasswordRequest** - Validates password reset (email, token, password with confirmation)

## Routes
- **Login** - User login form and authentication
- **Logout** - Session termination
- **Registration** - New user registration
- **Password Reset** - Forgot password flow with email verification

## Settings / Configuration
- **Session lifetime** - Configurable session duration (default: 30 minutes)
- **Throttle limits** - Rate limiting on login attempts (default: 5 per 15 minutes)
- **Bcrypt rounds** - Password hashing cost factor

## Dependencies
- **Core** - Uses the master layout and shared UI components for auth pages
