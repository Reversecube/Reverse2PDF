<?php
/**
 * Plugin Name: Reverse2PDF Pro
 * Plugin URI: https://github.com/Reversecube/Reverse2PDF
 * Description: Complete PDF generation solution - Visual builder, form integrations, conditional logic, math expressions, loops, and 25+ shortcodes
 * Version: 2.0.0
 * Author: Reversecube
 * Author URI: https://reversecube.com
 * License: Commercial
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

if (!defined('ABSPATH')) {
    exit('Direct access not allowed.');
}

// Plugin security check
if (!function_exists('add_action')) {
    echo 'WordPress not detected. Plugin cannot run independently.';
    exit;
}

// Define plugin constants
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
 * Requirements check
 */
function reverse2pdf_check_requirements() {
    $errors = array();
    
    if (version_compare(PHP_VERSION, REVERSE2PDF_MIN_PHP_VERSION, '<')) {
        $errors[] = sprintf(
            'Reverse2PDF requires PHP %s or higher. Current: %s',
            REVERSE2PDF_MIN_PHP_VERSION,
            PHP_VERSION
        );
    }
    
    global $wp_version;
    if (version_compare($wp_version, REVERSE2PDF_MIN_WP_VERSION, '<')) {
        $errors[] = sprintf(
            'Reverse2PDF requires WordPress %s or higher. Current: %s',
            REVERSE2PDF_MIN_WP_VERSION,
            $wp_version
        );
    }
    
    // Check essential extensions only
    $required_extensions = array('json');
    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            $errors[] = sprintf('PHP %s extension is required', $extension);
        }
    }
    
    return $errors;
}

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'reverse2pdf_activate');
function reverse2pdf_activate() {
    $errors = reverse2pdf_check_requirements();
    if (!empty($errors)) {
        wp_die('<h1>Activation Failed</h1><ul><li>' . implode('</li><li>', array_map('esc_html', $errors)) . '</li></ul>');
    }
    
    // Create database tables
    reverse2pdf_create_tables();
    
    // Create folders
    reverse2pdf_create_folders();
    
    // Install sample templates
    reverse2pdf_install_sample_templates();
    
    // Set default options
    reverse2pdf_set_default_options();
    
    // Schedule cleanup events
    reverse2pdf_schedule_events();
    
    // Set capabilities
    reverse2pdf_set_capabilities();
    
    flush_rewrite_rules();
}

/**
 * Create database tables
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
        KEY active (active)
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
        KEY status (status)
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
        created_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY form_type (form_type),
        KEY form_id (form_id),
        KEY template_id (template_id),
        KEY active (active),
        UNIQUE KEY unique_integration (form_type, form_id, template_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);
}

/**
 * Create folders
 */
function reverse2pdf_create_folders() {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'] . '/reverse2pdf';
    
    $folders = array(
        $base_dir,
        $base_dir . '/pdfs',
        $base_dir . '/cache',
        $base_dir . '/logs',
        $base_dir . '/fonts',
        $base_dir . '/images',
        $base_dir . '/templates'
    );
    
    foreach ($folders as $folder) {
        if (!file_exists($folder)) {
            wp_mkdir_p($folder);
        }
    }
    
    // Security files
    $htaccess = "Options -Indexes\nDeny from all\n<Files *.pdf>\nAllow from all\n</Files>";
    file_put_contents($base_dir . '/.htaccess', $htaccess);
    file_put_contents($base_dir . '/index.php', '<?php // Silence is golden');
}

/**
 * Install sample templates
 */
function reverse2pdf_install_sample_templates() {
    global $wpdb;
    
    $table = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;
    
    // Check if already installed
    $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE name LIKE '%Sample%'");
    if ($existing > 0) {
        return;
    }
    
    // Install sample templates
    $samples = array(
        array(
            'name' => 'Sample Invoice Template',
            'description' => 'Professional invoice template with company branding',
            'template_data' => wp_json_encode(reverse2pdf_get_sample_invoice()),
            'settings' => wp_json_encode(array('paper_size' => 'A4', 'orientation' => 'portrait')),
            'active' => 1,
            'created_by' => get_current_user_id()
        ),
        array(
            'name' => 'Sample Contact Form PDF',
            'description' => 'Contact form submission PDF template',
            'template_data' => wp_json_encode(reverse2pdf_get_sample_contact_form()),
            'settings' => wp_json_encode(array('paper_size' => 'A4', 'orientation' => 'portrait')),
            'active' => 1,
            'created_by' => get_current_user_id()
        ),
        array(
            'name' => 'Sample Certificate Template',
            'description' => 'Achievement certificate template',
            'template_data' => wp_json_encode(reverse2pdf_get_sample_certificate()),
            'settings' => wp_json_encode(array('paper_size' => 'A4', 'orientation' => 'landscape')),
            'active' => 1,
            'created_by' => get_current_user_id()
        )
    );
    
    foreach ($samples as $sample) {
        $wpdb->insert($table, $sample);
    }
}

/**
 * Set default options
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
        'auto_cleanup' => true,
        'cleanup_days' => 7,
        'max_execution_time' => 300,
        'memory_limit' => '512M',
        'enable_logging' => true,
        'log_retention_days' => 30
    );
    
    add_option('reverse2pdf_settings', $defaults);
    add_option('reverse2pdf_version', REVERSE2PDF_VERSION);
}

/**
 * Schedule events
 */
function reverse2pdf_schedule_events() {
    if (!wp_next_scheduled('reverse2pdf_cleanup')) {
        wp_schedule_event(time(), 'daily', 'reverse2pdf_cleanup');
    }
}

/**
 * Set capabilities
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
    
    $admin_role = get_role('administrator');
    if ($admin_role) {
        foreach ($capabilities as $capability) {
            $admin_role->add_cap($capability);
        }
    }
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'reverse2pdf_deactivate');
function reverse2pdf_deactivate() {
    wp_clear_scheduled_hook('reverse2pdf_cleanup');
    flush_rewrite_rules();
}

/**
 * Load plugin
 */
add_action('plugins_loaded', 'reverse2pdf_load_plugin');
function reverse2pdf_load_plugin() {
    // Load text domain
    load_plugin_textdomain('reverse2pdf', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Load vendor autoloader
    $autoload_file = REVERSE2PDF_PLUGIN_DIR . 'vendor/autoload.php';
    if (file_exists($autoload_file)) {
        require_once $autoload_file;
    }
    
    // Load core class
    $core_file = REVERSE2PDF_PLUGIN_DIR . 'includes/class-reverse2pdf-core.php';
    if (file_exists($core_file)) {
        require_once $core_file;
        
        if (class_exists('Reverse2PDF_Core')) {
            $GLOBALS['reverse2pdf'] = new Reverse2PDF_Core();
        }
    }
}

/**
 * Cleanup scheduled event
 */
add_action('reverse2pdf_cleanup', 'reverse2pdf_cleanup_files');
function reverse2pdf_cleanup_files() {
    $settings = get_option('reverse2pdf_settings', array());
    if (!isset($settings['auto_cleanup']) || !$settings['auto_cleanup']) {
        return;
    }
    
    $days = isset($settings['cleanup_days']) ? intval($settings['cleanup_days']) : 7;
    $cutoff = time() - ($days * 24 * 60 * 60);
    
    $upload_dir = wp_upload_dir();
    $pdf_dir = $upload_dir['basedir'] . '/reverse2pdf/pdfs';
    
    if (is_dir($pdf_dir)) {
        $files = glob($pdf_dir . '/*.{pdf,html}', GLOB_BRACE);
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}

// Sample template data functions
function reverse2pdf_get_sample_invoice() {
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
                        'fontWeight' => 'bold'
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
                        'id' => 'total_amount',
                        'type' => 'text',
                        'x' => 350,
                        'y' => 400,
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
}

function reverse2pdf_get_sample_contact_form() {
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
                        'textAlign' => 'center'
                    ),
                    array(
                        'id' => 'name_field',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 150,
                        'width' => 495,
                        'height' => 25,
                        'content' => 'Name: {your-name}',
                        'fontSize' => 12
                    ),
                    array(
                        'id' => 'email_field',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 180,
                        'width' => 495,
                        'height' => 25,
                        'content' => 'Email: {your-email}',
                        'fontSize' => 12
                    ),
                    array(
                        'id' => 'message_field',
                        'type' => 'text',
                        'x' => 50,
                        'y' => 220,
                        'width' => 495,
                        'height' => 150,
                        'content' => 'Message: {your-message}',
                        'fontSize' => 12
                    )
                )
            )
        )
    );
}

function reverse2pdf_get_sample_certificate() {
    return array(
        'pages' => array(
            array(
                'width' => 842,
                'height' => 595,
                'elements' => array(
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
                        'textAlign' => 'center'
                    )
                )
            )
        )
    );
}

/**
 * Plugin action links
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'reverse2pdf_action_links');
function reverse2pdf_action_links($links) {
    $settings_link = '<a href="admin.php?page=reverse2pdf">Dashboard</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * Add plugin meta links
 */
add_filter('plugin_row_meta', 'reverse2pdf_plugin_row_meta', 10, 2);
function reverse2pdf_plugin_row_meta($links, $file) {
    if (strpos($file, 'reverse2pdf.php') !== false) {
        $new_links = array(
            'docs' => '<a href="https://reversecube.com/reverse2pdf/docs" target="_blank">Documentation</a>',
            'support' => '<a href="https://reversecube.com/support" target="_blank">Support</a>',
            'github' => '<a href="https://github.com/Reversecube/Reverse2PDF" target="_blank">GitHub</a>'
        );
        $links = array_merge($links, $new_links);
    }
    return $links;
}
?>
