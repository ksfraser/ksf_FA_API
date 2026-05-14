# FA_API - UAT Plan

**Document ID:** UAT-FAAPI-001  
**Module:** ksf_FA_API  
**Version:** 1.0.0  

---

## 1. UAT Objectives

Verify that:
1. REST endpoints return correct responses
2. JSON format matches specification
3. Error responses are consistent
4. CRUD operations function correctly

## 2. Test Scenarios

| Scenario | Expected | Tester |
|----------|----------|--------|
| UAT-001: Get all employees | Valid JSON array | Integration Dev |
| UAT-002: Get employee by ID | Correct employee data | Integration Dev |
| UAT-003: Create new employee | Employee created, 201 returned | Integration Dev |
| UAT-004: Update employee | Changes persisted | Integration Dev |
| UAT-005: Delete employee | Employee removed | Integration Dev |
| UAT-006: Filter by department | Filtered results | Integration Dev |

## 3. Sign-Off

| Role | Name | Date |
|------|------|------|
| Integration Developer | | |
| QA Lead | | |