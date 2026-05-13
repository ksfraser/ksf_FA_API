# AGENTS.md - ksf_FA_API#

## Architecture Overview#

**FA Module** for REST API - exposes FA data via RESTful endpoints for external integrations.

### Core Principles#
- **SOLID**, **DRY**, **TDD**, **DI**, **SRP**#

## Repository Structure#

```
ksf_FA_API/
├── src/#
│   ├── Controllers/#
│   │   ├── CustomerController.php#
│   │   ├── InvoiceController.php#
│   │   └── ...#
│   ├── Middleware/#
│   │   ├── AuthMiddleware.php#
│   │   └── RateLimitMiddleware.php#
│   └── Models/#
├── routes/#
│   └── api_routes.php#
├── hooks.php#
├── composer.json#
└── ProjectDocs/#
```

## Dependencies#

- **ksf_FA_API_Core** (business logic)#
- **FrontAccounting 2.4+**#
