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
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('REVERSE2PDF_VERSION', '2.0.0');
define('REVERSE2PDF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REVERSE2PDF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('REVERSE2PDF_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Database constants
define('REVERSE2PDF_TABLE_TEMPLATES', 'reverse2pdf_templates');
define('REVERSE2PDF_TABLE_LOGS', 'reverse2pdf_logs');
define('REVERSE2PDF_TABLE_INTEGRATIONS', 'reverse2pdf_integrations');

// Requirements check
function reverse2pdf_check_requirements() {
    $errors = array();
    
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = 'PHP 7.4+ required. Current: ' . PHP_VERSION;
    }
    
    if (!extension_loaded('json')) {
        $errors[] = 'JSON extension required';
    }
    
    return $errors;
}

// Activation hook
register_activation_hook(__FILE__, 'reverse2pdf_activate');
function reverse2pdf_activate() {
    $errors = reverse2pdf_check_requirements();
    if (!empty($errors)) {
        wp_die('<h1>Activation Failed</h1><ul><li>' . implode('</li><li>', array_map('esc_html', $errors)) . '</li></ul>');
    }
    
    reverse2pdf_create_tables();
    reverse2pdf_create_folders();
    
    add_option('reverse2pdf_version', REVERSE2PDF_VERSION);
    add_option('reverse2pdf_settings', array(
        'pdf_engine' => 'dompdf',
        'paper_size' => 'A4',
        'paper_orientation' => 'portrait'
    ));
}

// Create database tables
function reverse2pdf_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        template_data longtext,
        settings longtext,
        active tinyint(1) DEFAULT 1,
        created_by int(11) DEFAULT 0,
        created_date datetime DEFAULT CURRENT_TIMESTAMP,
        modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Create folders
function reverse2pdf_create_folders() {
    $upload_dir = wp_upload_dir();
    $folders = array(
        $upload_dir['basedir'] . '/reverse2pdf',
        $upload_dir['basedir'] . '/reverse2pdf/pdfs',
        $upload_dir['basedir'] . '/reverse2pdf/cache',
        $upload_dir['basedir'] . '/reverse2pdf/logs'
    );
    
    foreach ($folders as $folder) {
        if (!file_exists($folder)) {
            wp_mkdir_p($folder);
        }
    }
    
    file_put_contents($upload_dir['basedir'] . '/reverse2pdf/.htaccess', 'Deny from all');
    file_put_contents($upload_dir['basedir'] . '/reverse2pdf/index.php', '<?php // Silence is golden');
}

// Load plugin
add_action('plugins_loaded', 'reverse2pdf_load_plugin');
function reverse2pdf_load_plugin() {
    load_plugin_textdomain('reverse2pdf', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Load core safely
    $core_file = REVERSE2PDF_PLUGIN_DIR . 'includes/class-reverse2pdf-core.php';
    if (file_exists($core_file)) {
        require_once $core_file;
        if (class_exists('Reverse2PDF_Core')) {
            $GLOBALS['reverse2pdf'] = new Reverse2PDF_Core();
        }
    }
}

// Admin menu (fallback)
add_action('admin_menu', 'reverse2pdf_admin_menu');
function reverse2pdf_admin_menu() {
    add_menu_page(
        'Reverse2PDF',
        'Reverse2PDF',
        'manage_options',
        'reverse2pdf',
        'reverse2pdf_admin_page',
        'dashicons-media-document'
    );
}

// Basic admin page
function reverse2pdf_admin_page() {
    ?>
    <div class="wrap">
        <h1>Reverse2PDF Pro</h1>
        <div class="notice notice-success"><p>Plugin loaded successfully!</p></div>
        
        <div class="card">
            <h2>System Status</h2>
            <table class="widefat">
                <tr>
                    <td><strong>PHP Version:</strong></td>
                    <td><?php echo PHP_VERSION; ?> <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? '✅' : '❌'; ?></td>
                </tr>
                <tr>
                    <td><strong>WordPress:</strong></td>
                    <td><?php echo get_bloginfo('version'); ?> ✅</td>
                </tr>
                <tr>
                    <td><strong>Plugin Version:</strong></td>
                    <td><?php echo REVERSE2PDF_VERSION; ?> ✅</td>
                </tr>
                <tr>
                    <td><strong>Core Files:</strong></td>
                    <td>
                        <?php
                        $core_files = array(
                            'Core' => 'includes/class-reverse2pdf-core.php',
                            'Admin' => 'includes/class-reverse2pdf-admin.php',
                            'Generator' => 'includes/class-reverse2pdf-enhanced-generator.php'
                        );
                        
                        foreach ($core_files as $name => $file) {
                            $exists = file_exists(REVERSE2PDF_PLUGIN_DIR . $file);
                            echo "<strong>$name:</strong> " . ($exists ? '✅' : '❌') . "<br>";
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}
?>
