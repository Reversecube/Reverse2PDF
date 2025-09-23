<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Upgrade {
    
    private $current_version;
    private $db_version;
    
    public function __construct() {
        $this->current_version = REVERSE2PDF_VERSION;
        $this->db_version = get_option('reverse2pdf_version', '0.0.0');
        
        add_action('admin_init', array($this, 'check_upgrade'));
    }
    
    /**
     * Check if upgrade is needed
     */
    public function check_upgrade() {
        if (version_compare($this->db_version, $this->current_version, '<')) {
            $this->perform_upgrade();
        }
    }
    
    /**
     * Perform upgrade
     */
    private function perform_upgrade() {
        $logger = new Reverse2PDF_Logger();
        $logger->log('info', 'Starting plugin upgrade', array(
            'from_version' => $this->db_version,
            'to_version' => $this->current_version
        ));
        
        // Version-specific upgrades
        if (version_compare($this->db_version, '1.1.0', '<')) {
            $this->upgrade_to_110();
        }
        
        if (version_compare($this->db_version, '2.0.0', '<')) {
            $this->upgrade_to_200();
        }
        
        // Update version in database
        update_option('reverse2pdf_version', $this->current_version);
        
        $logger->log('success', 'Plugin upgrade completed', array(
            'new_version' => $this->current_version
        ));
        
        // Clear any caches
        $this->clear_caches();
        
        do_action('reverse2pdf_upgraded', $this->db_version, $this->current_version);
    }
    
    /**
     * Upgrade to version 1.1.0
     */
    private function upgrade_to_110() {
        global $wpdb;
        
        // Add new columns if they don't exist
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;
        
        $columns = $wpdb->get_col("DESCRIBE $table");
        
        if (!in_array('form_type', $columns)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN form_type VARCHAR(50) DEFAULT NULL AFTER settings");
        }
        
        if (!in_array('form_id', $columns)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN form_id VARCHAR(50) DEFAULT NULL AFTER form_type");
        }
    }
    
    /**
     * Upgrade to version 2.0.0
     */
    private function upgrade_to_200() {
        global $wpdb;
        
        // Create new tables for version 2.0
        $charset_collate = $wpdb->get_charset_collate();
        
        // Integrations table
        $table_integrations = $wpdb->prefix . REVERSE2PDF_TABLE_INTEGRATIONS;
        $sql = "CREATE TABLE IF NOT EXISTS $table_integrations (
            id int(11) NOT NULL AUTO_INCREMENT,
            form_type varchar(50) NOT NULL,
            form_id varchar(50) NOT NULL,
            template_id int(11) NOT NULL,
            trigger_action varchar(100) DEFAULT 'form_submit',
            conditions longtext,
            settings longtext,
            active tinyint(1) DEFAULT 1,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_type (form_type),
            KEY form_id (form_id),
            KEY template_id (template_id),
            KEY active (active),
            UNIQUE KEY unique_integration (form_type, form_id, template_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Logs table
        $table_logs = $wpdb->prefix . REVERSE2PDF_TABLE_LOGS;
        $sql2 = "CREATE TABLE IF NOT EXISTS $table_logs (
            id int(11) NOT NULL AUTO_INCREMENT,
            template_id int(11) NOT NULL,
            user_id int(11) DEFAULT 0,
            action varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'success',
            message text,
            data longtext,
            ip_address varchar(45),
            user_agent text,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY template_id (template_id),
            KEY user_id (user_id),
            KEY action (action),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql2);
        
        // Update default settings
        $settings = get_option('reverse2pdf_settings', array());
        $new_defaults = array(
            'enable_cache' => true,
            'cache_lifetime' => 3600,
            'enable_debug' => false,
            'auto_cleanup' => true,
            'cleanup_days' => 7
        );
        
        $settings = array_merge($new_defaults, $settings);
        update_option('reverse2pdf_settings', $settings);
    }
    
    /**
     * Clear caches after upgrade
     */
    private function clear_caches() {
        // Clear template cache
        $cache_dir = wp_upload_dir()['basedir'] . '/reverse2pdf/cache';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        // Clear WordPress object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear any transients
        delete_transient('reverse2pdf_template_list');
        delete_transient('reverse2pdf_form_list');
    }
    
    /**
     * Get upgrade notices
     */
    public function get_upgrade_notices() {
        $notices = array();
        
        if (version_compare($this->db_version, '2.0.0', '<')) {
            $notices[] = array(
                'type' => 'info',
                'message' => __('Reverse2PDF 2.0 includes new features like advanced integrations and logging. Your existing templates will be automatically updated.', 'reverse2pdf')
            );
        }
        
        return $notices;
    }
    
    /**
     * Backup templates before upgrade
     */
    private function backup_templates() {
        global $wpdb;
        
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;
        $templates = $wpdb->get_results("SELECT * FROM $table");
        
        $backup_dir = wp_upload_dir()['basedir'] . '/reverse2pdf/backups';
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        $backup_file = $backup_dir . '/templates_backup_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($backup_file, json_encode($templates, JSON_PRETTY_PRINT));
        
        return $backup_file;
    }
}

// Initialize upgrade handler
new Reverse2PDF_Upgrade();
?>
