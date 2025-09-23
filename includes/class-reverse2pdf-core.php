<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Core {
    
    private $version = '2.0.0';
    private $components = array();
    
    public function __construct() {
        $this->define_constants();
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_components();
    }
    
    private function define_constants() {
        if (!defined('REVERSE2PDF_LOADED')) {
            define('REVERSE2PDF_LOADED', true);
        }
    }
    
    private function load_dependencies() {
        $includes = array(
            'class-reverse2pdf-admin',
            'class-reverse2pdf-generator',
            'class-reverse2pdf-enhanced-generator', 
            'class-reverse2pdf-shortcodes',
            'class-reverse2pdf-integrations',
            'class-reverse2pdf-visual-mapper',
            'class-reverse2pdf-conditional-logic',
            'class-reverse2pdf-templates',
            'class-reverse2pdf-logger'
        );
        
        foreach ($includes as $file) {
            $path = REVERSE2PDF_PLUGIN_DIR . 'includes/' . $file . '.php';
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
        add_action('wp_ajax_reverse2pdf_save_template', array($this, 'ajax_save_template'));
        add_action('wp_ajax_reverse2pdf_load_template', array($this, 'ajax_load_template'));
        add_action('wp_ajax_reverse2pdf_delete_template', array($this, 'ajax_delete_template'));
        add_action('wp_ajax_reverse2pdf_duplicate_template', array($this, 'ajax_duplicate_template'));
        add_action('wp_ajax_reverse2pdf_auto_mapper', array($this, 'ajax_auto_mapper'));
        add_action('wp_ajax_reverse2pdf_get_forms', array($this, 'ajax_get_forms'));
        add_action('wp_ajax_reverse2pdf_setup_integration', array($this, 'ajax_setup_integration'));
        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Add shortcode button to editor
        add_action('media_buttons', array($this, 'add_shortcode_button'));
        
        // Handle file downloads
        add_action('init', array($this, 'handle_downloads'));
    }
    
    private function init_components() {
        // Admin interface
        if (is_admin()) {
            $this->components['admin'] = new Reverse2PDF_Admin();
            $this->components['visual_mapper'] = new Reverse2PDF_Visual_Mapper();
        }
        
        // Always load these
        $this->components['shortcodes'] = new Reverse2PDF_Shortcodes();
        $this->components['integrations'] = new Reverse2PDF_Integrations();
        $this->components['conditional_logic'] = new Reverse2PDF_Conditional_Logic();
        $this->components['templates'] = new Reverse2PDF_Templates();
        $this->components['logger'] = new Reverse2PDF_Logger();
    }
    
    public function init() {
        // Initialize components
        do_action('reverse2pdf_init');
    }
    
    public function admin_init() {
        // Register settings
        register_setting('reverse2pdf_settings', 'reverse2pdf_settings', array($this, 'sanitize_settings'));
        
        // Add settings sections
        add_settings_section(
            'reverse2pdf_general',
            __('General Settings', 'reverse2pdf'),
            array($this, 'general_settings_callback'),
            'reverse2pdf_settings'
        );
        
        add_settings_section(
            'reverse2pdf_pdf',
            __('PDF Generation', 'reverse2pdf'),
            array($this, 'pdf_settings_callback'),
            'reverse2pdf_settings'
        );
        
        add_settings_section(
            'reverse2pdf_performance',
            __('Performance', 'reverse2pdf'),
            array($this, 'performance_settings_callback'),
            'reverse2pdf_settings'
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    private function add_settings_fields() {
        // PDF Engine
        add_settings_field(
            'pdf_engine',
            __('PDF Engine', 'reverse2pdf'),
            array($this, 'pdf_engine_field'),
            'reverse2pdf_settings',
            'reverse2pdf_pdf'
        );
        
        // Paper Size
        add_settings_field(
            'paper_size',
            __('Default Paper Size', 'reverse2pdf'),
            array($this, 'paper_size_field'),
            'reverse2pdf_settings',
            'reverse2pdf_pdf'
        );
        
        // Default Font
        add_settings_field(
            'default_font',
            __('Default Font', 'reverse2pdf'),
            array($this, 'default_font_field'),
            'reverse2pdf_settings',
            'reverse2pdf_pdf'
        );
        
        // Enable Cache
        add_settings_field(
            'enable_cache',
            __('Enable Caching', 'reverse2pdf'),
            array($this, 'enable_cache_field'),
            'reverse2pdf_settings',
            'reverse2pdf_performance'
        );
        
        // Debug Mode
        add_settings_field(
            'enable_debug',
            __('Debug Mode', 'reverse2pdf'),
            array($this, 'enable_debug_field'),
            'reverse2pdf_settings',
            'reverse2pdf_general'
        );
    }
    
    public function frontend_scripts() {
        wp_enqueue_style(
            'reverse2pdf-frontend',
            REVERSE2PDF_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            REVERSE2PDF_VERSION
        );
        
        wp_enqueue_script(
            'reverse2pdf-frontend',
            REVERSE2PDF_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            REVERSE2PDF_VERSION,
            true
        );
        
        wp_localize_script('reverse2pdf-frontend', 'reverse2pdf_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reverse2pdf_nonce'),
            'strings' => array(
                'generating' => __('Generating PDF...', 'reverse2pdf'),
                'error' => __('Error occurred', 'reverse2pdf'),
                'success' => __('PDF generated successfully', 'reverse2pdf')
            )
        ));
    }
    
    public function admin_scripts($hook) {
        if (strpos($hook, 'reverse2pdf') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'reverse2pdf-admin',
            REVERSE2PDF_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            REVERSE2PDF_VERSION
        );
        
        wp_enqueue_style('wp-color-picker');
        
        // JS
        wp_enqueue_script(
            'reverse2pdf-admin',
            REVERSE2PDF_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            REVERSE2PDF_VERSION,
            true
        );
        
        // Template builder
        if (strpos($hook, 'reverse2pdf-builder') !== false) {
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_script('jquery-ui-droppable');
            wp_enqueue_script('jquery-ui-resizable');
            wp_enqueue_script('jquery-ui-sortable');
            
            wp_enqueue_script(
                'reverse2pdf-builder',
                REVERSE2PDF_PLUGIN_URL . 'assets/js/template-builder.js',
                array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-resizable'),
                REVERSE2PDF_VERSION,
                true
            );
        }
        
        wp_localize_script('reverse2pdf-admin', 'reverse2pdf_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reverse2pdf_nonce'),
            'plugin_url' => REVERSE2PDF_PLUGIN_URL,
            'strings' => array(
                'save' => __('Save', 'reverse2pdf'),
                'saving' => __('Saving...', 'reverse2pdf'),
                'saved' => __('Saved', 'reverse2pdf'),
                'error' => __('Error', 'reverse2pdf'),
                'confirm_delete' => __('Are you sure you want to delete this template?', 'reverse2pdf'),
                'confirm_duplicate' => __('Duplicate this template?', 'reverse2pdf'),
                'generating' => __('Generating...', 'reverse2pdf'),
                'select_template' => __('Please select a template', 'reverse2pdf'),
                'template_required' => __('Template is required', 'reverse2pdf')
            ),
            'element_types' => $this->get_element_types()
        ));
    }
    
    private function get_element_types() {
        return array(
            'basic' => array(
                'text' => array('name' => __('Text', 'reverse2pdf'), 'icon' => 'dashicons-editor-textcolor'),
                'image' => array('name' => __('Image', 'reverse2pdf'), 'icon' => 'dashicons-format-image'),
                'line' => array('name' => __('Line', 'reverse2pdf'), 'icon' => 'dashicons-minus'),
                'rectangle' => array('name' => __('Rectangle', 'reverse2pdf'), 'icon' => 'dashicons-admin-page')
            ),
            'form' => array(
                'input' => array('name' => __('Text Field', 'reverse2pdf'), 'icon' => 'dashicons-edit'),
                'textarea' => array('name' => __('Textarea', 'reverse2pdf'), 'icon' => 'dashicons-editor-alignleft'),
                'checkbox' => array('name' => __('Checkbox', 'reverse2pdf'), 'icon' => 'dashicons-yes-alt'),
                'radio' => array('name' => __('Radio', 'reverse2pdf'), 'icon' => 'dashicons-marker'),
                'select' => array('name' => __('Select', 'reverse2pdf'), 'icon' => 'dashicons-arrow-down-alt2')
            ),
            'advanced' => array(
                'table' => array('name' => __('Table', 'reverse2pdf'), 'icon' => 'dashicons-grid-view'),
                'qr' => array('name' => __('QR Code', 'reverse2pdf'), 'icon' => 'dashicons-screenoptions'),
                'barcode' => array('name' => __('Barcode', 'reverse2pdf'), 'icon' => 'dashicons-admin-links'),
                'signature' => array('name' => __('Signature', 'reverse2pdf'), 'icon' => 'dashicons-edit-large'),
                'chart' => array('name' => __('Chart', 'reverse2pdf'), 'icon' => 'dashicons-chart-bar')
            ),
            'wordpress' => array(
                'post_title' => array('name' => __('Post Title', 'reverse2pdf'), 'icon' => 'dashicons-format-aside'),
                'post_content' => array('name' => __('Post Content', 'reverse2pdf'), 'icon' => 'dashicons-admin-post'),
                'featured_image' => array('name' => __('Featured Image', 'reverse2pdf'), 'icon' => 'dashicons-format-gallery'),
                'meta_field' => array('name' => __('Meta Field', 'reverse2pdf'), 'icon' => 'dashicons-admin-generic'),
                'user_info' => array('name' => __('User Info', 'reverse2pdf'), 'icon' => 'dashicons-admin-users')
            )
        );
    }
    
    public function add_meta_boxes() {
        $post_types = get_post_types(array('public' => true));
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'reverse2pdf_generator',
                __('PDF Generator', 'reverse2pdf'),
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
        
        echo '<p><label for="reverse2pdf_template">' . __('Select Template:', 'reverse2pdf') . '</label></p>';
        echo '<select id="reverse2pdf_template" name="reverse2pdf_template" style="width: 100%;">';
        echo '<option value="">' . __('Select a template', 'reverse2pdf') . '</option>';
        
        foreach ($templates as $template) {
            echo '<option value="' . esc_attr($template->id) . '">' . esc_html($template->name) . '</option>';
        }
        
        echo '</select>';
        echo '<p style="margin-top: 10px;">';
        echo '<button type="button" id="reverse2pdf_generate_btn" class="button button-primary" style="width: 100%;" data-post-id="' . $post->ID . '">';
        echo __('Generate PDF', 'reverse2pdf');
        echo '</button>';
        echo '</p>';
        echo '<p>';
        echo '<button type="button" id="reverse2pdf_preview_btn" class="button button-secondary" style="width: 100%;" data-post-id="' . $post->ID . '">';
        echo __('Preview PDF', 'reverse2pdf');
        echo '</button>';
        echo '</p>';
    }
    
    public function add_shortcode_button() {
        echo '<button type="button" id="reverse2pdf-shortcode-btn" class="button">';
        echo '<span class="dashicons dashicons-media-document" style="margin-top: 2px;"></span> ';
        echo __('Reverse2PDF', 'reverse2pdf');
        echo '</button>';
    }
    
    public function handle_downloads() {
        if (isset($_GET['reverse2pdf_download']) && $_GET['reverse2pdf_download'] && wp_verify_nonce($_GET['_wpnonce'], 'reverse2pdf_download')) {
            $template_id = intval($_GET['template_id']);
            $dataset_id = intval($_GET['dataset_id'] ?? 0);
            
            if ($template_id) {
                $generator = new Reverse2PDF_Enhanced_Generator();
                $pdf_path = $generator->generate_pdf($template_id, $dataset_id);
                
                if ($pdf_path && file_exists($pdf_path)) {
                    $filename = 'document_' . $template_id . '_' . time() . '.pdf';
                    
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Content-Length: ' . filesize($pdf_path));
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');
                    
                    readfile($pdf_path);
                    
                    // Clean up temporary file
                    if (strpos($pdf_path, '/tmp/') !== false || strpos($pdf_path, '/cache/') !== false) {
                        unlink($pdf_path);
                    }
                    
                    exit;
                }
            }
        }
    }
    
    // AJAX Handlers
    public function ajax_save_template() {
        check_ajax_referer('reverse2pdf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        $template_name = sanitize_text_field($_POST['template_name'] ?? '');
        $template_data = wp_unslash($_POST['template_data'] ?? '');
        $template_settings = wp_unslash($_POST['template_settings'] ?? '{}');
        
        if (empty($template_name)) {
            wp_send_json_error('Template name is required');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;
        
        $data = array(
            'name' => $template_name,
            'template_data' => $template_data,
            'settings' => $template_settings,
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
            wp_send_json_success(array(
                'template_id' => $template_id,
                'message' => 'Template saved successfully'
            ));
        } else {
            wp_send_json_error('Failed to save template');
        }
    }
    
    public function ajax_load_template() {
        check_ajax_referer('reverse2pdf_nonce', 'nonce');
        
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
        check_ajax_referer('reverse2pdf_nonce', 'nonce');
        
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
        
        if ($result) {
            wp_send_json_success('Template deleted successfully');
        } else {
            wp_send_json_error('Failed to delete template');
        }
    }
    
    public function ajax_duplicate_template() {
        check_ajax_referer('reverse2pdf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if (!$template_id) {
            wp_send_json_error('Template ID required');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;
        
        $original = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $template_id));
        
        if (!$original) {
            wp_send_json_error('Template not found');
        }
        
        $data = array(
            'name' => $original->name . ' (Copy)',
            'description' => $original->description,
            'template_data' => $original->template_data,
            'settings' => $original->settings,
            'created_by' => get_current_user_id(),
            'created_date' => current_time('mysql'),
            'modified_date' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            wp_send_json_success(array(
                'new_id' => $wpdb->insert_id,
                'message' => 'Template duplicated successfully'
            ));
        } else {
            wp_send_json_error('Failed to duplicate template');
        }
    }
    
    public function ajax_auto_mapper() {
        check_ajax_referer('reverse2pdf_nonce', 'nonce');
        
        $data_source = sanitize_text_field($_POST['data_source'] ?? '');
        $form_type = sanitize_text_field($_POST['form_type'] ?? '');
        $form_id = sanitize_text_field($_POST['form_id'] ?? '');
        
        $fields = array();
        
        switch ($data_source) {
            case 'post':
                $fields = $this->get_post_fields();
                break;
            case 'user':
                $fields = $this->get_user_fields();
                break;
            case 'form':
                if ($form_type && $form_id) {
                    $fields = $this->get_form_fields($form_type, $form_id);
                }
                break;
            case 'woocommerce':
                $fields = $this->get_woocommerce_fields();
                break;
        }
        
        $template_data = $this->generate_auto_template($fields, $_POST);
        
        wp_send_json_success(array(
            'fields' => $fields,
            'template_data' => $template_data
        ));
    }
    
    private function get_post_fields() {
        return array(
            array('name' => 'post_title', 'label' => 'Post Title', 'type' => 'text'),
            array('name' => 'post_content', 'label' => 'Post Content', 'type' => 'textarea'),
            array('name' => 'post_excerpt', 'label' => 'Post Excerpt', 'type' => 'textarea'),
            array('name' => 'post_date', 'label' => 'Post Date', 'type' => 'date'),
            array('name' => 'post_author', 'label' => 'Post Author', 'type' => 'text'),
            array('name' => 'featured_image', 'label' => 'Featured Image', 'type' => 'image')
        );
    }
    
    private function get_user_fields() {
        return array(
            array('name' => 'display_name', 'label' => 'Display Name', 'type' => 'text'),
            array('name' => 'user_email', 'label' => 'Email', 'type' => 'email'),
            array('name' => 'first_name', 'label' => 'First Name', 'type' => 'text'),
            array('name' => 'last_name', 'label' => 'Last Name', 'type' => 'text'),
            array('name' => 'user_url', 'label' => 'Website', 'type' => 'url'),
            array('name' => 'description', 'label' => 'Bio', 'type' => 'textarea')
        );
    }
    
    private function get_woocommerce_fields() {
        return array(
            array('name' => 'order_id', 'label' => 'Order ID', 'type' => 'text'),
            array('name' => 'order_total', 'label' => 'Order Total', 'type' => 'currency'),
            array('name' => 'customer_email', 'label' => 'Customer Email', 'type' => 'email'),
            array('name' => 'billing_first_name', 'label' => 'Billing First Name', 'type' => 'text'),
            array('name' => 'billing_last_name', 'label' => 'Billing Last Name', 'type' => 'text'),
            array('name' => 'billing_address_1', 'label' => 'Billing Address', 'type' => 'text'),
            array('name' => 'shipping_first_name', 'label' => 'Shipping First Name', 'type' => 'text'),
            array('name' => 'shipping_last_name', 'label' => 'Shipping Last Name', 'type' => 'text')
        );
    }
    
    private function get_form_fields($form_type, $form_id) {
        $fields = array();
        
        switch ($form_type) {
            case 'contact_form_7':
                if (class_exists('WPCF7_ContactForm')) {
                    $form = WPCF7_ContactForm::get_instance($form_id);
                    if ($form) {
                        $form_fields = $form->scan_form_tags();
                        foreach ($form_fields as $field) {
                            if (isset($field['name']) && !empty($field['name'])) {
                                $fields[] = array(
                                    'name' => $field['name'],
                                    'label' => ucwords(str_replace(array('-', '_'), ' ', $field['name'])),
                                    'type' => $field['basetype'] ?? 'text'
                                );
                            }
                        }
                    }
                }
                break;
                
            case 'gravity_forms':
                if (class_exists('GFFormsModel')) {
                    $form = GFFormsModel::get_form_meta($form_id);
                    if ($form) {
                        foreach ($form['fields'] as $field) {
                            $fields[] = array(
                                'name' => 'field_' . $field->id,
                                'label' => $field->label,
                                'type' => $field->type
                            );
                        }
                    }
                }
                break;
        }
        
        return $fields;
    }
    
    private function generate_auto_template($fields, $options) {
        $elements = array();
        $y = 50;
        $label_width = 150;
        $field_width = 200;
        
        foreach ($fields as $field) {
            $element_id = 'auto_' . $field['name'] . '_' . uniqid();
            
            // Add label
            $elements[] = array(
                'id' => $element_id . '_label',
                'type' => 'text',
                'x' => 50,
                'y' => $y,
                'width' => $label_width,
                'height' => 25,
                'content' => $field['label'] . ':',
                'fontSize' => 12,
                'fontWeight' => 'bold'
            );
            
            // Add field
            $elements[] = array(
                'id' => $element_id,
                'type' => 'text',
                'x' => 50 + $label_width + 10,
                'y' => $y,
                'width' => $field_width,
                'height' => $field['type'] === 'textarea' ? 60 : 25,
                'content' => '{' . $field['name'] . '}',
                'fontSize' => 11
            );
            
            $y += $field['type'] === 'textarea' ? 80 : 40;
        }
        
        return array(
            'pages' => array(
                array(
                    'width' => 595,
                    'height' => 842,
                    'elements' => $elements
                )
            )
        );
    }
    
    // Settings field callbacks
    public function general_settings_callback() {
        echo '<p>' . __('General plugin settings', 'reverse2pdf') . '</p>';
    }
    
    public function pdf_settings_callback() {
        echo '<p>' . __('PDF generation settings', 'reverse2pdf') . '</p>';
    }
    
    public function performance_settings_callback() {
        echo '<p>' . __('Performance and caching settings', 'reverse2pdf') . '</p>';
    }
    
    public function pdf_engine_field() {
        $settings = get_option('reverse2pdf_settings', array());
        $value = $settings['pdf_engine'] ?? 'dompdf';
        
        echo '<select name="reverse2pdf_settings[pdf_engine]">';
        echo '<option value="dompdf"' . selected($value, 'dompdf', false) . '>DomPDF</option>';
        echo '<option value="tcpdf"' . selected($value, 'tcpdf', false) . '>TCPDF</option>';
        echo '<option value="mpdf"' . selected($value, 'mpdf', false) . '>mPDF</option>';
        echo '</select>';
        echo '<p class="description">' . __('Choose the PDF generation engine', 'reverse2pdf') . '</p>';
    }
    
    public function paper_size_field() {
        $settings = get_option('reverse2pdf_settings', array());
        $value = $settings['paper_size'] ?? 'A4';
        
        echo '<select name="reverse2pdf_settings[paper_size]">';
        echo '<option value="A4"' . selected($value, 'A4', false) . '>A4</option>';
        echo '<option value="A3"' . selected($value, 'A3', false) . '>A3</option>';
        echo '<option value="Letter"' . selected($value, 'Letter', false) . '>Letter</option>';
        echo '<option value="Legal"' . selected($value, 'Legal', false) . '>Legal</option>';
        echo '</select>';
    }
    
    public function default_font_field() {
        $settings = get_option('reverse2pdf_settings', array());
        $value = $settings['default_font'] ?? 'Arial';
        
        echo '<input type="text" name="reverse2pdf_settings[default_font]" value="' . esc_attr($value) . '" />';
    }
    
    public function enable_cache_field() {
        $settings = get_option('reverse2pdf_settings', array());
        $value = $settings['enable_cache'] ?? true;
        
        echo '<input type="checkbox" name="reverse2pdf_settings[enable_cache]" value="1"' . checked($value, 1, false) . ' />';
        echo ' <label>' . __('Enable PDF caching for better performance', 'reverse2pdf') . '</label>';
    }
    
    public function enable_debug_field() {
        $settings = get_option('reverse2pdf_settings', array());
        $value = $settings['enable_debug'] ?? false;
        
        echo '<input type="checkbox" name="reverse2pdf_settings[enable_debug]" value="1"' . checked($value, 1, false) . ' />';
        echo ' <label>' . __('Enable debug logging', 'reverse2pdf') . '</label>';
    }
    
    public function sanitize_settings($input) {
        $output = array();
        
        if (isset($input['pdf_engine'])) {
            $output['pdf_engine'] = sanitize_text_field($input['pdf_engine']);
        }
        
        if (isset($input['paper_size'])) {
            $output['paper_size'] = sanitize_text_field($input['paper_size']);
        }
        
        if (isset($input['default_font'])) {
            $output['default_font'] = sanitize_text_field($input['default_font']);
        }
        
        $output['enable_cache'] = isset($input['enable_cache']) ? 1 : 0;
        $output['enable_debug'] = isset($input['enable_debug']) ? 1 : 0;
        
        return $output;
    }
}
?>
