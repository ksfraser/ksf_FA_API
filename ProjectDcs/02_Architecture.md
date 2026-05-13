# Architecture - FrontAccounting API Adapter (ksf_FA_API)

**Module:** ksf_FA_API  
**Version:** 1.0.0  
**Date:** May 2026  

---

## 1. Architecture Overview

The FA API Adapter follows a **Layered Architecture** pattern with clear separation between the presentation layer (REST controllers, SOAP services), business logic layer (domain entities), and data access layer (repositories). This ensures testability, maintainability, and adherence to SOLID principles.

### 1.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            External Clients                                  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │  REST API   │  │  SOAP       │  │  Mobile     │  │  Legacy     │        │
│  │  Consumer   │  │  Client     │  │  App        │  │  ERP        │        │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘        │
└─────────┼────────────────┼────────────────┼────────────────┼───────────────┘
          │                │                │                │
          ▼                ▼                ▼                ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                          API Adapter Layer                                   │
│  ┌─────────────────────────────────┐  ┌─────────────────────────────────┐   │
│  │     REST Controller             │  │      SOAP Service               │   │
│  │     EmployeeController          │  │      EmployeeSoapService        │   │
│  │     - list()                   │  │      - getEmployee()             │   │
│  │     - get()                    │  │      - getEmployeeByEmail()      │   │
│  │     - create()                 │  │      - listEmployees()          │   │
│  │     - update()                 │  │      - createEmployee()          │   │
│  │     - delete()                 │  │      - updateEmployee()          │   │
│  └────────────────┬────────────────┘  │      - deleteEmployee()          │   │
│                   │                  └───────────────┬─────────────────────┘   │
│                   │                                  │                         │
│                   └──────────────────┬───────────────┘                         │
│                                      │                                        │
│                                      ▼                                        │
│                         ┌────────────────────────┐                           │
│                         │   Domain Entities      │                           │
│                         │   Employee             │                           │
│                         └───────────┬────────────┘                           │
│                                     │                                        │
│                                     ▼                                        │
│                         ┌────────────────────────┐                           │
│                         │   Repositories         │                           │
│                         │   EmployeeRepository   │                           │
│                         └───────────┬────────────┘                           │
│                                     │                                        │
└─────────────────────────────────────┼────────────────────────────────────────┘
                                      │
                                      ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                       Data Access Layer                                       │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │                        FrontAccounting Database                          │ │
│  │   employees | departments | users | branches                            │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Class Diagram

### 2.1 REST API Components

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        <<Controller>>                                       │
│                       EmployeeController                                     │
├─────────────────────────────────────────────────────────────────────────────┤
│ - repository: EmployeeRepository                                            │
├─────────────────────────────────────────────────────────────────────────────┤
│ + __construct(?EmployeeRepository)                                           │
│ + list(Request, Response): Response                                         │
│ + get(Request, Response, array): Response                                   │
│ + create(Request, Response): Response                                        │
│ + update(Request, Response, array): Response                                 │
│ + delete(Request, Response, array): Response                                │
│ - toArray(Employee): array                                                  │
└─────────────────────────────────────────────────────────────────────────────┘
           │
           │ uses
           ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         <<Repository>>                                       │
│                      EmployeeRepository                                      │
├─────────────────────────────────────────────────────────────────────────────┤
│ + findAll(array filters): array                                             │
│ + findById(int id): ?Employee                                                │
│ + findByEmail(string email): ?Employee                                       │
│ + save(Employee): Employee                                                  │
│ + delete(int id): bool                                                      │
└─────────────────────────────────────────────────────────────────────────────┘
           │
           │ returns
           ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                          <<Entity>>                                          │
│                          Employee                                            │
├─────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                  │
│ - employee_number: string                                                   │
│ - first_name: string                                                        │
│ - last_name: string                                                         │
│ - email: ?string                                                            │
│ - department: ?string                                                       │
│ - status: string                                                            │
├─────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                              │
│ + getEmployeeNumber(): string                                              │
│ + setFirstName(string): self                                                │
│ + setLastName(string): self                                                 │
│ + setEmail(?string): self                                                   │
│ + setStatus(string): self                                                   │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 2.2 SOAP Service Components

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                     EmployeeSoapService                                      │
├─────────────────────────────────────────────────────────────────────────────┤
│ - repository: EmployeeRepository                                            │
├─────────────────────────────────────────────────────────────────────────────┤
│ + __construct(?EmployeeRepository)                                           │
│ + getEmployee(int id): ?array                                               │
│ + getEmployeeByEmail(string email): ?array                                   │
│ + listEmployees(?string status): array                                       │
│ + createEmployee(array data): array                                          │
│ + updateEmployee(int id, array data): ?array                                 │
│ + deleteEmployee(int id): bool                                              │
│ - toArray(Employee): array                                                   │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Data Flow

### 3.1 REST API Request Flow

```
Client                    REST API                       Business Logic
   │                         │                               │
   │─GET /api/v1/employees──>│                               │
   │                         │                               │
   │                         │─query params?───────────────>│
   │                         │<─filters array───────────────│
   │                         │                               │
   │                         │─findAll(filters)────────────>│
   │                         │<─Employee[]───────────────────│
   │                         │                               │
   │                         │─foreach: toArray(e)──────────>│
   │                         │                               │
   │<─200 OK {data:[]}───────│                               │
   │                         │                               │
```

**Detail: POST /api/v1/employees**

```
Client                    REST API                       Employee Entity
   │                         │                               │
   │─POST /api/v1/employees──>│                               │
   │   {first_name, last_name, email}                        │
   │                         │                               │
   │                         │─json_decode(body)────────────>│
   │                         │<─assoc array─────────────────│
   │                         │                               │
   │                         │─new Employee()───────────────>│
   │                         │─setFirstName()───────────────>│
   │                         │─setLastName()────────────────>│
   │                         │─setEmail()───────────────────>│
   │                         │─setStatus()──────────────────>│
   │                         │<─Employee instance───────────│
   │                         │                               │
   │                         │─repository.save(employee)───>│
   │                         │<─saved Employee──────────────│
   │                         │                               │
   │<─201 Created {id:1}─────│                               │
   │                         │                               │
```

### 3.2 SOAP Request Flow

```
SOAP Client                SOAP Server                   EmployeeSoapService
   │                         │                               │
   │─<getEmployee>──────────>│                               │
   │   <id>123</id>          │                               │
   │                         │─soapenv:Envelope────────────>│
   │                         │<─unmarshalled params────────│
   │                         │                               │
   │                         │─getEmployee(123)───────────>│
   │                         │<─Employee array──────────────│
   │                         │                               │
   │<─<getEmployeeResponse>──│                               │
   │   <return>employee data</return>                         │
   │                         │                               │
```

---

## 4. API Endpoints Specification

### 4.1 REST Endpoints

#### GET /api/v1/employees

**Description:** List all employees with optional filtering

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter by status (active, inactive) |
| department | string | No | Filter by department name |

**Request:**
```
GET /api/v1/employees?status=active&department=Sales
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "employee_number": "EMP-001",
      "first_name": "John",
      "last_name": "Doe",
      "email": "john.doe@example.com",
      "department": "Sales",
      "status": "active"
    }
  ],
  "total": 1
}
```

#### GET /api/v1/employees/{id}

**Description:** Get single employee by ID

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Employee ID |

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "employee_number": "EMP-001",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "department": "Sales",
    "status": "active"
  }
}
```

**Response (Not Found):**
```json
{
  "success": false,
  "error": "Employee not found"
}
```

#### POST /api/v1/employees

**Description:** Create new employee

**Request Body:**
```json
{
  "first_name": "Jane",
  "last_name": "Smith",
  "email": "jane.smith@example.com",
  "status": "active"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 2
  }
}
```

#### PUT /api/v1/employees/{id}

**Description:** Update existing employee

**Request Body:**
```json
{
  "first_name": "Jane",
  "last_name": "Doe",
  "department": "Marketing",
  "status": "inactive"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 2
  }
}
```

#### DELETE /api/v1/employees/{id}

**Description:** Delete employee

**Response:**
```json
{
  "success": true,
  "message": "Employee deleted"
}
```

---

### 4.2 SOAP Operations

#### getEmployee

```xml
<soap:operation="getEmployee">
  <input>
    <id type="int">123</id>
  </input>
  <output>
    <return type="array">
      <id>123</id>
      <employee_number>EMP-001</employee_number>
      <first_name>John</first_name>
      <last_name>Doe</last_name>
      <email>john.doe@example.com</email>
      <department>Sales</department>
      <status>active</status>
    </return>
  </output>
</soap:operation>
```

#### getEmployeeByEmail

```xml
<soap:operation="getEmployeeByEmail">
  <input>
    <email type="string">john.doe@example.com</email>
  </input>
  <output>
    <return type="array">...</return>
  </output>
</soap:operation>
```

---

## 5. Routing Configuration

### 5.1 Route Definitions

Routes are defined in `/src/Ksfraser/REST/routes.php`:

```php
$app->group('/api/v1', function (RouteCollectorProxy $group) {
    $group->get('/employees', [$employeeController, 'list']);
    $group->get('/employees/{id:\d+}', [$employeeController, 'get']);
    $group->post('/employees', [$employeeController, 'create']);
    $group->put('/employees/{id:\d+}', [$employeeController, 'update']);
    $group->delete('/employees/{id:\d+}', [$employeeController, 'delete']);
});
```

### 5.2 Route Middleware

| Middleware | Purpose |
|------------|---------|
| Authentication | Verify session or API key |
| CORS | Handle cross-origin requests |
| JSON Validation | Validate request body is valid JSON |

---

## 6. Error Handling

### 6.1 HTTP Status Codes

| Code | Meaning | Use Case |
|------|---------|----------|
| 200 | OK | Successful GET, PUT |
| 201 | Created | Successful POST |
| 400 | Bad Request | Invalid JSON, missing fields |
| 404 | Not Found | Employee doesn't exist |
| 500 | Internal Error | Database failure |

### 6.2 Error Response Format

```json
{
  "success": false,
  "error": "Error message description"
}
```

---

## 7. Package Structure

```
ksf_FA_API/
├── AGENTS.md
├── composer.json
├── src/
│   └── Ksfraser/
│       ├── REST/
│       │   ├── EmployeeController.php
│       │   ├── routes.php
│       │   └── v1/
│       │       └── routes.php
│       └── SOAP/
│           ├── EmployeeSoapService.php
│           └── soapserver.php
├── tests/
│   └── Unit/
│       ├── EmployeeControllerTest.php
│       └── EmployeeSoapServiceTest.php
└── ProjectDcs/
    ├── 01_Business_Requirements.md
    ├── 02_Architecture.md
    ├── 03_Functional_Requirements.md
    ├── 04_Use_Case.md
    ├── 05_Test_Plan.md
    └── 06_UAT_Plan.md
```

---

## 8. Namespace Convention

```json
{
  "autoload": {
    "psr-4": {
      "Ksfraser\\REST\\": "src/Ksfraser/REST/",
      "Ksfraser\\SOAP\\": "src/Ksfraser/SOAP/",
      "Ksfraser\\HRM\\Entity\\": "src/Ksfraser/HRM/Entity/",
      "Ksfraser\\HRM\\Repository\\": "src/Ksfraser/HRM/Repository/"
    }
  }
}
```

---

## 9. Design Patterns Applied

| Pattern | Application | Benefit |
|---------|-------------|---------|
| Controller | EmployeeController | Request handling separation |
| Repository | EmployeeRepository | Data access abstraction |
| Entity | Employee | Domain object representation |
| Adapter | REST/SOAP services | Platform interface adaptation |
| Factory | toArray() method | Consistent object serialization |

---

*Document Version: 1.0*  
*Last Updated: May 2026*