<?php

declare(strict_types=1);

namespace Ksfraser\Compat\Adapters;

use Ksfraser\Compat\v4_1\CRMController;
use Ksfraser\Compat\v4_1\AuthHandler;

class OrangeHRMAdapter
{
    private SystemAdapter $system;
    private CRMController $crm;
    private AuthHandler $auth;

    public function __construct(?SystemAdapter $system = null, ?CRMController $crm = null, ?AuthHandler $auth = null)
    {
        $this->system = $system ?? SystemAdapter::getInstance(SystemAdapter::ORANGEHRM);
        $this->crm = $crm ?? new CRMController($auth);
        $this->auth = $auth ?? new AuthHandler();
    }

    public function authenticate(string $username, string $password): array
    {
        $session = $this->auth->login([
            'user_auth' => [
                'user_name' => $username,
                'pass_clear' => $password,
            ],
        ]);

        return [
            'Authenticated' => true,
            'token' => $session['id'],
            'userId' => $session['name_value_list']['user_id']['value'] ?? 1,
        ];
    }

    public function getEmployee(string $employeeId): array
    {
        $result = $this->crm->getEntry([
            'session' => $this->getCurrentSession(),
            'module_name' => 'Employees',
            'id' => $employeeId,
        ]);

        return $this->toOrangeHRMFormat($result);
    }

    public function getEmployees(array $filters = []): array
    {
        $query = $this->buildQuery($filters);

        $result = $this->crm->getEntryList([
            'session' => $this->getCurrentSession(),
            'module_name' => 'Employees',
            'query' => $query,
            'max_results' => $filters['limit'] ?? 50,
        ]);

        return array_map(fn($e) => $this->toOrangeHRMFormat($e), $result['entry_list'] ?? []);
    }

    public function saveEmployee(array $data): array
    {
        $ksfData = $this->fromOrangeHRMFormat($data);

        $result = $this->crm->setEntry([
            'session' => $this->getCurrentSession(),
            'module_name' => 'Employees',
            'name_value_list' => $this->arrayToNameValueList($ksfData),
        ]);

        return [
            'id' => $result['id'],
            'message' => 'Successfully saved',
        ];
    }

    private function toOrangeHRMFormat(array $entry): array
    {
        $nvl = $entry['name_value_list'] ?? [];

        $result = [];
        foreach ($nvl as $item) {
            $ksfField = $item['name'];
            $value = $item['value'];
            $ohrmField = $this->system->translateField('Employees', $ksfField, 'from_ksf');
            $result[$ohrmField] = $value;
        }

        return array_merge([
            'empNumber' => $result['id'] ?? '',
            'empFirstName' => $result['first_name'] ?? '',
            'empLastName' => $result['last_name'] ?? '',
            'empWorkEmail' => $result['email'] ?? '',
            'empMobile' => $result['mobile'] ?? '',
            'empWorkTelephone' => $result['phone'] ?? '',
        ], $result);
    }

    private function fromOrangeHRMFormat(array $data): array
    {
        return [
            'id' => $data['empNumber'] ?? null,
            'first_name' => $data['empFirstName'] ?? '',
            'last_name' => $data['empLastName'] ?? '',
            'email' => $data['empWorkEmail'] ?? '',
            'mobile' => $data['empMobile'] ?? '',
            'phone' => $data['empWorkTelephone'] ?? '',
        ];
    }

    private function buildQuery(array $filters): string
    {
        $conditions = [];

        if (!empty($filters['empFirstName'])) {
            $conditions[] = "first_name LIKE '%" . addslashes($filters['empFirstName']) . "%'";
        }
        if (!empty($filters['empLastName'])) {
            $conditions[] = "last_name LIKE '%" . addslashes($filters['empLastName']) . "%'";
        }
        if (!empty($filters['empWorkEmail'])) {
            $conditions[] = "email = '" . addslashes($filters['empWorkEmail']) . "'";
        }

        return implode(' AND ', $conditions);
    }

    private function arrayToNameValueList(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = ['name' => $key, 'value' => $value];
        }
        return $result;
    }

    private function getCurrentSession(): string
    {
        return '';
    }
}

class OdooAdapter
{
    private SystemAdapter $system;
    private CRMController $crm;
    private AuthHandler $auth;
    private ?string $uid = null;
    private ?string $session = null;

    public function __construct(?SystemAdapter $system = null, ?CRMController $crm = null, ?AuthHandler $auth = null)
    {
        $this->system = $system ?? SystemAdapter::getInstance(SystemAdapter::ODOO);
        $this->crm = $crm ?? new CRMController($auth);
        $this->auth = $auth ?? new AuthHandler();
    }

    public function authenticate(string $db, string $username, string $password): array
    {
        try {
            $this->auth->login([
                'user_auth' => [
                    'user_name' => $username,
                    'pass_clear' => $password,
                ],
            ]);

            $this->uid = '1';
            $this->session = bin2hex(random_bytes(32));

            return [
                'uid' => $this->uid,
                'session' => $this->session,
            ];
        } catch (\Exception $e) {
            return [
                'faultCode' => 1,
                'faultString' => $e->getMessage(),
            ];
        }
    }

    public function execute(string $model, string $method, array $args, array $kwargs = []): array
    {
        if (!$this->uid) {
            return ['faultCode' => 1, 'faultString' => 'Not authenticated'];
        }

        $ksfModule = $this->system->translateModule($model);

        return match ($method) {
            'search' => $this->search($ksfModule, $args, $kwargs),
            'search_read' => $this->searchRead($ksfModule, $args, $kwargs),
            'read' => $this->read($ksfModule, $args, $kwargs),
            'create' => $this->create($ksfModule, $args),
            'write' => $this->write($ksfModule, $args),
            'unlink' => $this->unlink($ksfModule, $args),
            default => ['faultCode' => 1, 'faultString' => "Unknown method: $method"],
        };
    }

    private function search(string $module, array $args, array $kwargs): array
    {
        $domain = $kwargs['domain'] ?? [];
        $offset = $kwargs['offset'] ?? 0;
        $limit = $kwargs['limit'] ?? 100;

        $query = $this->domainToQuery($domain);

        $result = $this->crm->getEntryList([
            'session' => $this->session,
            'module_name' => $module,
            'query' => $query,
            'offset' => $offset,
            'max_results' => $limit,
        ]);

        return array_column($result['entry_list'] ?? [], 'id');
    }

    private function searchRead(string $module, array $args, array $kwargs): array
    {
        $domain = $kwargs['domain'] ?? [];
        $fields = $kwargs['fields'] ?? [];
        $offset = $kwargs['offset'] ?? 0;
        $limit = $kwargs['limit'] ?? 100;

        $ids = $this->search($module, $args, ['domain' => $domain, 'offset' => $offset, 'limit' => $limit]);

        if (empty($ids)) {
            return [];
        }

        $result = $this->crm->getEntryList([
            'session' => $this->session,
            'module_name' => $module,
            'max_results' => $limit,
        ]);

        $records = [];
        foreach ($result['entry_list'] ?? [] as $entry) {
            $record = ['id' => $entry['id']];
            foreach ($entry['name_value_list'] as $nvl) {
                if (empty($fields) || in_array($nvl['name'], $fields)) {
                    $record[$nvl['name']] = $nvl['value'];
                }
            }
            $records[] = $record;
        }

        return $records;
    }

    private function read(string $module, array $args, array $kwargs): array
    {
        $ids = $args[0] ?? [];
        $fields = $kwargs['fields'] ?? [];

        $records = [];
        foreach ($ids as $id) {
            $result = $this->crm->getEntry([
                'session' => $this->session,
                'module_name' => $module,
                'id' => $id,
            ]);

            if (!empty($result)) {
                $record = ['id' => $result['id']];
                foreach ($result['name_value_list'] as $nvl) {
                    if (empty($fields) || in_array($nvl['name'], $fields)) {
                        $record[$nvl['name']] = $nvl['value'];
                    }
                }
                $records[] = $record;
            }
        }

        return $records;
    }

    private function create(string $module, array $args): int
    {
        $data = $args[0] ?? [];

        $result = $this->crm->setEntry([
            'session' => $this->session,
            'module_name' => $module,
            'name_value_list' => $this->dictToNameValueList($data),
        ]);

        return (int)$result['id'];
    }

    private function write(string $module, array $args): bool
    {
        $ids = $args[0] ?? [];
        $data = $args[1] ?? [];

        foreach ($ids as $id) {
            $this->crm->setEntry([
                'session' => $this->session,
                'module_name' => $module,
                'name_value_list' => $this->dictToNameValueList(array_merge(['id' => $id], $data)),
            ]);
        }

        return true;
    }

    private function unlink(string $module, array $args): bool
    {
        $ids = $args[0] ?? [];

        foreach ($ids as $id) {
            $this->crm->deleteEntry([
                'session' => $this->session,
                'module_name' => $module,
                'id' => $id,
            ]);
        }

        return true;
    }

    private function domainToQuery(array $domain): string
    {
        if (empty($domain)) {
            return '';
        }

        $conditions = [];
        foreach ($domain as $clause) {
            if (count($clause) >= 3) {
                [$field, $operator, $value] = $clause;
                $conditions[] = $this->operatorToSql($field, $operator, $value);
            }
        }

        return implode(' AND ', $conditions);
    }

    private function operatorToSql(string $field, string $op, mixed $value): string
    {
        $value = addslashes($value);
        return match ($op) {
            '=' => "$field = '$value'",
            '!=' => "$field != '$value'",
            'like', 'ilike' => "$field LIKE '%$value%'",
            '>' => "$field > '$value'",
            '<' => "$field < '$value'",
            '>=' => "$field >= '$value'",
            '<=' => "$field <= '$value'",
            default => "$field = '$value'",
        };
    }

    private function dictToNameValueList(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = ['name' => $key, 'value' => $value];
        }
        return $result;
    }
}

class DolibarrAdapter
{
    private SystemAdapter $system;
    private CRMController $crm;
    private AuthHandler $auth;

    public function __construct(?SystemAdapter $system = null, ?CRMController $crm = null, ?AuthHandler $auth = null)
    {
        $this->system = $system ?? SystemAdapter::getInstance(SystemAdapter::DOLIBARR);
        $this->crm = $crm ?? new CRMController($auth);
        $this->auth = $auth ?? new AuthHandler();
    }

    public function getThirdParties(array $filters = []): array
    {
        $result = $this->crm->getEntryList([
            'session' => $this->getSession(),
            'module_name' => 'Accounts',
            'max_results' => $filters['limit'] ?? 100,
        ]);

        return array_map(fn($e) => $this->toDolibarrFormat($e), $result['entry_list'] ?? []);
    }

    public function getContacts(array $filters = []): array
    {
        $result = $this->crm->getEntryList([
            'session' => $this->getSession(),
            'module_name' => 'Contacts',
            'max_results' => $filters['limit'] ?? 100,
        ]);

        return array_map(fn($e) => $this->toDolibarrContactFormat($e), $result['entry_list'] ?? []);
    }

    public function getProjects(array $filters = []): array
    {
        $result = $this->crm->getEntryList([
            'session' => $this->getSession(),
            'module_name' => 'Opportunities',
            'max_results' => $filters['limit'] ?? 100,
        ]);

        return array_map(fn($e) => $this->toDolibarrProjectFormat($e), $result['entry_list'] ?? []);
    }

    public function authenticate(string $login, string $password): array
    {
        try {
            $session = $this->auth->login([
                'user_auth' => [
                    'user_name' => $login,
                    'pass_clear' => $password,
                ],
            ]);

            return [
                'success' => true,
                'token' => $session['id'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function toDolibarrFormat(array $entry): array
    {
        $data = $this->extractNameValueList($entry);

        return [
            'id' => $data['id'] ?? '',
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'zip' => $data['postal_code'] ?? '',
            'town' => $data['city'] ?? '',
            'country' => $data['country'] ?? '',
        ];
    }

    private function toDolibarrContactFormat(array $entry): array
    {
        $data = $this->extractNameValueList($entry);

        return [
            'id' => $data['id'] ?? '',
            'civility' => $data['civility'] ?? '',
            'lastname' => $data['last_name'] ?? '',
            'firstname' => $data['first_name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'phone_mobile' => $data['mobile'] ?? '',
        ];
    }

    private function toDolibarrProjectFormat(array $entry): array
    {
        $data = $this->extractNameValueList($entry);

        return [
            'id' => $data['id'] ?? '',
            'ref' => $data['id'] ?? '',
            'title' => $data['title'] ?? $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'date_start' => $data['close_date'] ?? '',
            'status' => $data['stage'] ?? '',
        ];
    }

    private function extractNameValueList(array $entry): array
    {
        $result = [];
        foreach ($entry['name_value_list'] ?? [] as $nvl) {
            $result[$nvl['name']] = $nvl['value'];
        }
        return $result;
    }

    private function getSession(): string
    {
        return '';
    }
}

class OpenProjectAdapter
{
    private SystemAdapter $system;
    private CRMController $crm;
    private AuthHandler $auth;

    public function __construct(?SystemAdapter $system = null, ?CRMController $crm = null, ?AuthHandler $auth = null)
    {
        $this->system = $system ?? SystemAdapter::getInstance(SystemAdapter::OPENPROJECT);
        $this->crm = $crm ?? new CRMController($auth);
        $this->auth = $auth ?? new AuthHandler();
    }

    public function getProjects(): array
    {
        $result = $this->crm->getEntryList([
            'session' => $this->getSession(),
            'module_name' => 'Opportunities',
            'max_results' => 100,
        ]);

        return array_map(fn($e) => $this->toOpenProjectFormat($e), $result['entry_list'] ?? []);
    }

    public function getWorkPackages(int $projectId = null): array
    {
        $module = 'Tasks';
        $query = $projectId ? "customer_id = '$projectId'" : '';

        $result = $this->crm->getEntryList([
            'session' => $this->getSession(),
            'module_name' => $module,
            'query' => $query,
            'max_results' => 100,
        ]);

        return array_map(fn($e) => $this->toOpenProjectWPFormat($e), $result['entry_list'] ?? []);
    }

    private function toOpenProjectFormat(array $entry): array
    {
        $data = $this->extractNameValueList($entry);

        return [
            'id' => $data['id'] ?? '',
            'name' => $data['title'] ?? $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'status' => $this->mapStatus($data['stage'] ?? 'new'),
            'createdAt' => $data['created_at'] ?? '',
            'updatedAt' => $data['updated_at'] ?? '',
        ];
    }

    private function toOpenProjectWPFormat(array $entry): array
    {
        $data = $this->extractNameValueList($entry);

        return [
            'id' => $data['id'] ?? '',
            'subject' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'status' => $this->mapStatus($data['status'] ?? 'new'),
            'priority' => $this->mapPriority($data['priority'] ?? 'medium'),
            'startDate' => $data['start_date'] ?? '',
            'dueDate' => $data['due_date'] ?? '',
            'estimatedHours' => $data['hours'] ?? 0,
        ];
    }

    private function mapStatus(string $ksfStatus): array
    {
        $mapping = [
            'new' => ['id' => 1, 'name' => 'New'],
            'in_progress' => ['id' => 2, 'name' => 'In Progress'],
            'pending' => ['id' => 3, 'name' => 'Pending'],
            'completed' => ['id' => 4, 'name' => 'Closed'],
            'cancelled' => ['id' => 5, 'name' => 'Cancelled'],
        ];

        return $mapping[$ksfStatus] ?? ['id' => 1, 'name' => ucfirst($ksfStatus)];
    }

    private function mapPriority(string $ksfPriority): array
    {
        $mapping = [
            'low' => ['id' => 1, 'name' => 'Low'],
            'medium' => ['id' => 2, 'name' => 'Normal'],
            'high' => ['id' => 3, 'name' => 'High'],
            'urgent' => ['id' => 4, 'name' => 'Urgent'],
        ];

        return $mapping[$ksfPriority] ?? ['id' => 2, 'name' => 'Normal'];
    }

    private function extractNameValueList(array $entry): array
    {
        $result = [];
        foreach ($entry['name_value_list'] ?? [] as $nvl) {
            $result[$nvl['name']] = $nvl['value'];
        }
        return $result;
    }

    private function getSession(): string
    {
        return '';
    }
}

class DotProjectAdapter
{
    private SystemAdapter $system;
    private CRMController $crm;
    private AuthHandler $auth;

    public function __construct(?SystemAdapter $system = null, ?CRMController $crm = null, ?AuthHandler $auth = null)
    {
        $this->system = $system ?? SystemAdapter::getInstance(SystemAdapter::DOTPROJECT);
        $this->crm = $crm ?? new CRMController($auth);
        $this->auth = $auth ?? new AuthHandler();
    }

    public function xmlrpcCall(string $method, array $params): array
    {
        if (!$this->auth->isAuthenticated()) {
            return ['faultCode' => 1, 'faultString' => 'Not authenticated'];
        }

        return match ($method) {
            'project.list' => $this->listProjects(),
            'project.get' => $this->getProject($params['project_id'] ?? 0),
            'task.list' => $this->listTasks($params['project_id'] ?? null),
            'user.list' => $this->listUsers(),
            default => ['faultCode' => 1, 'faultString' => "Unknown method: $method"],
        };
    }

    private function listProjects(): array
    {
        $result = $this->crm->getEntryList([
            'session' => $this->getSession(),
            'module_name' => 'Opportunities',
            'max_results' => 100,
        ]);

        return array_map(fn($e) => $this->toDotProjectFormat($e), $result['entry_list'] ?? []);
    }

    private function getProject(int $id): array
    {
        $result = $this->crm->getEntry([
            'session' => $this->getSession(),
            'module_name' => 'Opportunities',
            'id' => $id,
        ]);

        return $this->toDotProjectFormat($result);
    }

    private function listTasks(?int $projectId = null): array
    {
        $query = $projectId ? "customer_id = '$projectId'" : '';

        $result = $this->crm->getEntryList([
            'session' => $this->getSession(),
            'module_name' => 'Tasks',
            'query' => $query,
            'max_results' => 100,
        ]);

        return array_map(fn($e) => $this->toDotProjectTaskFormat($e), $result['entry_list'] ?? []);
    }

    private function listUsers(): array
    {
        $result = $this->crm->getEntryList([
            'session' => $this->getSession(),
            'module_name' => 'Employees',
            'max_results' => 100,
        ]);

        return array_map(fn($e) => $this->toDotProjectUserFormat($e), $result['entry_list'] ?? []);
    }

    private function toDotProjectFormat(array $entry): array
    {
        $data = $this->extractNameValueList($entry);

        return [
            'project_id' => $data['id'] ?? '',
            'project_name' => $data['title'] ?? $data['name'] ?? '',
            'project_description' => $data['description'] ?? '',
            'project_start_date' => $data['created_at'] ?? '',
            'project_end_date' => $data['close_date'] ?? '',
            'project_status' => $this->mapDotProjectStatus($data['stage'] ?? 'new'),
        ];
    }

    private function toDotProjectTaskFormat(array $entry): array
    {
        $data = $this->extractNameValueList($entry);

        return [
            'task_id' => $data['id'] ?? '',
            'task_name' => $data['title'] ?? '',
            'task_description' => $data['description'] ?? '',
            'task_start_date' => $data['start_date'] ?? '',
            'task_end_date' => $data['due_date'] ?? '',
            'task_status' => $this->mapDotProjectStatus($data['status'] ?? 'new'),
            'task_priority' => $data['priority'] ?? 2,
        ];
    }

    private function toDotProjectUserFormat(array $entry): array
    {
        $data = $this->extractNameValueList($entry);

        return [
            'user_id' => $data['id'] ?? '',
            'contact_first_name' => $data['first_name'] ?? '',
            'contact_last_name' => $data['last_name'] ?? '',
            'contact_email' => $data['email'] ?? '',
        ];
    }

    private function mapDotProjectStatus(string $ksfStatus): int
    {
        $mapping = [
            'new' => 1,
            'in_progress' => 2,
            'pending' => 3,
            'completed' => 4,
            'cancelled' => 5,
        ];

        return $mapping[$ksfStatus] ?? 1;
    }

    private function extractNameValueList(array $entry): array
    {
        $result = [];
        foreach ($entry['name_value_list'] ?? [] as $nvl) {
            $result[$nvl['name']] = $nvl['value'];
        }
        return $result;
    }

    private function getSession(): string
    {
        return '';
    }
}