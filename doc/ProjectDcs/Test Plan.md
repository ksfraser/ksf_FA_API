# FA_API - Test Plan

**Document ID:** TP-FAAPI-001  
**Module:** ksf_FA_API  
**Version:** 1.0.0  

---

## 1. Test Scope

- REST endpoint responses
- JSON serialization
- CRUD operations
- Error handling
- Filter functionality

## 2. Test Cases

| ID | Test | Endpoint | Expected |
|----|------|----------|----------|
| TC-001 | testListEmployees | GET /employees | JSON array returned |
| TC-002 | testListEmployees_Filtered | GET /employees?status=active | Filtered results |
| TC-003 | testGetEmployee | GET /employees/1 | Single employee JSON |
| TC-004 | testGetEmployee_NotFound | GET /employees/999 | 404 response |
| TC-005 | testCreateEmployee | POST /employees | 201 with ID |
| TC-006 | testCreateEmployee_InvalidJson | POST /employees | 400 error |
| TC-007 | testUpdateEmployee | PUT /employees/1 | 200 with ID |
| TC-008 | testDeleteEmployee | DELETE /employees/1 | 200 success |
| TC-009 | testResponseFormat | GET /employees | Contains success/data/total |