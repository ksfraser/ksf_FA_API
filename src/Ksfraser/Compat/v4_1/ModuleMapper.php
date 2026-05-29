<?php

declare(strict_types=1);

namespace Ksfraser\Compat\v4_1;

class ModuleMapper
{
    public const MODULE_MAPPINGS = [
        'Accounts' => [
            'ksf_module' => 'ksf_CRM',
            'entity' => 'Customer',
            'fields' => [
                'id' => 'debtor_no',
                'name' => 'name',
                'phone_office' => 'phone',
                'phone_fax' => 'fax',
                'email1' => 'email',
                'billing_address_street' => 'address',
                'billing_address_city' => 'city',
                'billing_address_state' => 'province',
                'billing_address_postalcode' => 'postal_code',
                'billing_address_country' => 'country',
                'industry' => 'industry',
                'website' => 'website',
                'employees' => 'staff_count',
            ],
        ],
        'Contacts' => [
            'ksf_module' => 'ksf_CRM',
            'entity' => 'Contact',
            'fields' => [
                'id' => 'id',
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'phone_work' => 'phone',
                'phone_mobile' => 'mobile',
                'phone_home' => 'home_phone',
                'phone_fax' => 'fax',
                'email1' => 'email',
                'email2' => 'secondary_email',
                'primary_address_street' => 'address',
                'primary_address_city' => 'city',
                'primary_address_state' => 'province',
                'primary_address_postalcode' => 'postal_code',
                'primary_address_country' => 'country',
                'title' => 'job_title',
                'department' => 'department',
                'account_id' => 'customer_id',
                'account_name' => 'customer_name',
                'birthdate' => 'date_of_birth',
                'lead_source' => 'source',
            ],
        ],
        'Leads' => [
            'ksf_module' => 'ksf_CRM',
            'entity' => 'Lead',
            'fields' => [
                'id' => 'id',
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'phone_work' => 'phone',
                'phone_mobile' => 'mobile',
                'email1' => 'email',
                'company' => 'company_name',
                'status' => 'status',
                'lead_source' => 'source',
                'description' => 'notes',
                'assigned_user_id' => 'assigned_to',
            ],
        ],
        'Opportunities' => [
            'ksf_module' => 'ksf_CRM',
            'entity' => 'Opportunity',
            'fields' => [
                'id' => 'id',
                'name' => 'title',
                'account_id' => 'customer_id',
                'account_name' => 'customer_name',
                'amount' => 'amount',
                'currency_id' => 'currency_code',
                'date_closed' => 'close_date',
                'sales_stage' => 'stage',
                'probability' => 'probability',
                'description' => 'description',
                'lead_source' => 'source',
                'campaign_id' => 'campaign_id',
                'next_step' => 'next_step',
                'assigned_user_id' => 'assigned_to',
            ],
        ],
        'Cases' => [
            'ksf_module' => 'ksf_SupportTickets',
            'entity' => 'Ticket',
            'fields' => [
                'id' => 'id',
                'name' => 'subject',
                'account_id' => 'customer_id',
                'account_name' => 'customer_name',
                'status' => 'status',
                'priority' => 'priority',
                'description' => 'description',
                'resolution' => 'resolution',
                'assigned_user_id' => 'assigned_to',
                'created_date' => 'created_at',
                'modified_date' => 'updated_at',
            ],
        ],
        'Notes' => [
            'ksf_module' => 'ksf_Notes',
            'entity' => 'Note',
            'fields' => [
                'id' => 'id',
                'name' => 'title',
                'parent_type' => 'entity_type',
                'parent_id' => 'entity_id',
                'description' => 'content',
                'filename' => 'attachment_name',
                'file_url' => 'attachment_url',
                'assigned_user_id' => 'created_by',
                'date_entered' => 'created_at',
                'date_modified' => 'updated_at',
            ],
        ],
        'Tasks' => [
            'ksf_module' => 'ksf_ProjectManagement',
            'entity' => 'Task',
            'fields' => [
                'id' => 'id',
                'name' => 'title',
                'status' => 'status',
                'priority' => 'priority',
                'description' => 'description',
                'parent_type' => 'entity_type',
                'parent_id' => 'entity_id',
                'date_due' => 'due_date',
                'date_start' => 'start_date',
                'assigned_user_id' => 'assigned_to',
            ],
        ],
        'Meetings' => [
            'ksf_module' => 'ksf_Calendar',
            'entity' => 'Event',
            'fields' => [
                'id' => 'id',
                'name' => 'title',
                'date_start' => 'start_time',
                'date_end' => 'end_time',
                'duration_hours' => 'duration',
                'status' => 'status',
                'location' => 'location',
                'description' => 'description',
                'assigned_user_id' => 'assigned_to',
                'parent_type' => 'entity_type',
                'parent_id' => 'entity_id',
            ],
        ],
        'Calls' => [
            'ksf_module' => 'ksf_AsteriskPBX',
            'entity' => 'Call',
            'fields' => [
                'id' => 'id',
                'name' => 'subject',
                'status' => 'status',
                'direction' => 'direction',
                'date_start' => 'start_time',
                'date_end' => 'end_time',
                'duration_hours' => 'duration',
                'description' => 'notes',
                'assigned_user_id' => 'assigned_to',
                'parent_type' => 'entity_type',
                'parent_id' => 'entity_id',
            ],
        ],
        'Emails' => [
            'ksf_module' => 'ksf_EmailManager',
            'entity' => 'Email',
            'fields' => [
                'id' => 'id',
                'name' => 'subject',
                'from_addr' => 'from_address',
                'to_addrs' => 'to_addresses',
                'cc_addrs' => 'cc_addresses',
                'bcc_addrs' => 'bcc_addresses',
                'description' => 'body_html',
                'description_html' => 'body_html',
                'status' => 'status',
                'assigned_user_id' => 'assigned_to',
            ],
        ],
        'Users' => [
            'ksf_module' => 'ksf_HRM',
            'entity' => 'Employee',
            'fields' => [
                'id' => 'id',
                'user_name' => 'username',
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'email1' => 'email',
                'phone_work' => 'phone',
                'phone_mobile' => 'mobile',
                'employee_status' => 'status',
                'department' => 'department',
                'title' => 'job_title',
                'reports_to_id' => 'manager_id',
            ],
        ],
        'Employees' => [
            'ksf_module' => 'ksf_HRM',
            'entity' => 'Employee',
            'fields' => [
                'id' => 'id',
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'email' => 'email',
                'phone_work' => 'phone',
                'phone_mobile' => 'mobile',
                'department' => 'department',
                'title' => 'job_title',
                'reports_to_id' => 'manager_id',
                'employee_number' => 'employee_id',
            ],
        ],
        'Campaigns' => [
            'ksf_module' => 'ksf_Marketing',
            'entity' => 'Campaign',
            'fields' => [
                'id' => 'id',
                'name' => 'name',
                'status' => 'status',
                'type' => 'campaign_type',
                'budget' => 'budget',
                'expected_cost' => 'expected_cost',
                'actual_cost' => 'actual_cost',
                'expected_revenue' => 'expected_revenue',
                'start_date' => 'start_date',
                'end_date' => 'end_date',
                'description' => 'description',
                'assigned_user_id' => 'assigned_to',
            ],
        ],
        'Documents' => [
            'ksf_module' => 'ksf_Documents',
            'entity' => 'Document',
            'fields' => [
                'id' => 'id',
                'name' => 'filename',
                'document_name' => 'title',
                'description' => 'description',
                'category_id' => 'category',
                'subcategory_id' => 'subcategory',
                'status_id' => 'status',
                'active_date' => 'publish_date',
                'exp_date' => 'expiry_date',
                'assigned_user_id' => 'assigned_to',
            ],
        ],
    ];

    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getMapping(string $moduleName): ?array
    {
        return self::MODULE_MAPPINGS[$moduleName] ?? null;
    }

    public function getKsfModule(string $crmModule): ?string
    {
        return self::MODULE_MAPPINGS[$crmModule]['ksf_module'] ?? null;
    }

    public function getEntity(string $crmModule): ?string
    {
        return self::MODULE_MAPPINGS[$crmModule]['entity'] ?? null;
    }

    public function isKnownModule(string $moduleName): bool
    {
        return isset(self::MODULE_MAPPINGS[$moduleName]);
    }

    public function getSupportedModules(): array
    {
        return array_keys(self::MODULE_MAPPINGS);
    }

    public function getFieldMapping(string $crmModule, string $ksfField): ?string
    {
        $mapping = self::MODULE_MAPPINGS[$crmModule]['fields'] ?? [];
        $inverse = array_flip($mapping);
        return $inverse[$ksfField] ?? null;
    }

    public function toCrmFormat(string $crmModule, array $ksfData): array
    {
        $mapping = self::MODULE_MAPPINGS[$crmModule]['fields'] ?? [];
        $result = [];

        foreach ($ksfData as $field => $value) {
            $crmField = array_search($field, $mapping, true);
            if ($crmField !== false) {
                $result[$crmField] = $value;
            }
        }

        return $result;
    }

    public function fromCrmFormat(string $crmModule, array $crmData): array
    {
        $mapping = self::MODULE_MAPPINGS[$crmModule]['fields'] ?? [];
        $result = [];

        foreach ($crmData as $field => $value) {
            $ksfField = $mapping[$field] ?? null;
            if ($ksfField) {
                $result[$ksfField] = $value;
            } else {
                $result[$field] = $value;
            }
        }

        return $result;
    }
}