<?php
/**
 * FA_API Module Hooks for FrontAccounting
 */

define('SS_API', 124 << 8);

class hooks_ksf_FA_API extends hooks {

    private function ensure_composer_dependencies(): void {
        $module_dir = dirname(__FILE__);
        $autoload_path = $module_dir . '/vendor/autoload.php';
        
        if (!file_exists($autoload_path)) {
            $composer_path = $module_dir . '/composer.json';
            if (file_exists($composer_path)) {
                chdir($module_dir);
                $output = [];
                $return_code = 0;
                exec('composer install --no-interaction --prefer-dist 2>&1', $output, $return_code);
                if ($return_code !== 0) {
                    error_log('KSF Module: composer install failed: ' . implode("\n", $output));
                }
            }
        }
    }
    var $module_name = 'ksf_FA_API';

    private function ensure_composer_dependencies(): void {
        $module_dir = dirname(__FILE__);
        $autoload_path = $module_dir . '/vendor/autoload.php';
        
        if (!file_exists($autoload_path)) {
            $composer_path = $module_dir . '/composer.json';
            if (file_exists($composer_path)) {
                chdir($module_dir);
                $output = [];
                $return_code = 0;
                exec('composer install --no-interaction --prefer-dist 2>&1', $output, $return_code);
                if ($return_code !== 0) {
                    error_log('KSF Module: composer install failed: ' . implode("\n", $output));
                }
            }
        }
    }
    var $version = '1.0.0';

    private function ensure_composer_dependencies(): void {
        $module_dir = dirname(__FILE__);
        $autoload_path = $module_dir . '/vendor/autoload.php';
        
        if (!file_exists($autoload_path)) {
            $composer_path = $module_dir . '/composer.json';
            if (file_exists($composer_path)) {
                chdir($module_dir);
                $output = [];
                $return_code = 0;
                exec('composer install --no-interaction --prefer-dist 2>&1', $output, $return_code);
                if ($return_code !== 0) {
                    error_log('KSF Module: composer install failed: ' . implode("\n", $output));
                }
            }
        }
    }

    function activate_extension($company, $check_only=true) {
        if ($check_only) {
            return true;
        }
        $this->ensure_api_schema();
        return true;
    }

    private function table_exists($table) {
        $sql = "SHOW TABLES LIKE " . db_escape($table);
        $res = db_query($sql, 'Failed checking table existence');
        return db_num_rows($res) > 0;
    }

    private function ensure_api_schema() {
        $tables = array(
            TB_PREF . "ksf_api_logs" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "ksf_api_logs` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `endpoint` VARCHAR(255) NOT NULL,
                    `method` VARCHAR(10) NOT NULL,
                    `request_data` TEXT,
                    `response_data` TEXT,
                    `status_code` INT(3) DEFAULT NULL,
                    `error_message` TEXT,
                    `user_id` VARCHAR(100) DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_endpoint` (`endpoint`),
                    KEY `idx_user` (`user_id`),
                    KEY `idx_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        foreach ($tables as $table_name => $sql) {
            db_query($sql, "Could not create API table: $table_name");
        }
    }

    function install_access() {
        $security_sections[SS_API] = _("API Management");
        $security_areas['SA_APIVIEW'] = array(SS_API | 1, _("View API"));
        $security_areas['SA_APIMANAGE'] = array(SS_API | 2, _("Manage API"));
        return array($security_areas, $security_sections);
    }
}