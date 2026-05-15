<?php

declare(strict_types=1);

namespace Ksfraser\Compat\v4_1;

use Ksfraser\Exceptions\CRM\RecordNotFoundException;
use Ksfraser\Exceptions\CRM\ValidationException;

class CRMController
{
    private AuthHandler $auth;
    private ModuleMapper $mapper;

    public function __construct(?AuthHandler $auth = null, ?ModuleMapper $mapper = null)
    {
        $this->auth = $auth ?? new AuthHandler();
        $this->mapper = $mapper ?? ModuleMapper::getInstance();
    }

    public function handleRequest(string $method, array $params): array
    {
        return match ($method) {
            'login' => $this->login($params),
            'logout' => $this->logout($params),
            'get_entry' => $this->getEntry($params),
            'get_entry_list' => $this->getEntryList($params),
            'get_entries_count' => $this->getEntriesCount($params),
            'set_entry' => $this->setEntry($params),
            'set_entries' => $this->setEntries($params),
            'set_relationship' => $this->setRelationship($params),
            'set_relationships' => $this->setRelationships($params),
            'delete' => $this->deleteEntry($params),
            'get_module_fields' => $this->getModuleFields($params),
            'get_module_field_md5' => $this->getModuleFieldMd5($params),
            'get_available_modules' => $this->getAvailableModules($params),
            'get_user_id' => $this->getUserId($params),
            'get_user_team_id' => $this->getUserTeamId($params),
            'seamless_login' => $this->seamlessLogin($params),
            'is_loopback_available' => $this->isLoopbackAvailable($params),
            default => throw new \BadMethodCallException("Unknown method: $method"),
        };
    }

    private function requireSession(array $params): array
    {
        $session = $params['session'] ?? '';
        if (empty($session)) {
            throw new \InvalidArgumentException('Session ID is required');
        }

        $user = $this->auth->getUserFromSession($session);
        if (!$user) {
            throw new \InvalidArgumentException('Invalid session');
        }

        return $user;
    }

    public function login(array $params): array
    {
        try {
            $result = $this->auth->login($params);
            return [
                'id' => $result['id'],
                'module_name' => $result['module_name'],
                'name_value_list' => $this->toNameValueList($result['name_value_list']),
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Invalid Login',
                'description' => $e->getMessage(),
                'number' => 10,
            ];
        }
    }

    public function logout(array $params): array
    {
        $session = $params['session'] ?? '';
        $this->auth->logout($session);

        return [
            'id' => -1,
            'module_name' => '',
            'name_value_list' => [],
        ];
    }

    public function getEntry(array $params): array
    {
        $this->requireSession($params);

        $moduleName = $params['module_name'] ?? '';
        $id = $params['id'] ?? '';
        $selectFields = $params['select_fields'] ?? [];
        $linkNameToFieldsArray = $params['link_name_to_fields_array'] ?? [];

        if (empty($moduleName) || empty($id)) {
            throw new \InvalidArgumentException('Module name and ID are required');
        }

        if (!$this->mapper->isKnownModule($moduleName)) {
            return [
                'id' => $id,
                'module_name' => $moduleName,
                'name_value_list' => [],
                'relationship_list' => [],
            ];
        }

        $data = $this->fetchRecord($moduleName, $id);

        if (empty($data)) {
            throw new RecordNotFoundException("Record not found: $id in $moduleName");
        }

        $crmData = $this->mapper->fromCrmFormat($moduleName, $data);

        return [
            'id' => $id,
            'module_name' => $moduleName,
            'name_value_list' => $this->toNameValueList($crmData),
            'relationship_list' => [],
        ];
    }

    public function getEntryList(array $params): array
    {
        $this->requireSession($params);

        $moduleName = $params['module_name'] ?? '';
        $query = $params['query'] ?? '';
        $orderBy = $params['order_by'] ?? '';
        $offset = $params['offset'] ?? 0;
        $selectFields = $params['select_fields'] ?? [];
        $linkNameToFieldsArray = $params['link_name_to_fields_array'] ?? [];
        $maxResults = $params['max_results'] ?? 1000;
        $deleted = $params['deleted'] ?? false;

        if (empty($moduleName)) {
            throw new \InvalidArgumentException('Module name is required');
        }

        if (!$this->mapper->isKnownModule($moduleName)) {
            return [
                'result_count' => 0,
                'total_count' => 0,
                'next_offset' => -1,
                'entry_list' => [],
                'relationship_list' => [],
            ];
        }

        $records = $this->fetchRecords($moduleName, $query, $orderBy, $offset, (int)$maxResults);

        $entryList = [];
        foreach ($records as $record) {
            $crmData = $this->mapper->fromCrmFormat($moduleName, $record);
            $entryList[] = [
                'id' => $record['id'] ?? $record[$this->mapper->getFieldMapping($moduleName, 'id')] ?? '',
                'module_name' => $moduleName,
                'name_value_list' => $this->toNameValueList($crmData),
                'date_modified' => $record['updated_at'] ?? $record['modified_date'] ?? '',
                'date_entered' => $record['created_at'] ?? $record['date_entered'] ?? '',
                'deleted' => false,
            ];
        }

        $totalCount = count($entryList);
        $nextOffset = count($records) >= $maxResults ? $offset + $maxResults : -1;

        return [
            'result_count' => count($entryList),
            'total_count' => $totalCount,
            'next_offset' => $nextOffset,
            'entry_list' => $entryList,
            'relationship_list' => [],
        ];
    }

    public function getEntriesCount(array $params): array
    {
        $this->requireSession($params);

        $moduleName = $params['module_name'] ?? '';
        $query = $params['query'] ?? '';

        if (empty($moduleName)) {
            throw new \InvalidArgumentException('Module name is required');
        }

        $count = $this->countRecords($moduleName, $query);

        return [
            'result_count' => $count,
        ];
    }

    public function setEntry(array $params): array
    {
        $this->requireSession($params);

        $moduleName = $params['module_name'] ?? '';
        $nameValueList = $params['name_value_list'] ?? [];

        if (empty($moduleName)) {
            throw new \InvalidArgumentException('Module name is required');
        }

        if (empty($nameValueList)) {
            throw new ValidationException('name_value_list is required');
        }

        $data = $this->fromNameValueList($nameValueList);
        $ksfData = $this->mapper->toCrmFormat($moduleName, $data);

        $id = $this->saveRecord($moduleName, $ksfData);

        return [
            'id' => $id,
            'module_name' => $moduleName,
            'name_value_list' => $nameValueList,
        ];
    }

    public function setEntries(array $params): array
    {
        $this->requireSession($params);

        $moduleName = $params['module_name'] ?? '';
        $nameValueLists = $params['name_value_lists'] ?? [];

        if (empty($moduleName)) {
            throw new \InvalidArgumentException('Module name is required');
        }

        $results = [];
        foreach ($nameValueLists as $nvl) {
            $params = ['module_name' => $moduleName, 'name_value_list' => $nvl];
            $results[] = $this->setEntry($params);
        }

        return [
            'ids' => array_column($results, 'id'),
            'status' => count($results) > 0 ? 'success' : 'error',
        ];
    }

    public function setRelationship(array $params): array
    {
        $this->requireSession($params);

        return [
            'created' => 1,
            'deleted' => 0,
        ];
    }

    public function setRelationships(array $params): array
    {
        $this->requireSession($params);

        return [
            'created' => count($params['module_ids'] ?? []),
            'failed' => 0,
            'deleted' => 0,
        ];
    }

    public function deleteEntry(array $params): array
    {
        $this->requireSession($params);

        $moduleName = $params['module_name'] ?? '';
        $id = $params['id'] ?? '';

        if (empty($moduleName) || empty($id)) {
            throw new \InvalidArgumentException('Module name and ID are required');
        }

        $this->deleteRecord($moduleName, $id);

        return [
            'id' => $id,
            'deleted' => true,
        ];
    }

    public function getModuleFields(array $params): array
    {
        $this->requireSession($params);

        $moduleName = $params['module_name'] ?? '';

        if (empty($moduleName)) {
            throw new \InvalidArgumentException('Module name is required');
        }

        $fields = $this->getModuleFieldDefinitions($moduleName);

        return [
            'module_name' => $moduleName,
            'module_fields' => $fields,
            'link_fields' => [],
        ];
    }

    public function getModuleFieldMd5(array $params): array
    {
        $fields = $this->getModuleFields($params);
        return [
            'id' => md5(json_encode($fields)),
            'md5' => md5(json_encode($fields)),
        ];
    }

    public function getAvailableModules(array $params): array
    {
        $this->requireSession($params);

        $modules = [];
        foreach ($this->mapper->getSupportedModules() as $module) {
            $modules[] = [
                'module_key' => $module,
                'module_label' => $this->getModuleLabel($module),
                'acls' => ['access', 'view', 'list', 'edit', 'delete', 'import', 'export'],
            ];
        }

        return [
            'modules' => $modules,
        ];
    }

    public function getUserId(array $params): array
    {
        $user = $this->requireSession($params);

        return [
            'id' => $user['id'],
        ];
    }

    public function getUserTeamId(array $params): array
    {
        $this->requireSession($params);

        return [
            'id' => 1,
            'team_id' => '1',
            'team_name' => 'Global',
        ];
    }

    public function seamlessLogin(array $params): array
    {
        $data = $params['rest_data'] ?? $params['user_auth'] ?? [];
        return $this->login(['user_auth' => $data]);
    }

    public function isLoopbackAvailable(array $params): array
    {
        return [
            'loopback_available' => true,
        ];
    }

    private function toNameValueList(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = [
                'name' => $key,
                'value' => $this->formatValue($value),
            ];
        }
        return $result;
    }

    private function fromNameValueList(array $nameValueList): array
    {
        $result = [];
        foreach ($nameValueList as $item) {
            if (is_array($item)) {
                $name = $item['name'] ?? $item[0] ?? '';
                $value = $item['value'] ?? $item[1] ?? '';
            } else {
                continue;
            }
            $result[$name] = $value;
        }
        return $result;
    }

    private function formatValue(mixed $value): mixed
    {
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        return $value;
    }

    private function fetchRecord(string $moduleName, string $id): array
    {
        if (!function_exists('db_query') || !defined('TB_PREF')) {
            return $this->mockFetchRecord($moduleName, $id);
        }

        $mapping = $this->mapper->getMapping($moduleName);
        if (!$mapping) {
            return [];
        }

        $table = $this->getTableName($moduleName);
        $idField = $mapping['fields']['id'] ?? 'id';

        $sql = "SELECT * FROM " . TB_PREF . $table . " WHERE " . db_escape($idField) . " = " . db_escape($id) . " LIMIT 1";

        $result = @db_query($sql);
        if (!$result) {
            return [];
        }

        return @db_fetch_assoc($result) ?: [];
    }

    private function fetchRecords(string $moduleName, string $query, string $orderBy, int $offset, int $limit): array
    {
        if (!function_exists('db_query') || !defined('TB_PREF')) {
            return $this->mockFetchRecords($moduleName, $limit);
        }

        $mapping = $this->mapper->getMapping($moduleName);
        if (!$mapping) {
            return [];
        }

        $table = $this->getTableName($moduleName);

        $sql = "SELECT * FROM " . TB_PREF . $table;

        if (!empty($query)) {
            $normalizedQuery = $this->convertWhereClause($query, $moduleName);
            $sql .= " WHERE " . $normalizedQuery;
        }

        if (!empty($orderBy)) {
            $orderField = $this->convertOrderBy($orderBy, $moduleName);
            $sql .= " ORDER BY " . $orderField;
        }

        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $result = @db_query($sql);
        if (!$result) {
            return [];
        }

        $records = [];
        while ($row = @db_fetch_assoc($result)) {
            $records[] = $row;
        }

        return $records;
    }

    private function countRecords(string $moduleName, string $query): int
    {
        if (!function_exists('db_query') || !defined('TB_PREF')) {
            return 0;
        }

        $table = $this->getTableName($moduleName);

        $sql = "SELECT COUNT(*) as cnt FROM " . TB_PREF . $table;

        if (!empty($query)) {
            $normalizedQuery = $this->convertWhereClause($query, $moduleName);
            $sql .= " WHERE " . $normalizedQuery;
        }

        $result = @db_query($sql);
        if (!$result) {
            return 0;
        }

        $row = @db_fetch_assoc($result);
        return (int)($row['cnt'] ?? 0);
    }

    private function saveRecord(string $moduleName, array $data): string
    {
        $mapping = $this->mapper->getMapping($moduleName);
        if (!$mapping) {
            return '';
        }

        $table = $this->getTableName($moduleName);
        $idField = $mapping['fields']['id'] ?? 'id';

        $id = $data[$idField] ?? '';

        if (!function_exists('db_query') || !defined('TB_PREF')) {
            return $id ?: uniqid('rec_');
        }

        if (!empty($id)) {
            $sets = [];
            foreach ($data as $field => $value) {
                if ($field !== $idField) {
                    $sets[] = db_escape($field) . " = " . db_escape($value);
                }
            }

            if (!empty($sets)) {
                $sql = "UPDATE " . TB_PREF . $table . " SET " . implode(', ', $sets) . " WHERE " . db_escape($idField) . " = " . db_escape($id);
                @db_query($sql);
            }
        } else {
            $cols = implode(', ', array_map(fn($k) => db_escape($k), array_keys($data)));
            $vals = implode(', ', array_map(fn($v) => db_escape($v), array_values($data)));
            $sql = "INSERT INTO " . TB_PREF . $table . " (" . $cols . ") VALUES (" . $vals . ")";
            @db_query($sql);
            $id = function_exists('db_insert_id') ? db_insert_id() : uniqid('rec_');
        }

        return $id;
    }

    private function deleteRecord(string $moduleName, string $id): bool
    {
        $mapping = $this->mapper->getMapping($moduleName);
        if (!$mapping) {
            return false;
        }

        if (!function_exists('db_query') || !defined('TB_PREF')) {
            return true;
        }

        $table = $this->getTableName($moduleName);
        $idField = $mapping['fields']['id'] ?? 'id';

        $sql = "DELETE FROM " . TB_PREF . $table . " WHERE " . db_escape($idField) . " = " . db_escape($id);
        return @db_query($sql) !== false;
    }

    private function getTableName(string $moduleName): string
    {
        $tableMap = [
            'Accounts' => 'debtors',
            'Contacts' => 'crm_contacts',
            'Leads' => 'crm_leads',
            'Opportunities' => 'crm_opportunities',
            'Cases' => 'support_tickets',
            'Notes' => 'notes',
            'Tasks' => 'project_tasks',
            'Meetings' => 'calendar_events',
            'Calls' => 'phone_calls',
            'Emails' => 'email_messages',
            'Users' => 'users',
            'Employees' => 'hr_employees',
            'Campaigns' => 'marketing_campaigns',
            'Documents' => 'documents',
        ];

        return $tableMap[$moduleName] ?? strtolower($moduleName);
    }

    private function getModuleFieldDefinitions(string $moduleName): array
    {
        $fieldDefinitions = [
            'Accounts' => [
                ['name' => 'id', 'type' => 'id', 'label' => 'ID'],
                ['name' => 'name', 'type' => 'name', 'label' => 'Account Name', 'required' => true],
                ['name' => 'phone_office', 'type' => 'phone', 'label' => 'Office Phone'],
                ['name' => 'phone_fax', 'type' => 'phone', 'label' => 'Fax'],
                ['name' => 'email1', 'type' => 'email', 'label' => 'Email Address'],
                ['name' => 'billing_address_street', 'type' => 'varchar', 'label' => 'Billing Street'],
                ['name' => 'billing_address_city', 'type' => 'varchar', 'label' => 'Billing City'],
                ['name' => 'billing_address_state', 'type' => 'varchar', 'label' => 'Billing State'],
                ['name' => 'billing_address_postalcode', 'type' => 'varchar', 'label' => 'Billing Postal Code'],
                ['name' => 'billing_address_country', 'type' => 'varchar', 'label' => 'Billing Country'],
                ['name' => 'industry', 'type' => 'enum', 'label' => 'Industry'],
                ['name' => 'website', 'type' => 'url', 'label' => 'Website'],
                ['name' => 'date_entered', 'type' => 'datetime', 'label' => 'Date Created'],
                ['name' => 'date_modified', 'type' => 'datetime', 'label' => 'Date Modified'],
            ],
            'Contacts' => [
                ['name' => 'id', 'type' => 'id', 'label' => 'ID'],
                ['name' => 'first_name', 'type' => 'varchar', 'label' => 'First Name', 'required' => true],
                ['name' => 'last_name', 'type' => 'varchar', 'label' => 'Last Name', 'required' => true],
                ['name' => 'phone_work', 'type' => 'phone', 'label' => 'Work Phone'],
                ['name' => 'phone_mobile', 'type' => 'phone', 'label' => 'Mobile Phone'],
                ['name' => 'email1', 'type' => 'email', 'label' => 'Email Address'],
                ['name' => 'account_name', 'type' => 'relate', 'label' => 'Account Name', 'link' => 'accounts'],
                ['name' => 'title', 'type' => 'varchar', 'label' => 'Title'],
                ['name' => 'department', 'type' => 'varchar', 'label' => 'Department'],
                ['name' => 'date_entered', 'type' => 'datetime', 'label' => 'Date Created'],
                ['name' => 'date_modified', 'type' => 'datetime', 'label' => 'Date Modified'],
            ],
            'Leads' => [
                ['name' => 'id', 'type' => 'id', 'label' => 'ID'],
                ['name' => 'first_name', 'type' => 'varchar', 'label' => 'First Name', 'required' => true],
                ['name' => 'last_name', 'type' => 'varchar', 'label' => 'Last Name', 'required' => true],
                ['name' => 'email1', 'type' => 'email', 'label' => 'Email Address'],
                ['name' => 'phone_work', 'type' => 'phone', 'label' => 'Phone'],
                ['name' => 'company', 'type' => 'varchar', 'label' => 'Company'],
                ['name' => 'status', 'type' => 'enum', 'label' => 'Status'],
                ['name' => 'lead_source', 'type' => 'enum', 'label' => 'Lead Source'],
                ['name' => 'description', 'type' => 'text', 'label' => 'Description'],
            ],
            'Opportunities' => [
                ['name' => 'id', 'type' => 'id', 'label' => 'ID'],
                ['name' => 'name', 'type' => 'name', 'label' => 'Opportunity Name', 'required' => true],
                ['name' => 'account_name', 'type' => 'relate', 'label' => 'Account Name'],
                ['name' => 'amount', 'type' => 'currency', 'label' => 'Amount'],
                ['name' => 'date_closed', 'type' => 'date', 'label' => 'Expected Close Date'],
                ['name' => 'sales_stage', 'type' => 'enum', 'label' => 'Sales Stage'],
                ['name' => 'probability', 'type' => 'int', 'label' => 'Probability (%)'],
                ['name' => 'description', 'type' => 'text', 'label' => 'Description'],
            ],
            'Cases' => [
                ['name' => 'id', 'type' => 'id', 'label' => 'ID'],
                ['name' => 'name', 'type' => 'name', 'label' => 'Case Number', 'required' => true],
                ['name' => 'account_name', 'type' => 'relate', 'label' => 'Account Name'],
                ['name' => 'status', 'type' => 'enum', 'label' => 'Status'],
                ['name' => 'priority', 'type' => 'enum', 'label' => 'Priority'],
                ['name' => 'description', 'type' => 'text', 'label' => 'Description'],
                ['name' => 'resolution', 'type' => 'text', 'label' => 'Resolution'],
            ],
        ];

        return $fieldDefinitions[$moduleName] ?? [
            ['name' => 'id', 'type' => 'id', 'label' => 'ID'],
            ['name' => 'name', 'type' => 'name', 'label' => 'Name'],
            ['name' => 'date_entered', 'type' => 'datetime', 'label' => 'Date Created'],
            ['name' => 'date_modified', 'type' => 'datetime', 'label' => 'Date Modified'],
        ];
    }

    private function getModuleLabel(string $moduleName): string
    {
        $labels = [
            'Accounts' => 'Accounts',
            'Contacts' => 'Contacts',
            'Leads' => 'Leads',
            'Opportunities' => 'Opportunities',
            'Cases' => 'Cases',
            'Notes' => 'Notes',
            'Tasks' => 'Tasks',
            'Meetings' => 'Meetings',
            'Calls' => 'Calls',
            'Emails' => 'Emails',
            'Users' => 'Users',
            'Employees' => 'Employees',
            'Campaigns' => 'Campaigns',
            'Documents' => 'Documents',
        ];

        return $labels[$moduleName] ?? $moduleName;
    }

    private function convertWhereClause(string $query, string $moduleName): string
    {
        $mapping = $this->mapper->getMapping($moduleName);
        if (!$mapping) {
            return $query;
        }

        $fields = $mapping['fields'];
        $result = $query;

        foreach ($fields as $crmField => $ksfField) {
            $result = preg_replace(
                '/' . preg_quote($crmField, '/') . '\s*=/',
                db_escape($ksfField) . ' =',
                $result
            );
            $result = preg_replace(
                '/' . preg_quote($crmField, '/') . '\s+LIKE/i',
                db_escape($ksfField) . ' LIKE',
                $result
            );
        }

        return $result;
    }

    private function convertOrderBy(string $orderBy, string $moduleName): string
    {
        $parts = explode(' ', trim($orderBy));
        $field = trim($parts[0]);
        $direction = strtoupper($parts[1] ?? 'ASC');

        $mapping = $this->mapper->getMapping($moduleName);
        if ($mapping && isset($mapping['fields'][$field])) {
            $field = $mapping['fields'][$field];
        }

        return db_escape($field) . ' ' . ($direction === 'DESC' ? 'DESC' : 'ASC');
    }

    private function mockFetchRecord(string $moduleName, string $id): array
    {
        $mockData = [
            'Accounts' => [
                'id' => $id,
                'name' => 'Test Account',
                'phone_office' => '555-1234',
                'email1' => 'test@example.com',
                'industry' => 'Technology',
                'date_entered' => date('Y-m-d H:i:s'),
                'date_modified' => date('Y-m-d H:i:s'),
            ],
            'Contacts' => [
                'id' => $id,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'phone_work' => '555-5678',
                'email1' => 'john.doe@example.com',
                'account_name' => 'Test Account',
                'date_entered' => date('Y-m-d H:i:s'),
            ],
            'Leads' => [
                'id' => $id,
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email1' => 'jane@example.com',
                'company' => 'Test Corp',
                'status' => 'New',
                'lead_source' => 'Web',
            ],
            'Opportunities' => [
                'id' => $id,
                'name' => 'Big Sale',
                'amount' => 50000,
                'date_closed' => date('Y-m-d', strtotime('+30 days')),
                'sales_stage' => 'Prospecting',
                'probability' => 20,
            ],
        ];

        return $mockData[$moduleName] ?? ['id' => $id, 'name' => "Mock $moduleName Record"];
    }

    private function mockFetchRecords(string $moduleName, int $limit): array
    {
        $records = [];
        for ($i = 0; $i < min($limit, 5); $i++) {
            $records[] = $this->mockFetchRecord($moduleName, uniqid("$moduleName-"));
        }
        return $records;
    }
}