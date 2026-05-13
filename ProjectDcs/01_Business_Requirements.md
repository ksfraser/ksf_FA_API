# Business Requirements - FrontAccounting API Adapter (ksf_FA_API)

**Module:** ksf_FA_API  
**Version:** 1.0.0  
**Date:** May 2026  
**Author:** Ksfraser Development Team  

---

## 1. Executive Summary

The FrontAccounting API Adapter (ksf_FA_API) provides a dual-interface integration layer between FrontAccounting's internal business logic and external systems. The module exposes RESTful API endpoints and SOAP web services to enable programmatic access to employee data, facilitating integration with HR systems, third-party applications, and custom development projects.

This adapter follows the Ksfraser architecture pattern of separating business logic (domain entities and repositories) from platform-specific adapters (REST controllers and SOAP services), ensuring maintainability and testability across the FrontAccounting ecosystem.

---

## 2. Problem Statement

### 2.1 Integration Challenges

Organizations using FrontAccounting face significant challenges when integrating with external systems:

1. **No Standard API**: FrontAccounting historically lacks a documented, versioned API for external access, forcing developers to interact directly with database tables.

2. **Direct Database Coupling**: Third-party integrations often bypass business logic, leading to data integrity issues and duplicate code paths.

3. **Inconsistent Data Formats**: External systems expecting standardized data formats (JSON, XML) cannot easily exchange information with FA's PHP array-based structure.

4. **SOAP vs REST Dilemma**: Modern applications prefer REST APIs while legacy systems may require SOAP; organizations need both options.

5. **Authentication Complexity**: No standardized authentication mechanism for API access.

### 2.2 Business Impact

- Integration projects require significant custom development time
- Risk of data corruption from bypassing business logic
- Difficulty connecting FA with modern cloud services
- Vendor lock-in due to custom integration code

---

## 3. Project Scope

### 3.1 In Scope

| Component | Description |
|-----------|-------------|
| REST API v1 | Versioned REST endpoints for employee operations |
| SOAP Service | WSDL-based web service for legacy integration |
| Employee CRUD | Create, Read, Update, Delete operations |
| JSON Response Format | Standardized response structure |
| Error Handling | Consistent error response format |
| PSR-7 Compliance | HTTP request/response interfaces |
| Authentication Layer | API key or session-based authentication |
| Routing System | Slim framework-based route definitions |
| Unit Tests | Test coverage for controller and service classes |

### 3.2 Out of Scope

- Authentication UI (handled by FA core)
- Rate limiting (future enhancement)
- API documentation auto-generation
- OAuth implementation
- Batch operations
- File upload endpoints
- Complex queries (filtering beyond status/department)

---

## 4. Features and Capabilities

### 4.1 REST API Features

#### 4.1.1 API Versioning

All REST endpoints are versioned under `/api/v1/` prefix:

```
GET    /api/v1/employees          - List all employees
GET    /api/v1/employees/{id}     - Get single employee
POST   /api/v1/employees          - Create new employee
PUT    /api/v1/employees/{id}    - Update employee
DELETE /api/v1/employees/{id}     - Delete employee
```

#### 4.1.2 Standard Response Format

All responses follow a consistent JSON structure:

```json
{
  "success": true,
  "data": { ... },
  "total": 42
}
```

#### 4.1.3 Filtering Support

List endpoint supports query parameters:

```
GET /api/v1/employees?status=active
GET /api/v1/employees?department=Sales
GET /api/v1/employees?status=active&department=Sales
```

### 4.2 SOAP Service Features

#### 4.2.1 WSDL-Based Service

The SOAP service exposes a machine-readable WSDL:

```
GET /soap/employee?wsdl
```

#### 4.2.2 Operations

```xml
getEmployee(int $id): array
getEmployeeByEmail(string $email): array
listEmployees(string $status = null): array
createEmployee(array $data): array
updateEmployee(int $id, array $data): array
deleteEmployee(int $id): bool
```

#### 4.2.3 PHP-Native Client

The SOAP service is designed for PHP clients using native SOAPClient:

```php
$client = new SoapClient("http://fa-server/soap/employee?wsdl");
$employee = $client->getEmployee(123);
```

### 4.3 Authentication

| Method | Description | Use Case |
|--------|-------------|----------|
| Session Auth | Uses FA session for logged-in users | Internal integrations |
| API Key | X-API-Key header for external access | Third-party integration |

---

## 5. Use Cases

### 5.1 External HR System Integration

**Scenario:** Organization runs separate HR system that needs to sync employee data with FA

```
HR System → REST API → FA Employee Data
```

**Flow:**
1. HR system authenticates via API key
2. Creates employee in FA via POST /api/v1/employees
3. Updates employee via PUT /api/v1/employees/{id}
4. Queries employees via GET /api/v1/employees

### 5.2 Legacy ERP Integration

**Scenario:** Older ERP system requires SOAP access to employee data

```
Legacy ERP → SOAP Service → FA Employee Data
```

**Flow:**
1. Legacy system connects via WSDL
2. Queries employee by email
3. Updates employee information
4. Receives confirmation response

### 5.3 Single Page Application

**Scenario:** Custom SPA needs to display employee list

**Flow:**
1. SPA makes AJAX call to REST endpoint
2. Receives JSON response
3. Renders employee data in UI

---

## 6. Integration Dependencies

### 6.1 Internal Dependencies

| Module | Purpose |
|--------|---------|
| `Ksfraser\HRM\Entity\Employee` | Employee domain entity |
| `Ksfraser\HRM\Repository\EmployeeRepository` | Data access layer |
| `Psr\Http\Message\ServerRequestInterface` | PSR-7 request |
| `Psr\Http\Message\ResponseInterface` | PSR-7 response |
| `Slim\Routing\RouteCollectorProxy` | Route definitions |

### 6.2 External Dependencies

| Package | Purpose | Version |
|---------|---------|---------|
| `slim/slim` | Routing framework | ^4.0 |
| `psr/http-message` | HTTP interfaces | ^1.0 |
| `php` | Runtime | >= 7.3 |

### 6.3 FrontAccounting Dependencies

| Component | Purpose |
|-----------|---------|
| FA Database | Employee data storage |
| FA Session | Authentication |
| FA Database Functions | db_query, db_fetch, etc. |

---

## 7. Technical Constraints

### 7.1 PHP Version Requirements

- **Minimum:** PHP 7.3
- **Recommended:** PHP 8.0+
- **Target:** PHP 8.2

### 7.2 Performance Requirements

- API response time < 500ms for typical requests
- Support concurrent requests (non-blocking)
- Memory footprint < 16MB per request

### 7.3 Security Requirements

- All API endpoints require authentication
- Input validation on all parameters
- SQL injection prevention via prepared statements
- XSS prevention via output escaping

---

## 8. Success Criteria

| Criterion | Measurement |
|-----------|-------------|
| REST endpoints functional | All CRUD operations work |
| SOAP service operational | WSDL accessible, operations work |
| Response format consistent | All responses follow standard structure |
| Error handling complete | All error cases return proper JSON |
| Unit tests passing | 100% pass rate on CI |
| Integration tests passing | End-to-end flow works |

---

## 9. Future Roadmap

| Version | Feature | Description |
|---------|---------|-------------|
| 1.1.0 | Departments Endpoint | Add department CRUD |
| 1.2.0 | Bulk Operations | Batch create/update/delete |
| 1.3.0 | OAuth2 Support | OAuth for external integrations |
| 2.0.0 | GraphQL API | Alternative query interface |
| 2.0.0 | API Key Management UI | Admin panel for API keys |

---

## 10. Glossary

| Term | Definition |
|------|------------|
| REST API | Representational State Transfer - HTTP-based API style |
| SOAP | Simple Object Access Protocol - XML-based web service |
| WSDL | Web Services Description Language - XML contract |
| PSR-7 | PHP Standard Recommendation for HTTP messages |
| CRUD | Create, Read, Update, Delete operations |
| Employee Entity | Domain object representing an employee |
| Repository | Pattern for data access abstraction |

---

*Document Version: 1.0*  
*Last Updated: May 2026*