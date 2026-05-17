-- API Module Schema
-- Uses @TB_PREF@ for table prefix

CREATE TABLE IF NOT EXISTS @TB_PREF@ksf_api_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    request_data TEXT,
    response_data TEXT,
    status_code INT(3) DEFAULT NULL,
    error_message TEXT,
    user_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_endpoint (endpoint),
    KEY idx_user (user_id),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
