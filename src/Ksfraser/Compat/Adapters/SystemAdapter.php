<?php

declare(strict_types=1);

namespace Ksfraser\Compat\Adapters;

class SystemAdapter
{
    public const SUITECRM = 'suitecrm';
    public const SUGARCRM = 'sugarcrm';
    public const VTIGER = 'vtiger';
    public const ORANGEHRM = 'orangehrm';
    public const ODOO = 'odoo';
    public const DOLIBARR = 'dolibarr';
    public const DOTPROJECT = 'dotproject';
    public const OPENPROJECT = 'openproject';
    public const LIBREPROJECT = 'libreproject';
    public const FRESHCUSTOMMATES = 'freshcrm';
    public const CAPITANHA = 'capitania';

    private static ?self $instance = null;
    private string $currentSystem;

    private array $systemMappings = [
        self::SUITECRM => [
            'version' => 'v4_1',
            'protocol' => 'rest',
            'module_prefix' => '',
            'date_format' => 'Y-m-d H:i:s',
        ],
        self::SUGARCRM => [
            'version' => 'v4_1',
            'protocol' => 'rest',
            'module_prefix' => '',
            'date_format' => 'Y-m-d H:i:s',
        ],
        self::VTIGER => [
            'version' => 'v1',
            'protocol' => 'rest',
            'module_prefix' => 'vtiger_',
            'date_format' => 'Y-m-d H:i:s',
        ],
        self::ORANGEHRM => [
            'version' => 'o365',
            'protocol' => 'rest',
            'module_prefix' => 'emp_',
            'date_format' => 'Y-m-d',
            'field_overrides' => [
                'employee_id' => 'id',
                'emp_firstname' => 'first_name',
                'emp_lastname' => 'last_name',
                'emp_work_email' => 'email',
                'emp_mobile' => 'mobile',
                'emp_work_telephone' => 'phone',
            ],
        ],
        self::ODOO => [
            'version' => '13',
            'protocol' => 'xmlrpc',
            'module_prefix' => 'res.',
            'date_format' => '%Y-%m-%d %H:%M:%S',
            'module_overrides' => [
                'res.partner' => 'Accounts',
                'hr.employee' => 'Employees',
                'crm.lead' => 'Leads',
                'crm.opportunity' => 'Opportunities',
            ],
        ],
        self::DOLIBARR => [
            'version' => 'v18',
            'protocol' => 'rest',
            'module_prefix' => '',
            'date_format' => 'Y-m-d',
            'module_overrides' => [
                'contacts' => 'Contacts',
                'thirdparties' => 'Accounts',
                'projects' => 'Opportunities',
            ],
        ],
        self::DOTPROJECT => [
            'version' => '2.x',
            'protocol' => 'xmlrpc',
            'module_prefix' => '',
            'date_format' => '%Y-%m-%d %H:%M:%S',
            'field_overrides' => [
                'project_id' => 'id',
                'project_name' => 'name',
            ],
        ],
        self::OPENPROJECT => [
            'version' => 'v3',
            'protocol' => 'rest',
            'module_prefix' => '',
            'date_format' => 'Y-m-d\TH:i:s\Z',
            'module_overrides' => [
                'work_packages' => 'Tasks',
                'projects' => 'Opportunities',
            ],
        ],
        self::LIBREPROJECT => [
            'version' => 'v1',
            'protocol' => 'rest',
            'module_prefix' => '',
            'date_format' => 'Y-m-d\TH:i:s\Z',
            'module_overrides' => [
                'tasks' => 'Tasks',
                'projects' => 'Opportunities',
            ],
        ],
    ];

    public static function getInstance(string $system = self::SUITECRM): self
    {
        if (self::$instance === null || self::$instance->currentSystem !== $system) {
            self::$instance = new self($system);
        }
        return self::$instance;
    }

    public function __construct(string $system = self::SUITECRM)
    {
        $this->currentSystem = $system;
    }

    public function detectSystem(array $requestData): string
    {
        if (isset($requestData['__source']) && $requestData['__source'] !== 'suitecrm') {
            return $requestData['__source'];
        }

        if (isset($requestData['application']) && $requestData['application'] === 'OrangeHRM') {
            return self::ORANGEHRM;
        }

        if (isset($requestData['key']) && isset($requestData['signed'])) {
            return self::ODOO;
        }

        return self::SUITECRM;
    }

    public function getConfig(string $system = null): array
    {
        $system = $system ?? $this->currentSystem;
        return $this->systemMappings[$system] ?? $this->systemMappings[self::SUITECRM];
    }

    public function translateModule(string $module): string
    {
        foreach ($this->systemMappings as $system => $config) {
            if (isset($config['module_overrides'][$module])) {
                return $config['module_overrides'][$module];
            }
        }
        return $module;
    }

    public function translateField(string $module, string $field, string $direction = 'to_ksf'): string
    {
        $config = $this->getConfig();
        $fieldOverrides = $config['field_overrides'] ?? [];

        if ($direction === 'to_ksf' && isset($fieldOverrides[$field])) {
            return $fieldOverrides[$field];
        }

        if ($direction === 'from_ksf') {
            $inverse = array_flip($fieldOverrides);
            if (isset($inverse[$field])) {
                return $inverse[$field];
            }
        }

        return $field;
    }

    public function translateData(string $module, array $data, string $direction = 'to_ksf'): array
    {
        $config = $this->getConfig();
        $result = [];

        foreach ($data as $field => $value) {
            $translatedField = $this->translateField($module, $field, $direction);
            $result[$translatedField] = $value;
        }

        return $result;
    }

    public function formatDate(string $date, string $direction = 'to_ksf'): string
    {
        $config = $this->getConfig();
        $format = $config['date_format'];

        if ($direction === 'to_ksf') {
            $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
            if (!$dt) {
                $dt = \DateTime::createFromFormat('Y-m-d', $date);
            }
            if ($dt) {
                return $dt->format('Y-m-d H:i:s');
            }
            return $date;
        }

        return date($format, strtotime($date));
    }

    public function supportsProtocol(string $protocol): bool
    {
        $config = $this->getConfig();
        return $config['protocol'] === $protocol;
    }

    public function getSupportedSystems(): array
    {
        return array_keys($this->systemMappings);
    }

    public function getSystemInfo(string $system = null): array
    {
        $system = $system ?? $this->currentSystem;
        $config = $this->getConfig($system);

        return [
            'system' => $system,
            'version' => $config['version'] ?? 'unknown',
            'protocol' => $config['protocol'] ?? 'rest',
            'modules' => count($config['module_overrides'] ?? []) > 0
                ? array_values($config['module_overrides'])
                : $this->getDefaultModules(),
        ];
    }

    private function getDefaultModules(): array
    {
        return [
            'Accounts',
            'Contacts',
            'Leads',
            'Opportunities',
            'Cases',
            'Tasks',
            'Meetings',
            'Calls',
            'Emails',
            'Users',
            'Employees',
            'Campaigns',
            'Notes',
            'Documents',
        ];
    }
}