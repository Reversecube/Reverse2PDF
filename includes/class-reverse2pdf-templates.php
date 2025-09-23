<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Templates {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;
    }
    
    /**
     * Create new template
     */
    public function create_template($data) {
        global $wpdb;
        
        $template_data = array(
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'template_data' => wp_json_encode($data['template_data'] ?? array()),
            'settings' => wp_json_encode($data['settings'] ?? array()),
            'form_type' => sanitize_text_field($data['form_type'] ?? ''),
            'form_id' => sanitize_text_field($data['form_id'] ?? ''),
            'active' => intval($data['active'] ?? 1),
            'created_by' => get_current_user_id(),
            'created_date' => current_time('mysql'),
            'modified_date' => current_time('mysql')
        );
        
        $result = $wpdb->insert($this->table_name, $template_data);
        
        if ($result !== false) {
            $template_id = $wpdb->insert_id;
            do_action('reverse2pdf_template_created', $template_id, $template_data);
            return $template_id;
        }
        
        return false;
    }
    
    /**
     * Update template
     */
    public function update_template($template_id, $data) {
        global $wpdb;
        
        $template_data = array(
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'template_data' => wp_json_encode($data['template_data'] ?? array()),
            'settings' => wp_json_encode($data['settings'] ?? array()),
            'modified_date' => current_time('mysql')
        );
        
        if (isset($data['active'])) {
            $template_data['active'] = intval($data['active']);
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $template_data,
            array('id' => $template_id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            do_action('reverse2pdf_template_updated', $template_id, $template_data);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get template by ID
     */
    public function get_template($template_id) {
        global $wpdb;
        
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE id = %d",
            $template_id
        ));
        
        if ($template) {
            $template->template_data = json_decode($template->template_data, true);
            $template->settings = json_decode($template->settings, true);
        }
        
        return $template;
    }
    
    /**
     * Get all templates
     */
    public function get_templates($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'active_only' => false,
            'form_type' => '',
            'orderby' => 'created_date',
            'order' => 'DESC',
            'limit' => 0,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        if ($args['active_only']) {
            $where_clauses[] = 'active = %d';
            $where_values[] = 1;
        }
        
        if (!empty($args['form_type'])) {
            $where_clauses[] = 'form_type = %s';
            $where_values[] = $args['form_type'];
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        $order_sql = sprintf(
            'ORDER BY %s %s',
            sanitize_sql_orderby($args['orderby']),
            $args['order'] === 'ASC' ? 'ASC' : 'DESC'
        );
        
        $limit_sql = '';
        if ($args['limit'] > 0) {
            $limit_sql = $wpdb->prepare('LIMIT %d', $args['limit']);
            if ($args['offset'] > 0) {
                $limit_sql = $wpdb->prepare('LIMIT %d, %d', $args['offset'], $args['limit']);
            }
        }
        
        $sql = "SELECT * FROM $this->table_name $where_sql $order_sql $limit_sql";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Delete template
     */
    public function delete_template($template_id) {
        global $wpdb;
        
        // Get template data before deletion
        $template = $this->get_template($template_id);
        
        if (!$template) {
            return false;
        }
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $template_id),
            array('%d')
        );
        
        if ($result !== false) {
            do_action('reverse2pdf_template_deleted', $template_id, $template);
            return true;
        }
        
        return false;
    }
    
    /**
     * Duplicate template
     */
    public function duplicate_template($template_id) {
        $original = $this->get_template($template_id);
        
        if (!$original) {
            return false;
        }
        
        $duplicate_data = array(
            'name' => $original->name . ' (Copy)',
            'description' => $original->description,
            'template_data' => $original->template_data,
            'settings' => $original->settings,
            'form_type' => $original->form_type,
            'form_id' => $original->form_id,
            'active' => 1
        );
        
        return $this->create_template($duplicate_data);
    }
    
    /**
     * Export template
     */
    public function export_template($template_id) {
        $template = $this->get_template($template_id);
        
        if (!$template) {
            return false;
        }
        
        $export_data = array(
            'version' => REVERSE2PDF_VERSION,
            'export_date' => current_time('mysql'),
            'template' => array(
                'name' => $template->name,
                'description' => $template->description,
                'template_data' => $template->template_data,
                'settings' => $template->settings
            )
        );
        
        do_action('reverse2pdf_template_exported', $template_id, $export_data);
        
        return $export_data;
    }
    
    /**
     * Import template
     */
    public function import_template($import_data) {
        if (!isset($import_data['template'])) {
            return false;
        }
        
        $template_data = $import_data['template'];
        
        // Validate required fields
        if (empty($template_data['name'])) {
            return false;
        }
        
        $new_template_data = array(
            'name' => $template_data['name'] . ' (Imported)',
            'description' => $template_data['description'] ?? '',
            'template_data' => $template_data['template_data'] ?? array(),
            'settings' => $template_data['settings'] ?? array(),
            'active' => 1
        );
        
        $template_id = $this->create_template($new_template_data);
        
        if ($template_id) {
            do_action('reverse2pdf_template_imported', $template_id, $import_data);
        }
        
        return $template_id;
    }
    
    /**
     * Search templates
     */
    public function search_templates($search_term, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'active_only' => false,
            'limit' => 50
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        // Search in name and description
        $where_clauses[] = '(name LIKE %s OR description LIKE %s)';
        $search_like = '%' . $wpdb->esc_like($search_term) . '%';
        $where_values[] = $search_like;
        $where_values[] = $search_like;
        
        if ($args['active_only']) {
            $where_clauses[] = 'active = %d';
            $where_values[] = 1;
        }
        
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        $limit_sql = $wpdb->prepare('LIMIT %d', $args['limit']);
        
        $sql = "SELECT * FROM $this->table_name $where_sql ORDER BY name ASC $limit_sql";
        $sql = $wpdb->prepare($sql, $where_values);
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get template statistics
     */
    public function get_template_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total templates
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name");
        
        // Active templates
        $stats['active'] = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name WHERE active = 1");
        
        // Templates by form type
        $stats['by_form_type'] = $wpdb->get_results("
            SELECT form_type, COUNT(*) as count 
            FROM $this->table_name 
            WHERE form_type != '' 
            GROUP BY form_type
        ");
        
        // Recently created (last 7 days)
        $week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        $stats['recent'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE created_date > %s",
            $week_ago
        ));
        
        return $stats;
    }
    
    /**
     * Bulk update templates
     */
    public function bulk_update($template_ids, $action, $data = array()) {
        global $wpdb;
        
        if (empty($template_ids) || !is_array($template_ids)) {
            return false;
        }
        
        $template_ids = array_map('intval', $template_ids);
        $ids_placeholder = implode(',', array_fill(0, count($template_ids), '%d'));
        
        switch ($action) {
            case 'activate':
                $sql = "UPDATE $this->table_name SET active = 1 WHERE id IN ($ids_placeholder)";
                break;
                
            case 'deactivate':
                $sql = "UPDATE $this->table_name SET active = 0 WHERE id IN ($ids_placeholder)";
                break;
                
            case 'delete':
                $sql = "DELETE FROM $this->table_name WHERE id IN ($ids_placeholder)";
                break;
                
            default:
                return false;
        }
        
        $result = $wpdb->query($wpdb->prepare($sql, $template_ids));
        
        if ($result !== false) {
            do_action('reverse2pdf_templates_bulk_updated', $template_ids, $action, $data);
            return $result;
        }
        
        return false;
    }
}
?>
