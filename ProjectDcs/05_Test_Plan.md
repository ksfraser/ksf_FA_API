# Test Plan - FrontAccounting API Adapter (ksf_FA_API)

**Module:** ksf_FA_API  
**Version:** 1.0.0  
**Date:** May 2026  

---

## 1. Test Overview

### 1.1 Test Objectives

- Verify all REST and SOAP endpoints function correctly
- Achieve 100% coverage on controller and service classes
- Validate request/response formats
- Ensure error handling works for all edge cases

### 1.2 Test Scope

| Component | Coverage Target |
|-----------|----------------|
| EmployeeController | 100% |
| EmployeeSoapService | 100% |
| routes.php | 100% |
| Error handling | All scenarios |

---

## 2. Test Cases

### 2.1 REST API Test Cases

#### TC-R001: List All Employees

**Test ID:** TC-R001  
**Requirement:** REST-001  
**Description:** Verify GET /api/v1/employees returns all employees

**Setup:**
- Database has 3 employees
- Repository mocked to return Employee[]

**Execution:**
```php
$request = createMockRequest('GET', '/api/v1/employees');
$response = $controller->list($request, $response);
```

**Verification:**
- Response status: 200
- Body contains 'success': true
- Body contains 'data' array
- Body contains 'total': 3
- All employee objects have required fields

**Expected Result:** Array of 3 employees in response

---

#### TC-R002: List Employees with Status Filter

**Test ID:** TC-R002  
**Requirement:** REST-002  
**Description:** Verify status query parameter filters results

**Setup:**
- Repository mocked with filtered results

**Execution:**
```php
$request = createMockRequest('GET', '/api/v1/employees?status=active');
$response = $controller->list($request, $response);
```

**Verification:**
- Repository received filters ['status' => 'active']
- Only active employees returned

**Expected Result:** Only active employees in data array

---

#### TC-R003: Get Employee by Valid ID

**Test ID:** TC-R003  
**Requirement:** REST-010  
**Description:** Verify GET /api/v1/employees/{id} returns single employee

**Setup:**
- Repository mocked to return Employee(id:5)

**Execution:**
```php
$request = createMockRequest('GET', '/api/v1/employees/5');
$response = $controller->get($request, $response, ['id' => '5']);
```

**Verification:**
- Response status: 200
- Body contains employee with id=5

**Expected Result:** Single employee object

---

#### TC-R004: Get Employee by Invalid ID

**Test ID:** TC-R004  
**Requirement:** REST-011  
**Description:** Verify 404 returned for non-existent employee

**Setup:**
- Repository mocked to return null

**Execution:**
```php
$request = createMockRequest('GET', '/api/v1/employees/999');
$response = $controller->get($request, $response, ['id' => '999']);
```

**Verification:**
- Response status: 404
- Body contains 'success': false
- Body contains 'error': 'Employee not found'

**Expected Result:** 404 with error message

---

#### TC-R005: Create Employee with Valid Data

**Test ID:** TC-R005  
**Requirement:** REST-020  
**Description:** Verify POST creates new employee

**Setup:**
- Repository mocked to return saved Employee with id=10

**Execution:**
```php
$body = json_encode(['first_name' => 'John', 'last_name' => 'Doe']);
$request = createMockRequest('POST', '/api/v1/employees', $body);
$response = $controller->create($request, $response);
```

**Verification:**
- Response status: 201
- Body contains 'data' with 'id': 10

**Expected Result:** Created with id returned

---

#### TC-R006: Create Employee with Invalid JSON

**Test ID:** TC-R006  
**Requirement:** REST-025  
**Description:** Verify 400 returned for invalid JSON

**Execution:**
```php
$body = 'not valid json {';
$request = createMockRequest('POST', '/api/v1/employees', $body);
$response = $controller->create($request, $response);
```

**Verification:**
- Response status: 400
- Body contains 'success': false
- Body contains error about invalid JSON

**Expected Result:** 400 with JSON error

---

#### TC-R007: Update Employee Successfully

**Test ID:** TC-R007  
**Requirement:** REST-030  
**Description:** Verify PUT updates employee fields

**Setup:**
- Repository mocked with existing Employee(id:5)

**Execution:**
```php
$body = json_encode(['department' => 'Sales']);
$request = createMockRequest('PUT', '/api/v1/employees/5', $body);
$response = $controller->update($request, $response, ['id' => '5']);
```

**Verification:**
- Employee entity had setDepartment('Sales') called
- Repository.save() called
- Response status: 200

**Expected Result:** Success with id

---

#### TC-R008: Update Non-existent Employee

**Test ID:** TC-R008  
**Requirement:** REST-031  
**Description:** Verify 404 when updating non-existent employee

**Setup:**
- Repository mocked to return null for findById

**Execution:**
```php
$body = json_encode(['department' => 'Sales']);
$request = createMockRequest('PUT', '/api/v1/employees/999', $body);
$response = $controller->update($request, $response, ['id' => '999']);
```

**Verification:**
- Response status: 404
- Body contains error message

**Expected Result:** 404 error

---

#### TC-R009: Delete Employee Successfully

**Test ID:** TC-R009  
**Requirement:** REST-040  
**Description:** Verify DELETE removes employee

**Setup:**
- Repository mocked to find existing employee

**Execution:**
```php
$request = createMockRequest('DELETE', '/api/v1/employees/5');
$response = $controller->delete($request, $response, ['id' => '5']);
```

**Verification:**
- Repository.delete(5) called
- Response status: 200
- Body contains 'message': 'Employee deleted'

**Expected Result:** Success message

---

#### TC-R010: Delete Non-existent Employee

**Test ID:** TC-R010  
**Requirement:** REST-041  
**Description:** Verify 404 when deleting non-existent employee

**Setup:**
- Repository mocked to return null for findById

**Execution:**
```php
$request = createMockRequest('DELETE', '/api/v1/employees/999');
$response = $controller->delete($request, $response, ['id' => '999']);
```

**Verification:**
- Response status: 404
- delete() not called on repository

**Expected Result:** 404 error

---

### 2.2 SOAP Service Test Cases

#### TC-S001: SOAP getEmployee

**Test ID:** TC-S001  
**Requirement:** SOAP-001  
**Description:** Verify getEmployee returns array

**Setup:**
- Service instantiated with mocked repository
- Repository returns Employee(id:123)

**Execution:**
```php
$result = $service->getEmployee(123);
```

**Verification:**
- Result is array
- Result contains 'id' => 123
- Result contains all employee fields

**Expected Result:** Employee array

---

#### TC-S002: SOAP getEmployee Not Found

**Test ID:** TC-S002  
**Requirement:** SOAP-002  
**Description:** Verify getEmployee returns null when not found

**Setup:**
- Repository returns null

**Execution:**
```php
$result = $service->getEmployee(999);
```

**Verification:**
- Result is null

**Expected Result:** null

---

#### TC-S003: SOAP getEmployeeByEmail

**Test ID:** TC-S003  
**Requirement:** SOAP-010  
**Description:** Verify employee lookup by email

**Setup:**
- Repository returns Employee(email: john@test.com)

**Execution:**
```php
$result = $service->getEmployeeByEmail('john@test.com');
```

**Verification:**
- Repository.findByEmail() called with correct email
- Result is array

**Expected Result:** Employee array or null

---

#### TC-S004: SOAP listEmployees

**Test ID:** TC-S004  
**Requirement:** SOAP-020  
**Description:** Verify listEmployees returns all

**Setup:**
- Repository returns array of 5 employees

**Execution:**
```php
$result = $service->listEmployees();
```

**Verification:**
- Result is array
- Result has 5 elements
- Each element is array

**Expected Result:** Array of 5 employees

---

#### TC-S005: SOAP listEmployees with Status

**Test ID:** TC-S005  
**Requirement:** SOAP-021  
**Description:** Verify status filter works

**Setup:**
- Repository returns only active employees

**Execution:**
```php
$result = $service->listEmployees('active');
```

**Verification:**
- Repository received filters ['status' => 'active']
- Only active employees returned

**Expected Result:** Filtered array

---

#### TC-S006: SOAP createEmployee

**Test ID:** TC-S006  
**Requirement:** SOAP-030  
**Description:** Verify createEmployee via SOAP

**Setup:**
- Repository returns saved Employee with id=15

**Execution:**
```php
$data = [
    'first_name' => 'Jane',
    'last_name' => 'Smith',
    'email' => 'jane@example.com'
];
$result = $service->createEmployee($data);
```

**Verification:**
- Employee entity created with correct data
- Repository.save() called
- Result contains id

**Expected Result:** Employee array with id

---

#### TC-S007: SOAP updateEmployee

**Test ID:** TC-S007  
**Requirement:** SOAP-040  
**Description:** Verify updateEmployee via SOAP

**Setup:**
- Repository returns existing Employee

**Execution:**
```php
$result = $service->updateEmployee(5, ['department' => 'Marketing']);
```

**Verification:**
- Employee found by id
- Setters called with new data
- Repository.save() called
- Result returned

**Expected Result:** Updated employee array

---

#### TC-S008: SOAP updateEmployee Not Found

**Test ID:** TC-S008  
**Requirement:** SOAP-041  
**Description:** Verify update returns null when not found

**Setup:**
- Repository returns null

**Execution:**
```php
$result = $service->updateEmployee(999, ['name' => 'Test']);
```

**Verification:**
- Result is null

**Expected Result:** null

---

#### TC-S009: SOAP deleteEmployee

**Test ID:** TC-S009  
**Requirement:** SOAP-050  
**Description:** Verify deleteEmployee via SOAP

**Setup:**
- Repository returns true for delete

**Execution:**
```php
$result = $service->deleteEmployee(5);
```

**Verification:**
- Repository.delete(5) called
- Result is true

**Expected Result:** true

---

### 2.3 Response Format Test Cases

#### TC-RESP001: Success Response Structure

**Test ID:** TC-RESP001  
**Description:** Verify success responses have correct structure

**Execution:**
```php
$result = $controller->get($request, $response, ['id' => '5']);
```

**Verification:**
- Response has 'success': true
- Response has 'data' object/array
- Response has Content-Type: application/json

**Expected Result:** Correct JSON structure

---

#### TC-RESP002: Error Response Structure

**Test ID:** TC-RESP002  
**Description:** Verify error responses have correct structure

**Execution:**
```php
$result = $controller->get($request, $response, ['id' => '999']);
```

**Verification:**
- Response has 'success': false
- Response has 'error' string
- Response status is 404

**Expected Result:** Error JSON structure

---

## 3. Test Data Matrix

| Test ID | Endpoint | Method | Input | Expected Status |
|---------|----------|--------|-------|----------------|
| TC-R001 | /api/v1/employees | GET | - | 200 |
| TC-R002 | /api/v1/employees?status=active | GET | - | 200 |
| TC-R003 | /api/v1/employees/5 | GET | - | 200 |
| TC-R004 | /api/v1/employees/999 | GET | - | 404 |
| TC-R005 | /api/v1/employees | POST | {first_name, last_name} | 201 |
| TC-R006 | /api/v1/employees | POST | invalid json | 400 |
| TC-R007 | /api/v1/employees/5 | PUT | {department} | 200 |
| TC-R008 | /api/v1/employees/999 | PUT | {department} | 404 |
| TC-R009 | /api/v1/employees/5 | DELETE | - | 200 |
| TC-R010 | /api/v1/employees/999 | DELETE | - | 404 |

---

## 4. Mock Strategy

### 4.1 Repository Mock

```php
$repository = $this->createMock(EmployeeRepository::class);
$repository->method('findById')->willReturn($employee);
$repository->method('findAll')->willReturn([$emp1, $emp2]);
$repository->method('save')->willReturn($savedEmployee);
$repository->method('delete')->willReturn(true);
```

### 4.2 Request Mock

```php
$request = $this->createMock(ServerRequestInterface::class);
$request->method('getQueryParams')->willReturn(['status' => 'active']);
$request->method('getBody')->willReturn($bodyStream);
```

---

## 5. Pass Criteria

| Criterion | Target |
|-----------|--------|
| All test cases pass | 100% |
| Controller coverage | 100% |
| Service coverage | 100% |
| No regressions | 0 failures |

---

*Document Version: 1.0*  
*Last Updated: May 2026*