<?php
/**
 * Reverse2PDF Uninstall Handler
 * 
 * This file runs when the plugin is deleted from the WordPress admin.
 * It removes all plugin data, files, and database entries.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load plugin constants
require_once plugin_dir_path(__FILE__) . 'reverse2pdf.php';

class Reverse2PDF_Uninstaller {
    
    public static function uninstall() {
        // Check if user has permission to delete plugins
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // Get uninstall options
        $settings = get_option('reverse2pdf_settings', array());
        $remove_data = isset($settings['remove_data_on_uninstall']) ? $settings['remove_data_on_uninstall'] : false;
        
        if ($remove_data) {
            self::remove_database_tables();
            self::remove_options();
            self::remove_files();
            self::remove_capabilities();
            self::cleanup_cron_jobs();
        }
        
        // Always remove the main plugin options
        delete_option('reverse2pdf_version');
        delete_option('reverse2pdf_activation_time');
    }
    
    /**
     * Remove database tables
     */
    private static function remove_database_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'reverse2pdf_templates',
            $wpdb->prefix . 'reverse2pdf_integrations',
            $wpdb->prefix . 'reverse2pdf_logs'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Remove plugin options
     */
    private static function remove_options() {
        $options = array(
            'reverse2pdf_settings',
            'reverse2pdf_version',
            'reverse2pdf_activation_time',
            'reverse2pdf_db_version',
            'reverse2pdf_sample_templates_installed',
            'reverse2pdf_wizard_completed',
            'reverse2pdf_cache_cleared',
            'reverse2pdf_upgrade_notice_dismissed'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Remove site options (for multisite)
        foreach ($options as $option) {
            delete_site_option($option);
        }
        
        // Remove user meta
        $users = get_users(array('meta_key' => 'reverse2pdf_*'));
        foreach ($users as $user) {
            $user_meta = get_user_meta($user->ID);
            foreach ($user_meta as $key => $value) {
                if (strpos($key, 'reverse2pdf_') === 0) {
                    delete_user_meta($user->ID, $key);
                }
            }
        }
    }
    
    /**
     * Remove uploaded files and directories
     */
    private static function remove_files() {
        $upload_dir = wp_upload_dir();
        $plugin_dir = $upload_dir['basedir'] . '/reverse2pdf';
        
        if (is_dir($plugin_dir)) {
            self::delete_directory($plugin_dir);
        }
        
        // Remove backup files
        $backup_dir = $upload_dir['basedir'] . '/reverse2pdf-backups';
        if (is_dir($backup_dir)) {
            self::delete_directory($backup_dir);
        }
    }
    
    /**
     * Recursively delete directory
     */
    private static function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $filepath = $dir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($filepath)) {
                self::delete_directory($filepath);
            } else {
                unlink($filepath);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Remove custom capabilities
     */
    private static function remove_capabilities() {
        $capabilities = array(
            'reverse2pdf_create_templates',
            'reverse2pdf_edit_templates',
            'reverse2pdf_delete_templates',
            'reverse2pdf_manage_integrations',
            'reverse2pdf_view_logs',
            'reverse2pdf_manage_settings'
        );
        
        // Get all roles
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        // Remove capabilities from all roles
        foreach ($wp_roles->roles as $role_name => $role_info) {
            $role = get_role($role_name);
            
            if ($role) {
                foreach ($capabilities as $capability) {
                    $role->remove_cap($capability);
                }
            }
        }
    }
    
    /**
     * Clean up cron jobs
     */
    private static function cleanup_cron_jobs() {
        // Remove scheduled events
        wp_clear_scheduled_hook('reverse2pdf_cleanup_old_pdfs');
        wp_clear_scheduled_hook('reverse2pdf_cleanup_logs');
        wp_clear_scheduled_hook('reverse2pdf_cleanup_cache');
        wp_clear_scheduled_hook('reverse2pdf_backup_templates');
        
        // Remove custom cron schedules
        $cron_schedules = get_option('cron');
        if (is_array($cron_schedules)) {
            foreach ($cron_schedules as $timestamp => $cron) {
                if (is_array($cron)) {
                    foreach ($cron as $hook => $events) {
                        if (strpos($hook, 'reverse2pdf_') === 0) {
                            wp_unschedule_event($timestamp, $hook);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Remove transients
     */
    private static function remove_transients() {
        global $wpdb;
        
        // Delete transients from options table
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_reverse2pdf_%' 
            OR option_name LIKE '_transient_timeout_reverse2pdf_%'
        ");
        
        // Delete transients from site options (multisite)
        if (is_multisite()) {
            $wpdb->query("
                DELETE FROM {$wpdb->sitemeta} 
                WHERE meta_key LIKE '_site_transient_reverse2pdf_%' 
                OR meta_key LIKE '_site_transient_timeout_reverse2pdf_%'
            ");
        }
    }
    
    /**
     * Log uninstall activity
     */
    private static function log_uninstall() {
        error_log('Reverse2PDF: Plugin uninstalled at ' . current_time('mysql'));
    }
}

// Run the uninstaller
Reverse2PDF_Uninstaller::uninstall();
?>
