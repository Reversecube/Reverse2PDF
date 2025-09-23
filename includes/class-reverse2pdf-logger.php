<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Logger {
    
    private $log_dir;
    private $enabled;
    
    public function __construct() {
        $settings = get_option('reverse2pdf_settings', array());
        $this->enabled = $settings['enable_debug'] ?? false;
        
        $upload_dir = wp_upload_dir();
        $this->log_dir = $upload_dir['basedir'] . '/reverse2pdf/logs';
        
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }
    }
    
    /**
     * Log message
     */
    public function log($level, $message, $context = array()) {
        if (!$this->enabled) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        );
        
        // Log to database
        $this->log_to_database($log_entry);
        
        // Log to file
        $this->log_to_file($log_entry);
        
        // Log to WordPress debug.log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Reverse2PDF [' . $level . ']: ' . $message . ' ' . json_encode($context));
        }
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Log to database
     */
    private function log_to_database($log_entry) {
        global $wpdb;
        
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_LOGS;
        
        $wpdb->insert($table, array(
            'template_id' => $log_entry['context']['template_id'] ?? 0,
            'user_id' => $log_entry['user_id'],
            'action' => 'log',
            'status' => strtolower($log_entry['level']),
            'message' => $log_entry['message'],
            'data' => json_encode($log_entry['context']),
            'ip_address' => $log_entry['ip_address'],
            'user_agent' => $log_entry['user_agent'],
            'created_date' => $log_entry['timestamp']
        ));
    }
    
    /**
     * Log to file
     */
    private function log_to_file($log_entry) {
        $log_file = $this->log_dir . '/reverse2pdf_' . date('Y-m-d') . '.log';
        
        $log_line = sprintf(
            "[%s] %s: %s %s\n",
            $log_entry['timestamp'],
            $log_entry['level'],
            $log_entry['message'],
            !empty($log_entry['context']) ? json_encode($log_entry['context']) : ''
        );
        
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get recent logs
     */
    public function get_recent_logs($limit = 100, $template_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_LOGS;
        $where = array('1=1');
        $params = array();
        
        if ($template_id) {
            $where[] = 'template_id = %d';
            $params[] = $template_id;
        }
        
        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY created_date DESC LIMIT %d";
        $params[] = $limit;
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Clear old logs
     */
    public function clear_old_logs($days = 30) {
        global $wpdb;
        
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_LOGS;
        $cutoff_date = date('Y-m-d H:i:s', time() - ($days * 24 * 60 * 60));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE created_date < %s",
            $cutoff_date
        ));
        
        // Clear log files
        $files = glob($this->log_dir . '/reverse2pdf_*.log');
        foreach ($files as $file) {
            if (filemtime($file) < time() - ($days * 24 * 60 * 60)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get log statistics
     */
    public function get_stats($days = 7) {
        global $wpdb;
        
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_LOGS;
        $start_date = date('Y-m-d H:i:s', time() - ($days * 24 * 60 * 60));
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                status,
                COUNT(*) as count,
                DATE(created_date) as date
            FROM $table 
            WHERE created_date >= %s 
            GROUP BY status, DATE(created_date)
            ORDER BY date DESC, status
        ", $start_date));
    }
}
?>
