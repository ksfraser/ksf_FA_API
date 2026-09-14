# ksf_FA_API - Installation & Dependency Guide

## Quick Start

```bash
cd /path/to/ksf_FA_API
composer install
./vendor/bin/phpunit --testdox
```

---

## Dependency Graph

```
ksf_FA_API
│
├── Runtime Requirements
│   └── PHP ^8.1
│
├── Development Dependencies
│   └── phpunit/phpunit ^10.0
│
└── KSF Module Dependencies (Optional - for full integration)
    ├── ksf_CRM          # Customer, Contact, Lead, Opportunity entities
    ├── ksf_HRM          # Employee entity
    ├── ksf_SupportTickets  # Ticket entity
    ├── ksf_Marketing    # Campaign entity
    ├── ksf_ProjectManagement  # Task entity
    ├── ksf_Notes        # Note entity
    ├── ksf_Calendar     # Event entity
    ├── ksf_AsteriskPBX  # Call entity
    ├── ksf_EmailManager  # Email entity
    └── ksf_Documents     # Document entity
```

---

## Full Installation with KSF Modules

If you want to connect to actual KSF entities:

```bash
# Install ksf_FA_API with all dependencies
composer require ksfraser/ksf_crm ksfraser/ksf_hrm ksfraser/ksf_support_tickets

# Or use the meta-package (when available)
composer require ksfraser/ksf_all
```

---

## Testing Without KSF Modules

The compatibility layer works standalone with **mock data** for testing:

```bash
# Install only dev dependencies
composer install --no-dev
composer require --dev phpunit/phpunit:^10.0

# Run tests (use mock data)
./vendor/bin/phpunit --testdox
```

---

## Endpoint URLs

### Compatibility Layer (SuiteCRM v4_1 Compatible)

| Protocol | URL | For Systems |
|----------|-----|-------------|
| REST | `/public/compat/v4_1/rest.php` | SuiteCRM, SugarCRM, vtiger |
| SOAP | `/public/compat/v4_1/soap.php` | SugarCRM SOAP clients |

### Native KSF REST API

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/public/api/v1/employees` | GET | List employees |
| `/public/api/v1/employees/{id}` | GET | Get employee |
| `/public/api/v1/employees` | POST | Create employee |
| `/public/api/v1/employees/{id}` | PUT | Update employee |
| `/public/api/v1/employees/{id}` | DELETE | Delete employee |

---

## Hermes Agent Migration

**Before (SuiteCRM):**
```php
$url = "http://fhsws002.ksfraser.com/ksfii/suitecrm/service/v4_1/rest.php";
$username = "ksfii_hermes";
$password = "hermes_agent";
```

**After (KSF FA API):**
```php
$url = "http://fhsws002.ksfraser.com/ksf_fa_api/public/compat/v4_1/rest.php";
$username = "ksfii_hermes";
$password = "hermes_agent";
// No other code changes needed!
```

---

## System-Specific Adapters

The compatibility layer auto-detects the source system:

| Detected By | System |
|-------------|--------|
| `__source: 'odoo'` | Odoo XML-RPC |
| `application: 'OrangeHRM'` | OrangeHRM REST |
| Standard v4_1 format | SuiteCRM/SugarCRM |

---

## Testing the Compat Layer

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit tests/Unit/Compat/v4_1/

# Test auth handler
./vendor/bin/phpunit tests/Unit/Compat/v4_1/AuthHandlerTest.php

# Test module mapper
./vendor/bin/phpunit tests/Unit/Compat/v4_1/ModuleMapperTest.php

# Test CRM controller
./vendor/bin/phpunit tests/Unit/Compat/v4_1/CRMControllerTest.php

# Test system adapters
./vendor/bin/phpunit tests/Unit/Compat/Adapters/SystemAdapterTest.php
```

---

## Test Credentials (Mock Mode)

When running without a database connection:

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | Administrator |
| ksfii_hermes | hermes_agent | Agent |

---

## Response Format

All responses follow SuiteCRM v4_1 format:

```json
// Login Response
{
    "id": "session_id_here",
    "module_name": "Users",
    "name_value_list": [
        {"name": "user_id", "value": "1"},
        {"name": "user_name", "value": "admin"}
    ]
}

// Entry List Response
{
    "result_count": 5,
    "total_count": 5,
    "next_offset": -1,
    "entry_list": [
        {
            "id": "123",
            "module_name": "Accounts",
            "name_value_list": [...]
        }
    ],
    "relationship_list": []
}
```

---

## Troubleshooting

### "Class not found" errors
```bash
composer dump-autoload
```

### Tests failing with "Invalid credentials"
This is expected when running without the FA database. The mock auth is working correctly.

### Port conflicts
The API runs on your web server (Apache/Nginx). Configure virtual host:
```apache
<VirtualHost *:80>
    ServerName api.example.com
    DocumentRoot /path/to/ksf_FA_API/public
</VirtualHost>
```