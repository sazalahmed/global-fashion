# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-11-28

### Added
- Initial release of the SteadFast Courier Laravel package
- Complete API coverage for all SteadFast endpoints:
  - Order placement (single and bulk)
  - Delivery status checking (by consignment ID, invoice, tracking code)
  - Balance checking
  - Return request management
  - Payment management
  - Police station listing
- OAuth 2.0 authentication with API Key and Secret Key
- Built-in rate limiting (configurable, default 60 requests/minute)
- Comprehensive input validation before API calls
- Detailed error handling with `SteadfastException`
- Laravel Service Provider and Facade support
- Publishable configuration file
- Complete PHPDoc documentation
- Type hints throughout the codebase
- Support for Laravel 10.x, 11.x, and 12.x
- Support for PHP 8.2+

### Features
- Automatic URL construction for API endpoints
- JSON encoding with proper float preservation for `cod_amount`
- Request/response logging for debugging
- Configurable base URL
- Webhook bearer token support
- Cache configuration support

