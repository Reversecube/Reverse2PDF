<?php
/**
 * Plugin Name: Reverse2PDF Pro
 * Plugin URI: https://reversecube.net/reverse2pdf
 * Description: Complete PDF generation solution - Visual builder, form integrations, conditional logic, math expressions, loops, and 25+ shortcodes
 * Version: 2.0.0
 * Author: Reversecube
 * Author URI: https://reversecube.net
 * License: Commercial
 * License URI: https://reversecube.net/license
 * Text Domain: reverse2pdf
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package Reverse2PDF
 * @author Reversecube
 * @version 2.0.0
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed.');
}

// Plugin security check
if (!function_exists('add_action')) {
    echo 'WordPress not detected. Plugin cannot run independently.';
    exit;
}

// Define constants
define('REVERSE2PDF_VERSION', '2.0.0');
define('REVERSE2PDF_MIN_PHP_VERSION', '7.4');
define('REVERSE2PDF_MIN_WP_VERSION', '5.0');
define('REVERSE2PDF_PLUGIN_FILE', __FILE__);
define('REVERSE2PDF_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('REVERSE2PDF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REVERSE2PDF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('REVERSE2PDF_PLUGIN_SLUG', dirname(REVERSE2PDF_PLUGIN_BASENAME));

// Database table names
define('REVERSE2PDF_TABLE_TEMPLATES', 'reverse2pdf_templates');
define('REVERSE2PDF_TABLE_LOGS', 'reverse2pdf_logs');
define('REVERSE2PDF_TABLE_INTEGRATIONS', 'reverse2pdf_integrations');

/**
 * Enhanced Requirements Check
 */
function reverse2pdf_check_requirements() {
    $errors = array();
    
    // Check PHP version
    if (version_compare(PHP_VERSION, REVERSE2PDF_MIN_PHP_VERSION, '<')) {
        $errors[] = sprintf(
            __('Reverse2PDF requires PHP %s or higher. You are running PHP %s.', 'reverse2pdf'),
            REVERSE2PDF_MIN_PHP_VERSION,
            PHP_VERSION
        );
    }
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, REVERSE2PDF_MIN_WP_VERSION, '<')) {
        $errors[] = sprintf(
            __('Reverse2PDF requires WordPress %s or higher. You are running WordPress %s.', 'reverse2pdf'),
            REVERSE2PDF_MIN_WP_VERSION,
            $wp_version
        );
    }
    
    // Check required PHP extensions
    $required_extensions = array('gd', 'curl', 'json', 'mbstring', 'zip');
    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            $errors[] = sprintf(
                __('Reverse2PDF requires the %s PHP extension to be installed and enabled.', 'reverse2pdf'),
                $extension
            );
        }
    }
    
    // Check memory limit
    $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
    if ($memory_limit > 0 && $memory_limit < 256 * 1024 * 1024) {
        $errors[] = __('Reverse2PDF requires at least 256MB of PHP memory. Consider increasing your memory limit.', 'reverse2pdf');
    }
    
    // Check file permissions
    $upload_dir = wp_upload_dir();
    if (!wp_is_writable($upload_dir['basedir'])) {
        $errors[] = __('WordPress uploads directory is not writable. Please check file permissions.', 'reverse2pdf');
    }
    
    return $errors;
}

/**
 * Display activation errors
 */
function reverse2pdf_activation_failed($errors) {
    $html = '<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #dc3545; border-radius: 8px; background: #f8d7da;">';
    $html .= '<h2 style="color: #721c24; margin-top: 0;">⚠️ Reverse2PDF Activation Failed</h2>';
    $html .= '<p style="color: #721c24; font-size: 16px; margin-bottom: 20px;">The following requirements are not met:</p>';
    $html .= '<ul style="color: #721c24; padding-left: 20px;">';
    
    foreach ($errors as $error) {
        $html .= '<li style="margin-bottom: 8px; line-height: 1.4;">' . esc_html($error) . '</li>';
    }
    
    $html .= '</ul>';
    $html .= '<div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.8); border-radius: 4px;">';
    $html .= '<h4 style="color: #721c24; margin-top: 0;">What to do next:</h4>';
    $html .= '<ol style="color: #721c24; padding-left: 20px;">';
    $html .= '<li>Contact your web hosting provider to resolve these issues</li>';
    $html .= '<li>Or contact our support team for assistance</li>';
    $html .= '<li>Once resolved, try activating the plugin again</li>';
    $html .= '</ol>';
    $html .= '<p style="margin-bottom: 0;"><strong>Support:</strong> <a href="https://reversecube.net/support" target="_blank" style="color: #0073aa;">https://reversecube.net/support</a></p>';
    $html .= '</div>';
    $html .= '</div>';
    
    wp_die($html, 'Plugin Activation Error', array('back_link' => true));
}

/**
 * Enhanced Activation Hook
 */
register_activation_hook(__FILE__, 'reverse2pdf_activate');
function reverse2pdf_activate() {
    // Check requirements
    $errors = reverse2pdf_check_requirements();
    if (!empty($errors)) {
        reverse2pdf_activation_failed($errors);
    }
    
    // Set activation flag
    add_option('reverse2pdf_activation_time', current_time('timestamp'));
    
    try {
        // Create database tables
        reverse2pdf_create_tables();
        
        // Create necessary folders
        reverse2pdf_create_folders();
        
        // Install sample templates
        reverse2pdf_install_sample_templates();
        
        // Set default options
        reverse2pdf_set_default_options();
        
        // Schedule cleanup events
        reverse2pdf_schedule_events();
        
        // Set permissions
        reverse2pdf_set_capabilities();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log successful activation
        error_log('Reverse2PDF: Plugin activated successfully on ' . site_url());
        
    } catch (Exception $e) {
        // Log activation error
        error_log('Reverse2PDF Activation Error: ' . $e->getMessage());
        
        wp_die(
            '<h1>Activation Error</h1><p>An error occurred during plugin activation: ' . esc_html($e->getMessage()) . '</p><p>Please check your server error log for more details.</p>',
            'Plugin Activation Error',
            array('back_link' => true)
        );
    }
}

/**
 * Set default plugin options
 */
function reverse2pdf_set_default_options() {
    $defaults = array(
        'pdf_engine' => 'dompdf',
        'paper_size' => 'A4',
        'paper_orientation' => 'portrait',
        'default_font' => 'Arial',
        'base_font_size' => '12px',
        'line_height' => '1.4',
        'margin_top' => '20mm',
        'margin_right' => '15mm',
        'margin_bottom' => '20mm',
        'margin_left' => '15mm',
        'enable_cache' => true,
        'cache_lifetime' => 3600,
        'enable_debug' => false,
        'upload_folder' => 'reverse2pdf',
        'auto_cleanup' => true,
        'cleanup_days' => 7,
        'max_execution_time' => 300,
        'memory_limit' => '512M',
        'enable_logging' => true,
        'log_retention_days' => 30,
        'font_subsetting' => true,
        'image_quality' => 90,
        'remove_data_on_uninstall' => false
    );
    
    add_option('reverse2pdf_settings', $defaults);
    add_option('reverse2pdf_version', REVERSE2PDF_VERSION);
}

/**
 * Schedule cleanup events
 */
function reverse2pdf_schedule_events() {
    // Daily cleanup
    if (!wp_next_scheduled('reverse2pdf_cleanup')) {
        wp_schedule_event(time(), 'daily', 'reverse2pdf_cleanup');
    }
    
    // Weekly log cleanup
    if (!wp_next_scheduled('reverse2pdf_cleanup_logs')) {
        wp_schedule_event(time(), 'weekly', 'reverse2pdf_cleanup_logs');
    }
    
    // Weekly template backup
    if (!wp_next_scheduled('reverse2pdf_backup_templates')) {
        wp_schedule_event(time(), 'weekly', 'reverse2pdf_backup_templates');
    }
}

/**
 * Set user capabilities
 */
function reverse2pdf_set_capabilities() {
    $capabilities = array(
        'reverse2pdf_create_templates',
        'reverse2pdf_edit_templates',
        'reverse2pdf_delete_templates',
        'reverse2pdf_manage_integrations',
        'reverse2pdf_view_logs',
        'reverse2pdf_manage_settings'
    );
    
    // Add capabilities to administrator role
    $admin_role = get_role('administrator');
    if ($admin_role) {
        foreach ($capabilities as $capability) {
            $admin_role->add_cap($capability);
        }
    }
    
    // Add basic capabilities to editor role
    $editor_role = get_role('editor');
    if ($editor_role) {
        $editor_role->add_cap('reverse2pdf_create_templates');
        $editor_role->add_cap('reverse2pdf_edit_templates');
    }
}

/**
 * Enhanced Deactivation Hook
 */
register_deactivation_hook(__FILE__, 'reverse2pdf_deactivate');
function reverse2pdf_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('reverse2pdf_cleanup');
    wp_clear_scheduled_hook('reverse2pdf_cleanup_logs');
    wp_clear_scheduled_hook('reverse2pdf_backup_templates');
    
    // Clear any transients
    delete_transient('reverse2pdf_system_status');
    delete_transient('reverse2pdf_template_cache');
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Log deactivation
    error_log('Reverse2PDF: Plugin deactivated on ' . site_url());
}

/**
 * Enhanced Database Tables Creation
 */
function reverse2pdf_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Templates table
    $table_templates = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;
    $sql1 = "CREATE TABLE IF NOT EXISTS $table_templates (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        template_data longtext,
        settings longtext,
        form_type varchar(50) DEFAULT NULL,
        form_id varchar(50) DEFAULT NULL,
        active tinyint(1) DEFAULT 1,
        created_by int(11) DEFAULT 0,
        created_date datetime DEFAULT CURRENT_TIMESTAMP,
        modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY name (name),
        KEY form_type (form_type),
        KEY active (active),
        KEY created_by (created_by),
        KEY created_date (created_date)
    ) $charset_collate;";
    
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
        execution_time float DEFAULT 0,
        memory_usage int DEFAULT 0,
        created_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY template_id (template_id),
        KEY user_id (user_id),
        KEY action (action),
        KEY status (status),
        KEY created_date (created_date)
    ) $charset_collate;";
    
    // Integrations table
    $table_integrations = $wpdb->prefix . REVERSE2PDF_TABLE_INTEGRATIONS;
    $sql3 = "CREATE TABLE IF NOT EXISTS $table_integrations (
        id int(11) NOT NULL AUTO_INCREMENT,
        form_type varchar(50) NOT NULL,
        form_id varchar(50) NOT NULL,
        template_id int(11) NOT NULL,
        trigger_action varchar(100) DEFAULT 'form_submit',
        conditions longtext,
        settings longtext,
        active tinyint(1) DEFAULT 1,
        created_by int(11) DEFAULT 0,
        created_date datetime DEFAULT CURRENT_TIMESTAMP,
        modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY form_type (form_type),
        KEY form_id (form_id),
        KEY template_id (template_id),
        KEY active (active),
        KEY created_by (created_by),
        UNIQUE KEY unique_integration (form_type, form_id, template_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $results = array();
    $results['templates'] = dbDelta($sql1);
    $results['logs'] = dbDelta($sql2);
    $results['integrations'] = dbDelta($sql3);
    
    // Check for database errors
    if ($wpdb->last_error) {
        throw new Exception('Database error: ' . $wpdb->last_error);
    }
    
    return $results;
}

/**
 * Enhanced Folder Creation
 */
function reverse2pdf_create_folders() {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'] . '/reverse2pdf';
    
    $folders = array(
        $base_dir,
        $base_dir . '/templates',
        $base_dir . '/pdfs',
        $base_dir . '/cache',
        $base_dir . '/logs',
        $base_dir . '/fonts',
        $base_dir . '/images',
        $base_dir . '/backups',
        $base_dir . '/temp'
    );
    
    foreach ($folders as $folder) {
        if (!file_exists($folder)) {
            if (!wp_mkdir_p($folder)) {
                throw new Exception("Failed to create directory: $folder");
            }
        }
        
        // Verify folder is writable
        if (!wp_is_writable($folder)) {
            throw new Exception("Directory is not writable: $folder");
        }
    }
    
    // Enhanced security files
    $htaccess_content = "# Reverse2PDF Security Rules\n";
    $htaccess_content .= "Options -Indexes\n";
    $htaccess_content .= "Options -ExecCGI\n";
    $htaccess_content .= "<Files *.php>\n";
    $htaccess_content .= "    Order Allow,Deny\n";
    $htaccess_content .= "    Deny from all\n";
    $htaccess_content .= "</Files>\n";
    $htaccess_content .= "<Files *.log>\n";
    $htaccess_content .= "    Order Allow,Deny\n";
    $htaccess_content .= "    Deny from all\n";
    $htaccess_content .= "</Files>\n";
    $htaccess_content .= "<Files *.json>\n";
    $htaccess_content .= "    Order Allow,Deny\n";
    $htaccess_content .= "    Deny from all\n";
    $htaccess_content .= "</Files>\n";
    
    if (!file_put_contents($base_dir . '/.htaccess', $htaccess_content)) {
        throw new Exception("Failed to create .htaccess file");
    }
    
    // Create index.php files for additional security
    $index_content = "<?php\n// Silence is golden\n// This file prevents directory browsing\n";
    foreach ($folders as $folder) {
        file_put_contents($folder . '/index.php', $index_content);
    }
}

/**
 * Enhanced Sample Templates Installation
 */
function reverse2pdf_install_sample_templates() {
    global $wpdb;
    
    $table = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;
    
    // Check if samples already exist
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE name LIKE %s",
        '%Sample%'
    ));
    
    if ($existing > 0) {
        return;
    }
    
    $samples = array(
        array(
            'name' => 'Sample Invoice Template',
            'description' => 'Professional invoice template with company branding and itemized billing',
            'template_data' => wp_json_encode(reverse2pdf_get_sample_invoice()),
            'settings' => wp_json_encode(array(
                'paper_size' => 'A4',
                'orientation' => 'portrait',
                'margins' => array('top' => '20mm', 'right' => '15mm', 'bottom' => '20mm', 'left' => '15mm')
            )),
            'active' => 1,
            'created_by' => get_current_user_id(),
            'created_date' => current_time('mysql'),
            'modified_date' => current_time('mysql')
        ),
        array(
            'name' => 'Sample Contact Form PDF',
            'description' => 'Contact form submission PDF template with form field mapping',
            'template_data' => wp_json_encode(reverse2pdf_get_sample_contact_form()),
            'settings' => wp_json_encode(array(
                'paper_size' => 'A4',
                'orientation' => 'portrait',
                'margins' => array('top' => '20mm', 'right' => '15mm', 'bottom' => '20mm', 'left' => '15mm')
            )),
            'active' => 1,
            'created_by' => get_current_user_id(),
            'created_date' => current_time('mysql'),
            'modified_date' => current_time('mysql')
        ),
        array(
            'name' => 'Sample Certificate Template',
            'description' => 'Achievement certificate template with elegant design and dynamic content',
            'template_data' => wp_json_encode(reverse2pdf_get_sample_certificate()),
            'settings' => wp_json_encode(array(
                'paper_size' => 'A4',
                'orientation' => 'landscape',
                'margins' => array('top' => '15mm', 'right' => '15mm', 'bottom' => '15mm', 'left' => '15mm')
            )),
            'active' => 1,
            'created_by' => get_current_user_id(),
            'created_date' => current_time('mysql'),
            'modified_date' => current_time('mysql')
        )
    );
    
    foreach ($samples as $sample) {
        $result = $wpdb->insert($table, $sample);
        if ($result === false) {
            error_log('Reverse2PDF: Failed to install sample template: ' . $sample['name']);
        }
    }
    
    // Mark samples as installed
    add_option('reverse2pdf_sample_templates_installed', true);
}

/**
 * Enhanced Plugin Loading
 */
add_action('plugins_loaded', 'reverse2pdf_load_plugin');
function reverse2pdf_load_plugin() {
    // Load text domain for internationalization
    load_plugin_textdomain('reverse2pdf', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Check if we need to run upgrade
    $current_version = get_option('reverse2pdf_version', '0.0.0');
    if (version_compare($current_version, REVERSE2PDF_VERSION, '<')) {
        reverse2pdf_run_upgrade($current_version, REVERSE2PDF_VERSION);
    }
    
    // Load vendor autoloader
    if (file_exists(REVERSE2PDF_PLUGIN_DIR . 'vendor/autoload.php')) {
        require_once REVERSE2PDF_PLUGIN_DIR . 'vendor/autoload.php';
    }
    
    // Load core classes
    $core_files = array(
        'includes/class-reverse2pdf-core.php',
        'includes/class-reverse2pdf-upgrade.php'
    );
    
    foreach ($core_files as $file) {
        $file_path = REVERSE2PDF_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            error_log("Reverse2PDF: Missing core file: $file");
        }
    }
    
    // Initialize the plugin
    if (class_exists('Reverse2PDF_Core')) {
        $GLOBALS['reverse2pdf'] = new Reverse2PDF_Core();
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Reverse2PDF Error:</strong> Core class not found. Please reinstall the plugin.</p></div>';
        });
        return;
    }
    
    // Initialize upgrade handler
    if (class_exists('Reverse2PDF_Upgrade')) {
        new Reverse2PDF_Upgrade();
    }
}

/**
 * Run plugin upgrade
 */
function reverse2pdf_run_upgrade($from_version, $to_version) {
    // Log upgrade start
    error_log("Reverse2PDF: Upgrading from $from_version to $to_version");
    
    // Update version
    update_option('reverse2pdf_version', $to_version);
    
    // Clear caches
    delete_transient('reverse2pdf_system_status');
    wp_cache_flush();
    
    // Log upgrade completion
    error_log("Reverse2PDF: Upgrade completed successfully");
}

/**
 * Enhanced Cleanup Function
 */
add_action('reverse2pdf_cleanup', 'reverse2pdf_cleanup_files');
function reverse2pdf_cleanup_files() {
    $start_time = microtime(true);
    $settings = get_option('reverse2pdf_settings', array());
    
    if (!isset($settings['auto_cleanup']) || !$settings['auto_cleanup']) {
        return;
    }
    
    $days = isset($settings['cleanup_days']) ? intval($settings['cleanup_days']) : 7;
    $cutoff = time() - ($days * 24 * 60 * 60);
    
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'] . '/reverse2pdf';
    
    $cleaned_files = 0;
    $cleaned_size = 0;
    
    // Clean PDFs
    $pdf_dir = $base_dir . '/pdfs';
    if (is_dir($pdf_dir)) {
        $files = glob($pdf_dir . '/*.{pdf,html}', GLOB_BRACE);
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                $size = filesize($file);
                if (unlink($file)) {
                    $cleaned_files++;
                    $cleaned_size += $size;
                }
            }
        }
    }
    
    // Clean cache
    $cache_dir = $base_dir . '/cache';
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                $size = filesize($file);
                if (unlink($file)) {
                    $cleaned_files++;
                    $cleaned_size += $size;
                }
            }
        }
    }
    
    // Clean temp files
    $temp_dir = $base_dir . '/temp';
    if (is_dir($temp_dir)) {
        $files = glob($temp_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < ($cutoff - 3600)) { // Clean temp files older than 1 hour
                $size = filesize($file);
                if (unlink($file)) {
                    $cleaned_files++;
                    $cleaned_size += $size;
                }
            }
        }
    }
    
    $execution_time = microtime(true) - $start_time;
    
    // Log cleanup results
    if ($cleaned_files > 0) {
        error_log(sprintf(
            'Reverse2PDF Cleanup: Removed %d files (%.2f MB) in %.2f seconds',
            $cleaned_files,
            $cleaned_size / 1024 / 1024,
            $execution_time
        ));
    }
}

/**
 * Log cleanup function
 */
add_action('reverse2pdf_cleanup_logs', 'reverse2pdf_cleanup_old_logs');
function reverse2pdf_cleanup_old_logs() {
    global $wpdb;
    
    $settings = get_option('reverse2pdf_settings', array());
    $retention_days = isset($settings['log_retention_days']) ? intval($settings['log_retention_days']) : 30;
    
    $cutoff_date = date('Y-m-d H:i:s', time() - ($retention_days * 24 * 60 * 60));
    $table = $wpdb->prefix . REVERSE2PDF_TABLE_LOGS;
    
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM $table WHERE created_date < %s",
        $cutoff_date
    ));
    
    if ($deleted > 0) {
        error_log("Reverse2PDF: Cleaned up $deleted old log entries");
    }
}

/**
 * Template backup function
 */
add_action('reverse2pdf_backup_templates', 'reverse2pdf_backup_templates');
function reverse2pdf_backup_templates() {
    global $wpdb;
    
    $table = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;
    $templates = $wpdb->get_results("SELECT * FROM $table");
    
    if (empty($templates)) {
        return;
    }
    
    $upload_dir = wp_upload_dir();
    $backup_dir = $upload_dir['basedir'] . '/reverse2pdf/backups';
    
    $backup_data = array(
        'version' => REVERSE2PDF_VERSION,
        'timestamp' => current_time('mysql'),
        'templates' => $templates
    );
    
    $backup_file = $backup_dir . '/templates_backup_' . date('Y-m-d_H-i-s') . '.json';
    
    if (file_put_contents($backup_file, wp_json_encode($backup_data, JSON_PRETTY_PRINT))) {
        error_log("Reverse2PDF: Templates backed up to $backup_file");
        
        // Keep only last 5 backups
        $backup_files = glob($backup_dir . '/templates_backup_*.json');
        if (count($backup_files) > 5) {
            usort($backup_files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $files_to_delete = array_slice($backup_files, 0, -5);
            foreach ($files_to_delete as $file) {
                unlink($file);
            }
        }
    }
}

// Sample template data functions (keep existing functions but add error handling)
function reverse2pdf_get_sample_invoice() {
    try {
        return array(
            'pages' => array(
                array(
                    'width' => 595,
                    'height' => 842,
                    'elements' => array(
                        array(
                            'id' => 'invoice_title',
                            'type' => 'text',
                            'x' => 50,
                            'y' => 50,
                            'width' => 495,
                            'height' => 40,
                            'content' => 'INVOICE',
                            'fontSize' => 24,
                            'fontWeight' => 'bold',
                            'textAlign' => 'center',
                            'color' => '#2c3e50'
                        ),
                        array(
                            'id' => 'company_name',
                            'type' => 'text',
                            'x' => 50,
                            'y' => 100,
                            'width' => 200,
                            'height' => 25,
                            'content' => '{company_name}',
                            'fontSize' => 16,
                            'fontWeight' => 'bold',
                            'color' => '#2c3e50'
                        ),
                        array(
                            'id' => 'invoice_number',
                            'type' => 'text',
                            'x' => 350,
                            'y' => 100,
                            'width' => 195,
                            'height' => 25,
                            'content' => 'Invoice #: {invoice_number}',
                            'fontSize' => 12,
                            'textAlign' => 'right'
                        ),
                        array(
                            'id' => 'invoice_date',
                            'type' => 'text',
                            'x' => 350,
                            'y' => 125,
                            'width' => 195,
                            'height' => 25,
                            'content' => 'Date: {invoice_date}',
                            'fontSize' => 12,
                            'textAlign' => 'right'
                        ),
                        array(
                            'id' => 'customer_info',
                            'type' => 'text',
                            'x' => 50,
                            'y' => 200,
                            'width' => 250,
                            'height' => 100,
                            'content' => "Bill To:\n{customer_name}\n{customer_address}\n{customer_city}, {customer_state} {customer_zip}",
                            'fontSize' => 11,
                            'lineHeight' => 1.4
                        ),
                        array(
                            'id' => 'items_header',
                            'type' => 'text',
                            'x' => 50,
                            'y' => 350,
                            'width' => 495,
                            'height' => 25,
                            'content' => 'Description                    Qty    Price    Total',
                            'fontSize' => 12,
                            'fontWeight' => 'bold',
                            'backgroundColor' => '#ecf0f1',
                            'padding' => '8px'
                        ),
                        array(
                            'id' => 'total_amount',
                            'type' => 'text',
                            'x' => 350,
                            'y' => 500,
                            'width' => 195,
                            'height' => 30,
                            'content' => 'Total: {total_amount}',
                            'fontSize' => 16,
                            'fontWeight' => 'bold',
                            'textAlign' => 'right',
                            'color' => '#e74c3c'
                        )
                    )
                )
            )
        );
    } catch (Exception $e) {
        error_log('Reverse2PDF: Error creating sample invoice: ' . $e->getMessage());
        return array('pages' => array());
    }
}

function reverse2pdf_get_sample_contact_form() {
    try {
        return array(
            'pages' => array(
                array(
                    'width' => 595,
                    'height' => 842,
                    'elements' => array(
                        array(
                            'id' => 'form_title',
                            'type' => 'text',
                            'x' => 50,
                            'y' => 50,
                            'width' => 495,
                            'height' => 40,
                            'content' => 'CONTACT FORM SUBMISSION',
                            'fontSize' => 20,
                            'fontWeight' => 'bold',
                            'textAlign' => 'center',
                            'color' => '#2c3e50'
                        ),
                        array(
                            'id' => 'submission_date',
                            'type' => 'text',
                            'x' => 50,
                            'y' => 100,
                            'width' => 495,
                            'height' => 25,
                            'content' => 'Submitted: {submission_date}',
                            'fontSize' => 12,
                            'textAlign' => 'center',
                            'color' => '#7f8c8d'
                        ),
                        array(
                            'id' => 'name_field',
                            'type' => 'text',
                            'x' => 50,
                            'y' => 150,
                            'width' => 495,
                            'height' => 25,
                            'content' => 'Name: {your-name}',
                            'fontSize' => 12,
                            'fontWeight' => '600'
                        ),
                        array(
                            'id' => 'email_field',
                            'type' => 'text',
                            'x' => 50,
                            'y' => 180,
                            'width' => 495,
                            'height' => 25,
                            'content' => 'Email: {your-email}',
                            'fontSize' => 12,
                            'fontWeight' => '600'
                        ),
                        array(
                            'id' => 'subject_field',
                            'type' => 'text',
                            'x' => 50,
                            'y' => 210,
                            'width' => 495,
                            'height' => 25,
                            'content' => 'Subject: {your-subject}',
                            'fontSize' => 12,
                            'fontWeight' => '600'
                        ),
                        array(
                            'id' => 'message_field',
                            'type' => 'text',
                            'x' => 50,
                            'y' => 250,
                            'width' => 495,
                            'height' => 200,
                            'content' => 'Message:\n{your-message}',
                            'fontSize' => 12,
                            'lineHeight' => 1.4,
                            'backgroundColor' => '#f8f9fa',
                            'padding' => '10px'
                        )
                    )
                )
            )
        );
    } catch (Exception $e) {
        error_log('Reverse2PDF: Error creating sample contact form: ' . $e->getMessage());
        return array('pages' => array());
    }
}

function reverse2pdf_get_sample_certificate() {
    try {
        return array(
            'pages' => array(
                array(
                    'width' => 842,
                    'height' => 595,
                    'elements' => array(
                        array(
                            'id' => 'cert_border',
                            'type' => 'rectangle',
                            'x' => 20,
                            'y' => 20,
                            'width' => 802,
                            'height' => 555,
                            'fillColor' => 'transparent',
                            'borderColor' => '#8B4513',
                            'borderWidth' => 3
                        ),
                        array(
                            'id' => 'cert_title',
                            'type' => 'text',
                            'x' => 50,
                            'y' => 100,
                            'width' => 742,
                            'height' => 50,
                            'content' => 'CERTIFICATE OF ACHIEVEMENT',
                            'fontSize' => 28,
                            'fontWeight' => 'bold',
                            'textAlign' => 'center',
                            'color' => '#8B4513'
                        ),
                        array(
                            'id' => 'recipient_name',
                            'type' => 'text',
                            'x' => 50,
                            'y' => 250,
                            'width' => 742,
                            'height' => 50,
                            'content' => '{recipient_name}',
                            'fontSize' => 32,
                            'fontWeight' => 'bold',
                            'textAlign' => 'center',
                            'color' => '#8B4513',
                            'fontStyle' => 'italic'
                        ),
                        array(
                            'id' => 'achievement_text',
                            'type' => 'text',
                            'x' => 50,
                            'y' => 350,
                            'width' => 742,
                            'height' => 50,
                            'content' => 'has successfully completed {course_name}',
                            'fontSize' => 16,
                            'textAlign' => 'center'
                        ),
                        array(
                            'id' => 'cert_date',
                            'type' => 'text',
                            'x' => 100,
                            'y' => 450,
                            'width' => 200,
                            'height' => 25,
                            'content' => 'Date: {completion_date}',
                            'fontSize' => 12,
                            'textAlign' => 'center'
                        ),
                        array(
                            'id' => 'signature_line',
                            'type' => 'line',
                            'x' => 550,
                            'y' => 470,
                            'width' => 150,
                            'height' => 1,
                            'color' => '#000000',
                            'thickness' => 1
                        ),
                        array(
                            'id' => 'signature_text',
                            'type' => 'text',
                            'x' => 550,
                            'y' => 480,
                            'width' => 150,
                            'height' => 15,
                            'content' => 'Authorized Signature',
                            'fontSize' => 10,
                            'textAlign' => 'center'
                        )
                    )
                )
            )
        );
    } catch (Exception $e) {
        error_log('Reverse2PDF: Error creating sample certificate: ' . $e->getMessage());
        return array('pages' => array());
    }
}

/**
 * Enhanced AJAX endpoint for PDF generation
 */
add_action('wp_ajax_reverse2pdf_generate', 'reverse2pdf_ajax_generate');
add_action('wp_ajax_nopriv_reverse2pdf_generate', 'reverse2pdf_ajax_generate');

function reverse2pdf_ajax_generate() {
    // Verify nonce for security
    if (!check_ajax_referer('reverse2pdf_nonce', 'nonce', false)) {
        wp_send_json_error(array(
            'message' => __('Security check failed', 'reverse2pdf'),
            'code' => 'invalid_nonce'
        ));
    }
    
    // Check permissions
    if (!current_user_can('read')) {
        wp_send_json_error(array(
            'message' => __('Insufficient permissions', 'reverse2pdf'),
            'code' => 'insufficient_permissions'
        ));
    }
    
    $start_time = microtime(true);
    $template_id = intval($_POST['template_id'] ?? 0);
    $dataset_id = intval($_POST['dataset_id'] ?? 0);
    
    if (!$template_id) {
        wp_send_json_error(array(
            'message' => __('Template ID required', 'reverse2pdf'),
            'code' => 'missing_template_id'
        ));
    }
    
    try {
        // Load generator class
        require_once REVERSE2PDF_PLUGIN_DIR . 'includes/class-reverse2pdf-enhanced-generator.php';
        
        if (!class_exists('Reverse2PDF_Enhanced_Generator')) {
            throw new Exception('Generator class not found');
        }
        
        $generator = new Reverse2PDF_Enhanced_Generator();
        $options = array();
        
        // Add form data if provided
        if (isset($_POST['form_data']) && is_array($_POST['form_data'])) {
            $options['form_data'] = array_map('sanitize_text_field', $_POST['form_data']);
        }
        
        // Generate PDF
        $result = $generator->generate_pdf($template_id, $dataset_id, $options);
        
        if ($result) {
            $execution_time = microtime(true) - $start_time;
            
            wp_send_json_success(array(
                'pdf_url' => $result,
                'template_id' => $template_id,
                'execution_time' => round($execution_time, 3),
                'message' => __('PDF generated successfully', 'reverse2pdf')
            ));
        } else {
            throw new Exception('PDF generation returned false');
        }
        
    } catch (Exception $e) {
        $execution_time = microtime(true) - $start_time;
        
        // Log the error
        error_log('Reverse2PDF Generation Error: ' . $e->getMessage() . ' (Template ID: ' . $template_id . ')');
        
        wp_send_json_error(array(
            'message' => __('PDF generation failed', 'reverse2pdf') . ': ' . $e->getMessage(),
            'code' => 'generation_failed',
            'template_id' => $template_id,
            'execution_time' => round($execution_time, 3)
        ));
    }
}

/**
 * System status check for admin
 */
add_action('wp_ajax_reverse2pdf_system_status', 'reverse2pdf_ajax_system_status');
function reverse2pdf_ajax_system_status() {
    if (!check_ajax_referer('reverse2pdf_nonce', 'nonce', false) || !current_user_can('manage_options')) {
        wp_send_json_error('Access denied');
    }
    
    $status = array(
        'php_version' => PHP_VERSION,
        'wp_version' => get_bloginfo('version'),
        'plugin_version' => REVERSE2PDF_VERSION,
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'extensions' => array(),
        'writable_dirs' => array(),
        'database_status' => 'ok'
    );
    
    // Check extensions
    $required_extensions = array('gd', 'curl', 'json', 'mbstring', 'zip');
    foreach ($required_extensions as $ext) {
        $status['extensions'][$ext] = extension_loaded($ext);
    }
    
    // Check writable directories
    $upload_dir = wp_upload_dir();
    $dirs_to_check = array(
        'uploads' => $upload_dir['basedir'],
        'reverse2pdf' => $upload_dir['basedir'] . '/reverse2pdf',
        'pdfs' => $upload_dir['basedir'] . '/reverse2pdf/pdfs',
        'cache' => $upload_dir['basedir'] . '/reverse2pdf/cache'
    );
    
    foreach ($dirs_to_check as $name => $dir) {
        $status['writable_dirs'][$name] = is_dir($dir) && wp_is_writable($dir);
    }
    
    // Check database tables
    global $wpdb;
    $tables = array(
        REVERSE2PDF_TABLE_TEMPLATES,
        REVERSE2PDF_TABLE_LOGS,
        REVERSE2PDF_TABLE_INTEGRATIONS
    );
    
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$exists) {
            $status['database_status'] = 'missing_tables';
            break;
        }
    }
    
    wp_send_json_success($status);
}

// Add admin notice for important updates
add_action('admin_notices', 'reverse2pdf_admin_notices');
function reverse2pdf_admin_notices() {
    $screen = get_current_screen();
    if (strpos($screen->id, 'reverse2pdf') === false) {
        return;
    }
    
    // Check if requirements are still met
    $errors = reverse2pdf_check_requirements();
    if (!empty($errors)) {
        echo '<div class="notice notice-error"><p><strong>Reverse2PDF:</strong> ' . implode(', ', $errors) . '</p></div>';
    }
}

// Add plugin meta links
add_filter('plugin_row_meta', 'reverse2pdf_plugin_row_meta', 10, 2);
function reverse2pdf_plugin_row_meta($links, $file) {
    if (strpos($file, 'reverse2pdf.php') !== false) {
        $new_links = array(
            'docs' => '<a href="https://reversecube.net/reverse2pdf/docs" target="_blank">' . __('Documentation', 'reverse2pdf') . '</a>',
            'support' => '<a href="https://reversecube.net/support" target="_blank">' . __('Support', 'reverse2pdf') . '</a>',
            'premium' => '<a href="https://reversecube.net/reverse2pdf-pro" target="_blank" style="color: #39b54a; font-weight: bold;">' . __('Get Pro', 'reverse2pdf') . '</a>'
        );
        $links = array_merge($links, $new_links);
    }
    return $links;
}

// Initialize error logging
if (!function_exists('reverse2pdf_log_error')) {
    function reverse2pdf_log_error($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Reverse2PDF: ' . $message . (!empty($context) ? ' | Context: ' . wp_json_encode($context) : ''));
        }
    }
}
?>
