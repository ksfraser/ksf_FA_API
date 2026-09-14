# FA_API - Architecture

**Document ID:** ARCH-FAAPI-001  
**Module:** ksf_FA_API  
**Version:** 1.0.0  

---

## 1. Module Overview

FA_API implements dual API protocols (REST and SOAP) for FrontAccounting integration, using controller-based routing for REST and service classes for SOAP.

## 2. Component Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    REST Layer                                │
├─────────────────────────────────────────────────────────────┤
│ - EmployeeController                                         │
│   + list(ServerRequest, Response)                           │
│   + get(ServerRequest, Response, args)                     │
│   + create(ServerRequest, Response)                         │
│   + update(ServerRequest, Response, args)                   │
│   + delete(ServerRequest, Response, args)                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ uses
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Service Layer                              │
├─────────────────────────────────────────────────────────────┤
│ - EmployeeRepository                                         │
│ - Employee Entity                                            │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    SOAP Layer                                │
├─────────────────────────────────────────────────────────────┤
│ - EmployeeSoapService                                        │
│ - soapserver.php                                            │
└─────────────────────────────────────────────────────────────┘
```

## 3. Directory Structure

```
ksf_FA_API/
├── src/Ksfraser/
│   ├── REST/
│   │   ├── EmployeeController.php
│   │   ├── routes.php
│   │   └── v1/routes.php
│   └── SOAP/
│       ├── EmployeeSoapService.php
│       └── soapserver.php
├── tests/
│   └── Unit/
│       └── APITest.php
└── doc/ProjectDcs/
```

## 4. REST API Specification

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/employees | List all employees |
| GET | /api/v1/employees/{id} | Get employee by ID |
| POST | /api/v1/employees | Create employee |
| PUT | /api/v1/employees/{id} | Update employee |
| DELETE | /api/v1/employees/{id} | Delete employee |

## 5. Technology Stack

| Component | Technology |
|-----------|------------|
| REST | PSR-7, PSR-15 |
| SOAP | PHP SOAP Extension |
| Serialization | JSON |