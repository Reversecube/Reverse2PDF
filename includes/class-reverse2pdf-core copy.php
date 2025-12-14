<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Core {
    
    private $version;
    private $components = array();
    
    public function __construct() {
        $this->version = REVERSE2PDF_VERSION;
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_components();
    }
    
    private function load_dependencies() {
        $includes = array(
            'class-reverse2pdf-admin.php',
            'class-reverse2pdf-generator.php',
            'class-reverse2pdf-enhanced-generator.php',
            'class-reverse2pdf-shortcodes.php',
            'class-reverse2pdf-integrations.php',
            'class-reverse2pdf-visual-mapper.php',
            'class-reverse2pdf-conditional-logic.php',
            'class-reverse2pdf-templates.php',
            'class-reverse2pdf-logger.php'
        );
        
        foreach ($includes as $file) {
            $path = REVERSE2PDF_PLUGIN_DIR . 'includes/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        if (is_admin()) {
            $admin = new Reverse2PDF_Admin();
            $admin->handle_ajax_requests();
        }
        // AJAX handlers
        add_action('wp_ajax_reverse2pdf_generate_pdf', array($this, 'ajax_generate_pdf'));
        add_action('wp_ajax_nopriv_reverse2pdf_generate_pdf', array($this, 'ajax_generate_pdf'));
        add_action('wp_ajax_reverse2pdf_save_template', array($this, 'ajax_save_template'));
        add_action('wp_ajax_reverse2pdf_load_template', array($this, 'ajax_load_template'));


        // Template AJAX handlers
        add_action('wp_ajax_reverse2pdf_delete_template', array($this, 'ajax_delete_template'));
        add_action('wp_ajax_reverse2pdf_get_templates', array($this, 'ajax_get_templates'));


        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Handle downloads
        add_action('init', array($this, 'handle_downloads'));
    }
    
    private function init_components() {
        // if (is_admin() && class_exists('Reverse2PDF_Admin')) {
        //     $this->components['admin'] = new Reverse2PDF_Admin();
        // }
        
        if (class_exists('Reverse2PDF_Shortcodes')) {
            $this->components['shortcodes'] = new Reverse2PDF_Shortcodes();
        }
        
        if (class_exists('Reverse2PDF_Integrations')) {
            $this->components['integrations'] = new Reverse2PDF_Integrations();
        }
        
        if (class_exists('Reverse2PDF_Visual_Mapper')) {
            $this->components['visual_mapper'] = new Reverse2PDF_Visual_Mapper();
        }
        
        if (class_exists('Reverse2PDF_Conditional_Logic')) {
            $this->components['conditional_logic'] = new Reverse2PDF_Conditional_Logic();
        }
        
        if (class_exists('Reverse2PDF_Templates')) {
            $this->components['templates'] = new Reverse2PDF_Templates();
        }
        
        if (class_exists('Reverse2PDF_Logger')) {
            $this->components['logger'] = new Reverse2PDF_Logger();
        }
    }
    
    public function init() {
        do_action('reverse2pdf_init');
    }
    
    public function admin_init() {
        // Register settings
        register_setting('reverse2pdf_settings', 'reverse2pdf_settings', array($this, 'sanitize_settings'));
        
        // Add settings sections
        add_settings_section(
            'reverse2pdf_general',
            'General Settings',
            array($this, 'general_settings_callback'),
            'reverse2pdf_settings'
        );
        
        add_settings_section(
            'reverse2pdf_pdf',
            'PDF Generation',
            array($this, 'pdf_settings_callback'),
            'reverse2pdf_settings'
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    private function add_settings_fields() {
        add_settings_field(
            'pdf_engine',
            'PDF Engine',
            array($this, 'pdf_engine_field'),
            'reverse2pdf_settings',
            'reverse2pdf_pdf'
        );
        
        add_settings_field(
            'paper_size',
            'Default Paper Size',
            array($this, 'paper_size_field'),
            'reverse2pdf_settings',
            'reverse2pdf_pdf'
        );
        
        add_settings_field(
            'enable_cache',
            'Enable Caching',
            array($this, 'enable_cache_field'),
            'reverse2pdf_settings',
            'reverse2pdf_general'
        );
    }
    
    public function frontend_scripts() {
        wp_enqueue_style(
            'reverse2pdf-frontend',
            REVERSE2PDF_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'reverse2pdf-frontend',
            REVERSE2PDF_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('reverse2pdf-frontend', 'reverse2pdf_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reverse2pdf_nonce')
        ));
    }
    
    public function admin_scripts($hook) {
        if (strpos($hook, 'reverse2pdf') === false) {
            return;
        }
        
        wp_enqueue_style(
            'reverse2pdf-admin',
            REVERSE2PDF_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'reverse2pdf-admin',
            REVERSE2PDF_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'),
            $this->version,
            true
        );
        
        wp_localize_script('reverse2pdf-admin', 'reverse2pdf_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reverse2pdf_nonce'),
            'plugin_url' => REVERSE2PDF_PLUGIN_URL
        ));
    }
    
    public function add_meta_boxes() {
        $post_types = get_post_types(array('public' => true));
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'reverse2pdf_generator',
                'PDF Generator',
                array($this, 'render_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    public function render_meta_box($post) {
        wp_nonce_field('reverse2pdf_meta_box', 'reverse2pdf_meta_box_nonce');
        
        global $wpdb;
        $templates = $wpdb->get_results(
            "SELECT id, name FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " WHERE active = 1 ORDER BY name"
        );
        
        echo '<p><label for="reverse2pdf_template">Select Template:</label></p>';
        echo '<select id="reverse2pdf_template" style="width: 100%;">';
        echo '<option value="">Select a template</option>';
        
        foreach ($templates as $template) {
            echo '<option value="' . esc_attr($template->id) . '">' . esc_html($template->name) . '</option>';
        }
        
        echo '</select>';
        echo '<p><button type="button" id="reverse2pdf_generate_btn" class="button button-primary" style="width: 100%;" data-post-id="' . $post->ID . '">Generate PDF</button></p>';
    }
    
    public function handle_downloads() {
        if (isset($_GET['reverse2pdf_download']) && wp_verify_nonce($_GET['_wpnonce'], 'reverse2pdf_download')) {
            $template_id = intval($_GET['template_id']);
            
            if ($template_id && class_exists('Reverse2PDF_Enhanced_Generator')) {
                $generator = new Reverse2PDF_Enhanced_Generator();
                $pdf_path = $generator->generate_pdf($template_id, 0);
                
                if ($pdf_path) {
                    $filename = 'document_' . $template_id . '_' . time() . '.pdf';
                    
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    
                    if (file_exists($pdf_path)) {
                        readfile($pdf_path);
                    } else {
                        // Return HTML version if PDF doesn't exist
                        echo $pdf_path;
                    }
                    
                    exit;
                }
            }
        }
    }
    
    // AJAX handlers
    public function ajax_generate_pdf() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'reverse2pdf_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        if (!$template_id) {
            wp_send_json_error('Template ID required');
        }
        
        if (class_exists('Reverse2PDF_Enhanced_Generator')) {
            $generator = new Reverse2PDF_Enhanced_Generator();
            $result = $generator->generate_pdf($template_id, 0, $_POST['form_data'] ?? array());
            
            if ($result) {
                wp_send_json_success(array('pdf_url' => $result));
            } else {
                wp_send_json_error('PDF generation failed');
            }
        } else {
            wp_send_json_error('PDF generator not available');
        }
    }
    
    public function ajax_save_template() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'reverse2pdf_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('reverse2pdf_edit_templates')) {
            wp_send_json_error('Permission denied');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        $template_name = sanitize_text_field($_POST['template_name'] ?? '');
        $template_data = wp_unslash($_POST['template_data'] ?? '');
        
        global $wpdb;
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;
        
        $data = array(
            'name' => $template_name,
            'template_data' => $template_data,
            'modified_date' => current_time('mysql')
        );
        
        if ($template_id) {
            $result = $wpdb->update($table, $data, array('id' => $template_id));
        } else {
            $data['created_by'] = get_current_user_id();
            $data['created_date'] = current_time('mysql');
            $result = $wpdb->insert($table, $data);
            $template_id = $wpdb->insert_id;
        }
        
        if ($result !== false) {
            wp_send_json_success(array('template_id' => $template_id));
        } else {
            wp_send_json_error('Failed to save template');
        }
    }
    
    public function ajax_load_template() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'reverse2pdf_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        if (!$template_id) {
            wp_send_json_error('Template ID required');
        }
        
        global $wpdb;
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " WHERE id = %d",
            $template_id
        ));
        
        if ($template) {
            wp_send_json_success($template);
        } else {
            wp_send_json_error('Template not found');
        }
    }
    
    public function ajax_delete_template() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'reverse2pdf_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        if (!$template_id) {
            wp_send_json_error('Template ID required');
        }
        
        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES,
            array('id' => $template_id),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Template deleted successfully');
        } else {
            wp_send_json_error('Failed to delete template');
        }
    }

    public function ajax_get_templates() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'reverse2pdf_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $templates = $wpdb->get_results(
            "SELECT id, name, description FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " WHERE active = 1 ORDER BY name"
        );
        
        wp_send_json_success($templates);
    }

    // Settings callbacks
    public function general_settings_callback() {
        echo '<p>General plugin settings</p>';
    }
    
    public function pdf_settings_callback() {
        echo '<p>PDF generation settings</p>';
    }
    
    public function pdf_engine_field() {
        $settings = get_option('reverse2pdf_settings', array());
        $value = $settings['pdf_engine'] ?? 'dompdf';
        
        echo '<select name="reverse2pdf_settings[pdf_engine]">';
        echo '<option value="dompdf"' . selected($value, 'dompdf', false) . '>DomPDF</option>';
        echo '<option value="tcpdf"' . selected($value, 'tcpdf', false) . '>TCPDF</option>';
        echo '<option value="mpdf"' . selected($value, 'mpdf', false) . '>mPDF</option>';
        echo '</select>';
    }
    
    public function paper_size_field() {
        $settings = get_option('reverse2pdf_settings', array());
        $value = $settings['paper_size'] ?? 'A4';
        
        echo '<select name="reverse2pdf_settings[paper_size]">';
        echo '<option value="A4"' . selected($value, 'A4', false) . '>A4</option>';
        echo '<option value="A3"' . selected($value, 'A3', false) . '>A3</option>';
        echo '<option value="Letter"' . selected($value, 'Letter', false) . '>Letter</option>';
        echo '</select>';
    }
    
    public function enable_cache_field() {
        $settings = get_option('reverse2pdf_settings', array());
        $value = $settings['enable_cache'] ?? true;
        
        echo '<input type="checkbox" name="reverse2pdf_settings[enable_cache]" value="1"' . checked($value, 1, false) . ' />';
        echo ' <label>Enable PDF caching for better performance</label>';
    }
    
    public function sanitize_settings($input) {
        $output = array();
        
        if (isset($input['pdf_engine'])) {
            $output['pdf_engine'] = sanitize_text_field($input['pdf_engine']);
        }
        
        if (isset($input['paper_size'])) {
            $output['paper_size'] = sanitize_text_field($input['paper_size']);
        }
        
        $output['enable_cache'] = isset($input['enable_cache']) ? 1 : 0;
        
        return $output;
    }
}
?>
