# Use Case Specification - FrontAccounting API Adapter (ksf_FA_API)

**Module:** ksf_FA_API  
**Version:** 1.0.0  
**Date:** May 2026  

---

## 1. Use Case Overview

| ID | Use Case | Actor | Priority |
|----|----------|-------|----------|
| UC-001 | List Employees | External System, SPA | HIGH |
| UC-002 | Get Employee by ID | External System, SPA | HIGH |
| UC-003 | Create Employee | HR System, Admin | HIGH |
| UC-004 | Update Employee | HR System, Admin | HIGH |
| UC-005 | Delete Employee | Admin | HIGH |
| UC-006 | Get Employee via SOAP | Legacy ERP | MEDIUM |
| UC-007 | Find Employee by Email | External System | MEDIUM |

---

## 2. Use Case Definitions

### UC-001: List Employees

**Actor:** External System (REST Client), SPA  
**Precondition:** API accessible, authentication valid  
**Trigger:** Client requests employee list

**Steps:**
1. Client sends GET /api/v1/employees with optional query params
2. Controller receives request
3. Controller extracts query parameters (status, department)
4. Controller builds filters array
5. Controller calls repository.findAll(filters)
6. Repository queries database
7. Repository returns Employee entities
8. Controller maps entities to arrays via toArray()
9. Controller builds response JSON
10. Controller returns 200 with JSON body

**Postcondition:** Client receives list of employees

**Success Scenario:**
```
Request: GET /api/v1/employees?status=active
Response:
{
  "success": true,
  "data": [
    {"id": 1, "first_name": "John", ...},
    {"id": 2, "first_name": "Jane", ...}
  ],
  "total": 2
}
```

**Failure Scenarios:**
- No employees found: Return empty array, total: 0
- Database error: Return 500 with error message

---

### UC-002: Get Employee by ID

**Actor:** External System, SPA  
**Precondition:** Employee ID known  
**Trigger:** Client requests single employee

**Steps:**
1. Client sends GET /api/v1/employees/123
2. Controller receives request with id in args
3. Controller converts id to integer
4. Controller calls repository.findById(id)
5. Repository queries database
6. If employee found:
   a. Repository returns Employee entity
   b. Controller maps to array
   c. Controller returns 200 with data
7. If employee not found:
   a. Controller returns 404 with error

**Postcondition:** Client receives single employee or 404

**Success Scenario:**
```
Request: GET /api/v1/employees/5
Response:
{
  "success": true,
  "data": {
    "id": 5,
    "employee_number": "EMP-005",
    "first_name": "Alice",
    "last_name": "Johnson",
    "email": "alice@example.com",
    "department": "Engineering",
    "status": "active"
  }
}
```

**Not Found Scenario:**
```
Request: GET /api/v1/employees/999
Response:
{
  "success": false,
  "error": "Employee not found"
}
Status: 404
```

---

### UC-003: Create Employee

**Actor:** HR System, Admin Interface  
**Precondition:** Valid employee data in request body  
**Trigger:** Client sends POST with employee data

**Steps:**
1. Client sends POST /api/v1/employees with JSON body
2. Controller receives request
3. Controller parses JSON body
4. If JSON invalid: return 400 with error
5. If missing required fields: return 400 with error
6. Controller creates new Employee entity
7. Controller calls setters with provided data
8. Controller calls repository.save(employee)
9. Repository inserts into database, returns saved entity
10. Controller returns 201 with id in data

**Postcondition:** New employee created with generated ID

**Success Scenario:**
```
Request: POST /api/v1/employees
{
  "first_name": "Bob",
  "last_name": "Williams",
  "email": "bob@example.com"
}

Response:
{
  "success": true,
  "data": {
    "id": 12
  }
}
Status: 201
```

**Validation Failure Scenario:**
```
Request: POST /api/v1/employees
{
  "email": "bob@example.com"
}

Response:
{
  "success": false,
  "error": "Missing required fields: first_name, last_name"
}
Status: 400
```

---

### UC-004: Update Employee

**Actor:** HR System, Admin Interface  
**Precondition:** Valid employee ID and update data  
**Trigger:** Client sends PUT with updated data

**Steps:**
1. Client sends PUT /api/v1/employees/5 with JSON body
2. Controller extracts id from args
3. Controller calls repository.findById(id)
4. If employee not found: return 404
5. Controller parses JSON body
6. Controller iterates through provided fields
7. For each field, call corresponding setter on entity
8. Controller calls repository.save(employee)
9. Repository updates database
10. Controller returns 200 with id

**Postcondition:** Employee record updated with new data

**Success Scenario:**
```
Request: PUT /api/v1/employees/5
{
  "department": "Sales",
  "status": "inactive"
}

Response:
{
  "success": true,
  "data": {
    "id": 5
  }
}
```

---

### UC-005: Delete Employee

**Actor:** Admin Interface  
**Precondition:** Valid employee ID  
**Trigger:** Client sends DELETE request

**Steps:**
1. Client sends DELETE /api/v1/employees/5
2. Controller extracts id from args
3. Controller calls repository.findById(id)
4. If employee not found: return 404
5. Controller calls repository.delete(id)
6. Repository deletes from database
7. Controller returns 200 with success message

**Postcondition:** Employee record removed from database

**Success Scenario:**
```
Request: DELETE /api/v1/employees/5
Response:
{
  "success": true,
  "message": "Employee deleted"
}
```

---

### UC-006: Get Employee via SOAP

**Actor:** Legacy ERP System  
**Precondition:** Valid WSDL access, valid employee ID  
**Trigger:** SOAP client calls getEmployee operation

**Steps:**
1. SOAP client loads WSDL from /soap/employee?wsdl
2. SOAP client calls getEmployee(123)
3. SOAP server receives envelope
4. Server extracts id parameter
5. Server calls EmployeeSoapService.getEmployee(123)
6. Service calls repository.findById(123)
7. If found: Service calls toArray() on entity
8. Service returns array to SOAP server
9. SOAP server wraps in response envelope
10. Client receives employee array

**Postcondition:** SOAP client receives employee data

**Success Scenario:**
```
SOAP Request:
<soapenv:Envelope>
  <soapenv:Body>
    <getEmployee>
      <id>123</id>
    </getEmployee>
  </soapenv:Body>
</soapenv:Envelope>

SOAP Response:
<soapenv:Envelope>
  <soapenv:Body>
    <getEmployeeResponse>
      <return>
        <id>123</id>
        <employee_number>EMP-123</employee_number>
        <first_name>John</first_name>
        <last_name>Doe</last_name>
        <email>john.doe@example.com</email>
        <department>Sales</department>
        <status>active</status>
      </return>
    </getEmployeeResponse>
  </soapenv:Body>
</soapenv:Envelope>
```

---

### UC-007: Find Employee by Email

**Actor:** External System, SPA  
**Precondition:** Valid email address  
**Trigger:** Client searches for employee by email

**Steps (REST):**
1. Use GET /api/v1/employees?email=john@example.com (if supported)
   OR
2. List all employees, filter client-side by email

**Steps (SOAP):**
1. SOAP client calls getEmployeeByEmail("john@example.com")
2. Service calls repository.findByEmail(email)
3. Repository queries database WHERE email = ?
4. If found: Return Employee entity
5. Service converts to array
6. Return to client

**Postcondition:** Employee found by email or null returned

---

## 3. Use Case Matrix

| Use Case | Actor | Trigger | Precondition | Postcondition |
|----------|-------|---------|--------------|---------------|
| UC-001 | External System | GET /api/v1/employees | Authenticated | List returned |
| UC-002 | External System | GET /api/v1/employees/{id} | Valid ID | Employee or 404 |
| UC-003 | HR System | POST /api/v1/employees | Valid data | Employee created |
| UC-004 | HR System | PUT /api/v1/employees/{id} | Valid ID, data | Employee updated |
| UC-005 | Admin | DELETE /api/v1/employees/{id} | Valid ID | Employee deleted |
| UC-006 | Legacy ERP | getEmployee() call | Valid ID | Employee array |
| UC-007 | External System | getEmployeeByEmail() | Valid email | Employee array |

---

## 4. Error Handling Use Cases

### EH-001: Invalid JSON in Request Body

**Trigger:** POST/PUT with invalid JSON  
**Steps:**
1. Controller calls json_decode(body)
2. json_last_error() != JSON_ERROR_NONE
3. Return 400 with error message

**Response:**
```json
{
  "success": false,
  "error": "Invalid JSON"
}
```

---

### EH-002: Missing Required Fields

**Trigger:** POST without first_name or last_name  
**Steps:**
1. Controller parses JSON
2. Checks for required fields
3. If missing: Return 400 with field names

**Response:**
```json
{
  "success": false,
  "error": "Missing required field: first_name"
}
```

---

### EH-003: Employee Not Found

**Trigger:** GET/PUT/DELETE with non-existent ID  
**Steps:**
1. Repository.findById() returns null
2. Controller returns 404 with error

**Response:**
```json
{
  "success": false,
  "error": "Employee not found"
}
```

---

### EH-004: Database Connection Error

**Trigger:** Database unavailable  
**Steps:**
1. Repository throws exception or db returns false
2. Controller catches and returns 500

**Response:**
```json
{
  "success": false,
  "error": "Internal server error"
}
```

---

## 5. Alternative Flows

### AF-001: Empty Results

**Trigger:** List with filters matching no employees  
**Flow:**
1. repository.findAll(filters) returns empty array
2. Controller builds response with empty data array
3. Returns 200 with total: 0

### AF-002: Default Status on Create

**Trigger:** POST without status field  
**Flow:**
1. Controller parses JSON
2. Status field not present
3. Controller uses default: Employee::STATUS_ACTIVE
4. Employee created with active status

---

*Document Version: 1.0*  
*Last Updated: May 2026*