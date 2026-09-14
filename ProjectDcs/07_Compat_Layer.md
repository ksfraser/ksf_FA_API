# CRM Compatibility Layer - Architecture Specification

**Module:** ksf_FA_API  
**Version:** 1.1.0  
**Date:** May 2026  

---

## 1. Overview

The CRM Compatibility Layer provides drop-in API replacement for legacy CRM and ERP systems. This allows organizations to migrate from SuiteCRM, SugarCRM, vtiger, OrangeHRM, Odoo, Dolibarr, dotproject, OpenProject, and LibreProject to KSF modules without modifying existing client integrations.

### 1.1 Supported Endpoints

| Protocol | Endpoint | Compatibility |
|----------|----------|---------------|
| REST | `/api/compat/v4_1/rest.php` | SuiteCRM/SugarCRM v4_1 |
| SOAP | `/api/compat/v4_1/soap.php` | SugarCRM SOAP |

---

## 2. Architecture

### 2.1 Component Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         External Client (Hermes Agent)                       │
│                      SuiteCRM Client | SugarCRM Client                       │
│                      OrangeHRM Client | Odoo Client                          │
└─────────────────────────────────┬───────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         Compatibility Layer                                  │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │                        RestHandler                                       │ │
│  │  - Parses SuiteCRM-style POST requests                                  │ │
│  │  - Routes to CRMController                                              │ │
│  │  - Returns name_value_list format                                        │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │                        SoapHandler                                       │ │
│  │  - Parses SOAP XML envelopes                                            │ │
│  │  - Routes to CRMController                                              │ │
│  │  - Returns SOAP XML responses                                            │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │                        CRMController                                    │ │
│  │  - Implements get_entry, get_entry_list, set_entry                      │ │
│  │  - Uses ModuleMapper for field translation                              │ │
│  │  - Uses AuthHandler for session management                               │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │                        ModuleMapper                                      │ │
│  │  - Maps CRM module names to KSF entities                                │ │
│  │  - Translates field names between systems                               │ │
│  │  - Supports 14+ CRM modules                                             │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           KSF Business Layer                                  │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │                        AuthHandler                                      │ │
│  │  - Session-based authentication                                         │ │
│  │  - Compatible with SuiteCRM login flow                                  │ │
│  │  - Generates session IDs                                                │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │                      KSF Modules                                        │ │
│  │  ksf_CRM (Customer, Contact, Lead, Opportunity)                          │ │
│  │  ksf_HRM (Employee)                                                      │ │
│  │  ksf_SupportTickets (Ticket)                                             │ │
│  │  ksf_Marketing (Campaign)                                               │ │
│  │  ksf_ProjectManagement (Task, Project)                                   │ │
│  │  ksf_Notes (Note)                                                        │ │
│  │  ksf_Calendar (Event, Meeting)                                          │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Supported Modules

### 3.1 Module Mapping

| CRM Module | KSF Module | KSF Entity | Fields Mapped |
|------------|------------|------------|---------------|
| Accounts | ksf_CRM | Customer | 12 fields |
| Contacts | ksf_CRM | Contact | 15 fields |
| Leads | ksf_CRM | Lead | 11 fields |
| Opportunities | ksf_CRM | Opportunity | 12 fields |
| Cases | ksf_SupportTickets | Ticket | 8 fields |
| Tasks | ksf_ProjectManagement | Task | 9 fields |
| Meetings | ksf_Calendar | Event | 8 fields |
| Calls | ksf_AsteriskPBX | Call | 8 fields |
| Emails | ksf_EmailManager | Email | 7 fields |
| Users | ksf_HRM | Employee | 10 fields |
| Employees | ksf_HRM | Employee | 9 fields |
| Campaigns | ksf_Marketing | Campaign | 12 fields |
| Notes | ksf_Notes | Note | 7 fields |
| Documents | ksf_Documents | Document | 8 fields |

### 3.2 Field Name Translations

#### Accounts → Customer

| SuiteCRM Field | KSF Field | Type |
|----------------|-----------|------|
| id | debtor_no | string |
| name | name | string |
| phone_office | phone | string |
| email1 | email | string |
| billing_address_street | address | string |
| billing_address_city | city | string |
| billing_address_state | province | string |
| billing_address_postalcode | postal_code | string |
| industry | industry | enum |
| website | website | url |

#### Contacts → Contact

| SuiteCRM Field | KSF Field | Type |
|----------------|-----------|------|
| first_name | first_name | string |
| last_name | last_name | string |
| phone_work | phone | string |
| email1 | email | string |
| account_name | customer_name | string |

---

## 4. API Methods

### 4.1 Authentication Methods

#### login

**Request:**
```json
{
    "method": "login",
    "rest_data": {
        "user_auth": {
            "user_name": "admin",
            "password": "",
            "pass_clear": "password123"
        },
        "application_name": "Hermes Agent"
    }
}
```

**Response:**
```json
{
    "id": "a1b2c3d4e5f6...",
    "module_name": "Users",
    "name_value_list": [
        {"name": "user_id", "value": "1"},
        {"name": "user_name", "value": "admin"}
    ]
}
```

#### logout

**Request:**
```json
{
    "method": "logout",
    "session": "a1b2c3d4...",
    "rest_data": {}
}
```

### 4.2 Data Retrieval Methods

#### get_entry

**Request:**
```json
{
    "method": "get_entry",
    "session": "a1b2c3d4...",
    "rest_data": {
        "module_name": "Accounts",
        "id": "123"
    }
}
```

**Response:**
```json
{
    "id": "123",
    "module_name": "Accounts",
    "name_value_list": [
        {"name": "name", "value": "Acme Corp"},
        {"name": "phone_office", "value": "555-1234"}
    ]
}
```

#### get_entry_list

**Request:**
```json
{
    "method": "get_entry_list",
    "session": "a1b2c3d4...",
    "rest_data": {
        "module_name": "Contacts",
        "query": "accounts.name='Acme Corp'",
        "order_by": "contacts.last_name ASC",
        "offset": 0,
        "max_results": 25
    }
}
```

**Response:**
```json
{
    "result_count": 5,
    "total_count": 5,
    "next_offset": -1,
    "entry_list": [
        {
            "id": "456",
            "module_name": "Contacts",
            "name_value_list": [
                {"name": "first_name", "value": "John"},
                {"name": "last_name", "value": "Doe"}
            ]
        }
    ],
    "relationship_list": []
}
```

### 4.3 Data Modification Methods

#### set_entry

**Request:**
```json
{
    "method": "set_entry",
    "session": "a1b2c3d4...",
    "rest_data": {
        "module_name": "Accounts",
        "name_value_list": [
            {"name": "name", "value": "New Company"},
            {"name": "phone_office", "value": "555-9999"}
        ]
    }
}
```

**Response:**
```json
{
    "id": "789",
    "module_name": "Accounts",
    "name_value_list": [...]
}
```

#### delete

**Request:**
```json
{
    "method": "delete",
    "session": "a1b2c3d4...",
    "rest_data": {
        "module_name": "Accounts",
        "id": "789"
    }
}
```

### 4.4 Metadata Methods

#### get_module_fields

**Request:**
```json
{
    "method": "get_module_fields",
    "session": "a1b2c3d4...",
    "rest_data": {"module_name": "Accounts"}
}
```

**Response:**
```json
{
    "module_name": "Accounts",
    "module_fields": [
        {"name": "id", "type": "id"},
        {"name": "name", "type": "name", "label": "Account Name"},
        {"name": "phone_office", "type": "phone", "label": "Office Phone"}
    ],
    "link_fields": []
}
```

#### get_available_modules

**Request:**
```json
{
    "method": "get_available_modules",
    "session": "a1b2c3d4..."
}
```

**Response:**
```json
{
    "modules": [
        {"module_key": "Accounts", "module_label": "Accounts"},
        {"module_key": "Contacts", "module_label": "Contacts"},
        {"module_key": "Leads", "module_label": "Leads"}
    ]
}
```

---

## 5. System-Specific Adapters

### 5.1 OrangeHRM Adapter

OrangeHRM uses a different field naming convention. The ModuleMapper handles:

| OrangeHRM Field | Standard Field |
|-----------------|----------------|
| emp_number | id |
| emp_firstname | first_name |
| emp_lastname | last_name |
| emp_work_email | email |

### 5.2 Odoo Adapter

Odoo uses XML-RPC. The adapter converts:

```
Odoo XML-RPC          →        SuiteCRM v4_1
execute_kw()          →        get_entry_list()
model.write()         →        set_entry()
res.partner           →        Accounts
hr.employee           →        Employees
```

### 5.3 Dolibarr Adapter

Dolibarr REST endpoints map to:

| Dolibarr Endpoint | SuiteCRM Method |
|-------------------|-----------------|
| GET /contacts | get_entry_list (Contacts) |
| POST /contacts | set_entry (Contacts) |
| GET /thirdparties | get_entry_list (Accounts) |

### 5.4 OpenProject/LibreProject Adapter

| OpenProject | SuiteCRM |
|-------------|----------|
| /api/v3/work_packages | get_entry_list (Tasks) |
| /api/v3/projects | get_entry_list (Opportunities) |

---

## 6. Security

### 6.1 Authentication

- Session-based auth matching SuiteCRM behavior
- Session timeout: 1 hour (configurable)
- Session ID: 64-character hex string

### 6.2 Input Validation

- All inputs sanitized before database queries
- SQL injection prevention via db_escape()
- XSS prevention via htmlspecialchars()

### 6.3 Rate Limiting

- Per-session rate limiting: 100 requests/minute
- Global rate limiting: 1000 requests/minute

---

## 7. Directory Structure

```
ksf_FA_API/
├── src/
│   └── Ksfraser/
│       └── Compat/
│           └── v4_1/
│               ├── ModuleMapper.php      # Field & module mapping
│               ├── AuthHandler.php       # Session management
│               ├── CRMController.php     # API method implementations
│               ├── RestHandler.php       # HTTP request handler
│               └── SoapHandler.php      # SOAP request handler
├── public/
│   └── compat/
│       └── v4_1/
│           ├── rest.php                  # REST endpoint
│           └── soap.php                  # SOAP endpoint
├── tests/
│   └── Unit/
│       └── Compat/
│           └── v4_1/
│               ├── AuthHandlerTest.php
│               ├── ModuleMapperTest.php
│               └── CRMControllerTest.php
└── wsdl/
    └── KSFAPI.wsdl                      # WSDL definition
```

---

## 8. Migration Guide

### 8.1 Hermes Agent Migration

**Before (SuiteCRM):**
```php
$url = "http://fhsws002.ksfraser.com/ksfii/suitecrm/service/v4_1/rest.php";
```

**After (KSF):**
```php
$url = "http://fhsws002.ksfraser.com/ksf_fa_api/api/compat/v4_1/rest.php";
```

### 8.2 Authentication Migration

The Hermes agent can continue using the same `login` method:

```php
$login_params = [
    "user_auth" => [
        "user_name" => "ksfii_hermes",
        "pass_clear" => "hermes_agent"
    ]
];
$login_result = call("login", $login_params, $new_url);
$session_id = $login_result->id;
```

### 8.3 Data Access Migration

No changes required to existing data access code.

---

## 9. Testing

### 9.1 Test Coverage

| Component | Tests | Status |
|-----------|-------|--------|
| AuthHandler | 10 tests | Passing |
| ModuleMapper | 9 tests | Passing |
| CRMController | 15 tests | Passing |
| RestHandler | Manual | Manual |
| SoapHandler | Manual | Manual |

### 9.2 Manual Testing Checklist

- [ ] Login with valid credentials
- [ ] Login with invalid credentials (error returned)
- [ ] Get available modules
- [ ] Get module fields
- [ ] get_entry for each module
- [ ] get_entry_list with query
- [ ] set_entry creates record
- [ ] set_entry updates record
- [ ] delete removes record
- [ ] Session timeout handling

---

*Document Version: 1.0*  
*Last Updated: May 2026*