<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Integrations {
    
    private $supported_forms = array();
    private $active_integrations = array();
    
    public function __construct() {
        $this->init_supported_forms();
        $this->load_active_integrations();
        $this->init_hooks();
    }
    
    /**
     * Initialize supported form plugins
     */
    private function init_supported_forms() {
        $this->supported_forms = array(
            'contact_form_7' => array(
                'name' => 'Contact Form 7',
                'class' => 'WPCF7_ContactForm',
                'active' => class_exists('WPCF7_ContactForm'),
                'hook' => 'wpcf7_mail_sent',
                'priority' => 10,
                'args' => 1
            ),
            'gravity_forms' => array(
                'name' => 'Gravity Forms',
                'class' => 'GFForms',
                'active' => class_exists('GFForms'),
                'hook' => 'gform_after_submission',
                'priority' => 10,
                'args' => 2
            ),
            'wpforms' => array(
                'name' => 'WPForms',
                'class' => 'WPForms',
                'active' => function_exists('wpforms'),
                'hook' => 'wpforms_process_complete',
                'priority' => 10,
                'args' => 4
            ),
            'formidable' => array(
                'name' => 'Formidable Forms',
                'class' => 'FrmForm',
                'active' => class_exists('FrmForm'),
                'hook' => 'frm_after_create_entry',
                'priority' => 30,
                'args' => 2
            ),
            'ninja_forms' => array(
                'name' => 'Ninja Forms',
                'class' => 'Ninja_Forms',
                'active' => class_exists('Ninja_Forms'),
                'hook' => 'ninja_forms_after_submission',
                'priority' => 10,
                'args' => 1
            ),
            'fluent_forms' => array(
                'name' => 'Fluent Forms',
                'class' => 'FluentForm\Framework\Helpers\ArrayHelper',
                'active' => defined('FLUENTFORM'),
                'hook' => 'fluentform_submission_inserted',
                'priority' => 10,
                'args' => 3
            ),
            'elementor_forms' => array(
                'name' => 'Elementor Forms',
                'class' => '\ElementorPro\Modules\Forms\Module',
                'active' => class_exists('\ElementorPro\Modules\Forms\Module'),
                'hook' => 'elementor_pro/forms/new_record',
                'priority' => 10,
                'args' => 2
            ),
            'forminator' => array(
                'name' => 'Forminator',
                'class' => 'Forminator',
                'active' => class_exists('Forminator'),
                'hook' => 'forminator_custom_form_submit_before_set_fields',
                'priority' => 10,
                'args' => 3
            )
        );
        
        $this->supported_forms = apply_filters('reverse2pdf_supported_forms', $this->supported_forms);
    }
    
    /**
     * Load active integrations from database
     */
    private function load_active_integrations() {
        global $wpdb;
        
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_INTEGRATIONS;
        $integrations = $wpdb->get_results("SELECT * FROM $table WHERE active = 1");
        
        foreach ($integrations as $integration) {
            $this->active_integrations[$integration->form_type][$integration->form_id] = $integration;
        }
    }
    
    /**
     * Initialize hooks for form submissions
     */
    private function init_hooks() {
        foreach ($this->supported_forms as $form_type => $form_config) {
            if ($form_config['active']) {
                add_action(
                    $form_config['hook'],
                    array($this, 'handle_' . $form_type . '_submission'),
                    $form_config['priority'],
                    $form_config['args']
                );
            }
        }
        
        // AJAX handlers
        add_action('wp_ajax_reverse2pdf_get_forms', array($this, 'ajax_get_forms'));
        add_action('wp_ajax_reverse2pdf_get_form_fields', array($this, 'ajax_get_form_fields'));
        add_action('wp_ajax_reverse2pdf_setup_integration', array($this, 'ajax_setup_integration'));
        add_action('wp_ajax_reverse2pdf_test_integration', array($this, 'ajax_test_integration'));
        
        // Admin hooks
        add_action('admin_init', array($this, 'process_integration_settings'));
    }
    
    /**
     * Handle Contact Form 7 submission
     */
    public function handle_contact_form_7_submission($contact_form) {
        $form_id = $contact_form->id();
        
        if (!isset($this->active_integrations['contact_form_7'][$form_id])) {
            return;
        }
        
        $integration = $this->active_integrations['contact_form_7'][$form_id];
        $submission = WPCF7_Submission::get_instance();
        
        if (!$submission) {
            return;
        }
        
        $form_data = $submission->get_posted_data();
        $uploaded_files = $submission->uploaded_files();
        
        // Add uploaded files to form data
        foreach ($uploaded_files as $field_name => $file_path) {
            if (is_array($file_path)) {
                $form_data[$field_name] = array_map('wp_get_attachment_url', array_map('attachment_url_to_postid', $file_path));
            } else {
                $form_data[$field_name] = wp_get_attachment_url(attachment_url_to_postid($file_path));
            }
        }
        
        $this->process_form_submission('contact_form_7', $form_id, $form_data, $integration);
    }
    
    /**
     * Handle Gravity Forms submission
     */
    public function handle_gravity_forms_submission($entry, $form) {
        $form_id = $form['id'];
        
        if (!isset($this->active_integrations['gravity_forms'][$form_id])) {
            return;
        }
        
        $integration = $this->active_integrations['gravity_forms'][$form_id];
        $form_data = array();
        
        foreach ($form['fields'] as $field) {
            $field_id = $field->id;
            $field_value = rgar($entry, $field_id);
            
            // Handle different field types
            switch ($field->type) {
                case 'fileupload':
                    if ($field_value) {
                        $files = json_decode($field_value, true);
                        if (is_array($files)) {
                            $form_data['field_' . $field_id] = array_column($files, 'url');
                        } else {
                            $form_data['field_' . $field_id] = $field_value;
                        }
                    }
                    break;
                case 'name':
                    if (is_array($field_value)) {
                        $form_data['field_' . $field_id] = implode(' ', array_filter($field_value));
                        foreach ($field_value as $part_key => $part_value) {
                            $form_data['field_' . $field_id . '_' . $part_key] = $part_value;
                        }
                    } else {
                        $form_data['field_' . $field_id] = $field_value;
                    }
                    break;
                case 'address':
                    if (is_array($field_value)) {
                        $form_data['field_' . $field_id] = implode(', ', array_filter($field_value));
                        foreach ($field_value as $part_key => $part_value) {
                            $form_data['field_' . $field_id . '_' . $part_key] = $part_value;
                        }
                    } else {
                        $form_data['field_' . $field_id] = $field_value;
                    }
                    break;
                default:
                    $form_data['field_' . $field_id] = $field_value;
            }
            
            // Also add with field label as key
            if (!empty($field->label)) {
                $label_key = sanitize_key($field->label);
                $form_data[$label_key] = $field_value;
            }
        }
        
        // Add entry meta
        $form_data['entry_id'] = $entry['id'];
        $form_data['entry_date'] = $entry['date_created'];
        $form_data['user_ip'] = $entry['ip'];
        $form_data['user_agent'] = $entry['user_agent'];
        
        $this->process_form_submission('gravity_forms', $form_id, $form_data, $integration);
    }
    
    /**
     * Handle WPForms submission
     */
    public function handle_wpforms_submission($fields, $entry, $form_data, $entry_id) {
        $form_id = $form_data['id'];
        
        if (!isset($this->active_integrations['wpforms'][$form_id])) {
            return;
        }
        
        $integration = $this->active_integrations['wpforms'][$form_id];
        $processed_data = array();
        
        foreach ($fields as $field_id => $field) {
            $field_name = 'field_' . $field_id;
            $processed_data[$field_name] = $field['value'];
            
            // Also add with field name if available
            if (!empty($field['name'])) {
                $processed_data[$field['name']] = $field['value'];
            }
        }
        
        // Add entry meta
        $processed_data['entry_id'] = $entry_id;
        $processed_data['form_title'] = $form_data['settings']['form_title'];
        
        $this->process_form_submission('wpforms', $form_id, $processed_data, $integration);
    }
    
    /**
     * Handle Formidable Forms submission
     */
    public function handle_formidable_submission($entry_id, $form_id) {
        if (!isset($this->active_integrations['formidable'][$form_id])) {
            return;
        }
        
        $integration = $this->active_integrations['formidable'][$form_id];
        $entry = FrmEntry::getOne($entry_id);
        $form = FrmForm::getOne($form_id);
        
        if (!$entry || !$form) {
            return;
        }
        
        $form_data = array();
        $fields = FrmField::get_all_for_form($form_id);
        
        foreach ($fields as $field) {
            $field_value = FrmEntryMeta::get_entry_meta_by_field($entry_id, $field->id);
            $form_data['field_' . $field->field_key] = $field_value;
            
            if (!empty($field->name)) {
                $form_data[$field->name] = $field_value;
            }
        }
        
        // Add entry meta
        $form_data['entry_id'] = $entry_id;
        $form_data['entry_key'] = $entry->item_key;
        $form_data['form_name'] = $form->name;
        
        $this->process_form_submission('formidable', $form_id, $form_data, $integration);
    }
    
    /**
     * Handle Ninja Forms submission
     */
    public function handle_ninja_forms_submission($form_data) {
        $form_id = $form_data['form_id'];
        
        if (!isset($this->active_integrations['ninja_forms'][$form_id])) {
            return;
        }
        
        $integration = $this->active_integrations['ninja_forms'][$form_id];
        $processed_data = array();
        
        if (isset($form_data['fields'])) {
            foreach ($form_data['fields'] as $field) {
                $field_key = isset($field['key']) ? $field['key'] : 'field_' . $field['id'];
                $processed_data[$field_key] = $field['value'];
            }
        }
        
        $processed_data['form_title'] = $form_data['settings']['title'] ?? '';
        
        $this->process_form_submission('ninja_forms', $form_id, $processed_data, $integration);
    }
    
    /**
     * Handle Fluent Forms submission
     */
    public function handle_fluent_forms_submission($entryId, $formData, $form) {
        $form_id = $form->id;
        
        if (!isset($this->active_integrations['fluent_forms'][$form_id])) {
            return;
        }
        
        $integration = $this->active_integrations['fluent_forms'][$form_id];
        $processed_data = $formData;
        
        // Add entry meta
        $processed_data['entry_id'] = $entryId;
        $processed_data['form_title'] = $form->title;
        
        $this->process_form_submission('fluent_forms', $form_id, $processed_data, $integration);
    }
    
    /**
     * Handle Elementor Forms submission
     */
    public function handle_elementor_forms_submission($record, $handler) {
        $form_name = $record->get_form_settings('form_name');
        $form_id = $record->get_form_settings('form_id') ?: $form_name;
        
        if (!isset($this->active_integrations['elementor_forms'][$form_id])) {
            return;
        }
        
        $integration = $this->active_integrations['elementor_forms'][$form_id];
        $raw_fields = $record->get('fields');
        $processed_data = array();
        
        foreach ($raw_fields as $id => $field) {
            $processed_data[$id] = $field['value'];
        }
        
        $processed_data['form_name'] = $form_name;
        
        $this->process_form_submission('elementor_forms', $form_id, $processed_data, $integration);
    }
    
    /**
     * Handle Forminator submission
     */
    public function handle_forminator_submission($entry, $form_id, $field_data_array) {
        if (!isset($this->active_integrations['forminator'][$form_id])) {
            return;
        }
        
        $integration = $this->active_integrations['forminator'][$form_id];
        $processed_data = array();
        
        foreach ($field_data_array as $field) {
            if (isset($field['name']) && isset($field['value'])) {
                $processed_data[$field['name']] = $field['value'];
            }
        }
        
        $this->process_form_submission('forminator', $form_id, $processed_data, $integration);
    }
    
    /**
     * Process form submission and generate PDF
     */
    private function process_form_submission($form_type, $form_id, $form_data, $integration) {
        try {
            // Log the submission
            $logger = new Reverse2PDF_Logger();
            $logger->log('info', 'Processing form submission', array(
                'form_type' => $form_type,
                'form_id' => $form_id,
                'template_id' => $integration->template_id,
                'integration_id' => $integration->id
            ));
            
            // Check conditions if set
            if (!empty($integration->conditions)) {
                $conditions = json_decode($integration->conditions, true);
                if ($conditions && !$this->evaluate_integration_conditions($conditions, $form_data)) {
                    $logger->log('info', 'Form submission skipped due to conditions', array(
                        'integration_id' => $integration->id
                    ));
                    return;
                }
            }
            
            // Get integration settings
            $settings = json_decode($integration->settings ?: '{}', true);
            
            // Prepare dataset
            $dataset = array_merge($form_data, array(
                'form_type' => $form_type,
                'form_id' => $form_id,
                'submission_date' => current_time('mysql'),
                'submission_time' => current_time('H:i:s'),
                'user_ip' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? ''
            ));
            
            // Generate PDF
            $generator = new Reverse2PDF_Enhanced_Generator();
            $pdf_url = $generator->generate_pdf($integration->template_id, 0, array(
                'form_data' => $dataset,
                'save_to_media' => $settings['save_to_media'] ?? false
            ));
            
            if ($pdf_url) {
                // Handle PDF based on settings
                $this->handle_generated_pdf($pdf_url, $form_data, $settings, $integration);
                
                $logger->log('success', 'PDF generated successfully for form submission', array(
                    'template_id' => $integration->template_id,
                    'pdf_url' => $pdf_url
                ));
            } else {
                throw new Exception('PDF generation failed');
            }
            
        } catch (Exception $e) {
            $logger->log('error', 'Form submission processing failed: ' . $e->getMessage(), array(
                'form_type' => $form_type,
                'form_id' => $form_id,
                'template_id' => $integration->template_id,
                'error' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Handle generated PDF based on integration settings
     */
    private function handle_generated_pdf($pdf_url, $form_data, $settings, $integration) {
        // Email attachment
        if ($settings['email_attachment'] ?? false) {
            $this->send_pdf_email($pdf_url, $form_data, $settings);
        }
        
        // Save to media library
        if ($settings['save_to_media'] ?? false) {
            $this->save_pdf_to_media($pdf_url, $form_data, $integration);
        }
        
        // Redirect with PDF
        if ($settings['redirect_with_pdf'] ?? false) {
            $this->setup_pdf_redirect($pdf_url, $settings);
        }
        
        // Webhook notification
        if ($settings['webhook_url'] ?? false) {
            $this->send_webhook_notification($settings['webhook_url'], $pdf_url, $form_data);
        }
        
        // Store in session for download
        if ($settings['enable_download'] ?? true) {
            $this->store_pdf_for_download($pdf_url, $form_data);
        }
    }
    
    /**
     * Send PDF as email attachment
     */
    private function send_pdf_email($pdf_url, $form_data, $settings) {
        $to = $settings['email_to'] ?? ($form_data['your-email'] ?? $form_data['email'] ?? '');
        
        if (empty($to)) {
            return false;
        }
        
        $subject = $settings['email_subject'] ?? __('Your PDF Document', 'reverse2pdf');
        $message = $settings['email_message'] ?? __('Please find your PDF document attached.', 'reverse2pdf');
        
        // Replace placeholders in subject and message
        foreach ($form_data as $key => $value) {
            if (is_scalar($value)) {
                $subject = str_replace('{' . $key . '}', $value, $subject);
                $message = str_replace('{' . $key . '}', $value, $message);
            }
        }
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Add CC and BCC if specified
        if (!empty($settings['email_cc'])) {
            $headers[] = 'Cc: ' . $settings['email_cc'];
        }
        if (!empty($settings['email_bcc'])) {
            $headers[] = 'Bcc: ' . $settings['email_bcc'];
        }
        
        // Get PDF file path
        $pdf_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $pdf_url);
        
        $attachments = array();
        if (file_exists($pdf_path)) {
            $attachments[] = $pdf_path;
        }
        
        return wp_mail($to, $subject, $message, $headers, $attachments);
    }
    
    /**
     * Save PDF to media library
     */
    private function save_pdf_to_media($pdf_url, $form_data, $integration) {
        $pdf_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $pdf_url);
        
        if (!file_exists($pdf_path)) {
            return false;
        }
        
        $filename = basename($pdf_path);
        $upload_dir = wp_upload_dir();
        $new_path = $upload_dir['path'] . '/' . $filename;
        
        // Copy to uploads directory
        copy($pdf_path, $new_path);
        
        // Create attachment
        $attachment = array(
            'guid' => $upload_dir['url'] . '/' . basename($new_path),
            'post_mime_type' => 'application/pdf',
            'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $new_path);
        
        if ($attachment_id) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $new_path);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            
            return $attachment_id;
        }
        
        return false;
    }
    
    /**
     * Setup PDF redirect
     */
    private function setup_pdf_redirect($pdf_url, $settings) {
        $redirect_url = $settings['redirect_url'] ?? '';
        
        if ($redirect_url) {
            $redirect_url = add_query_arg('pdf', urlencode($pdf_url), $redirect_url);
        } else {
            $redirect_url = $pdf_url;
        }
        
        // Store redirect URL in session/transient for later use
        set_transient('reverse2pdf_redirect_' . session_id(), $redirect_url, 300);
    }
    
    /**
     * Send webhook notification
     */
    private function send_webhook_notification($webhook_url, $pdf_url, $form_data) {
        $payload = array(
            'pdf_url' => $pdf_url,
            'form_data' => $form_data,
            'timestamp' => current_time('timestamp'),
            'site_url' => home_url()
        );
        
        wp_remote_post($webhook_url, array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'Reverse2PDF/' . REVERSE2PDF_VERSION
            ),
            'body' => json_encode($payload)
        ));
    }
    
    /**
     * Store PDF for download
     */
    private function store_pdf_for_download($pdf_url, $form_data) {
        $download_data = array(
            'pdf_url' => $pdf_url,
            'form_data' => $form_data,
            'created' => time()
        );
        
        set_transient('reverse2pdf_download_' . session_id(), $download_data, 3600);
    }
    
    /**
     * Evaluate integration conditions
     */
    private function evaluate_integration_conditions($conditions, $form_data) {
        $conditional_logic = new Reverse2PDF_Conditional_Logic();
        return $conditional_logic->evaluate_conditions($conditions, $form_data);
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
    
    // AJAX Handlers
    
    /**
     * AJAX: Get forms for specific form type
     */
    public function ajax_get_forms() {
        check_ajax_referer('reverse2pdf_nonce', 'nonce');
        
        $form_type = sanitize_text_field($_POST['form_type'] ?? '');
        
        if (empty($form_type) || !isset($this->supported_forms[$form_type])) {
            wp_send_json_error('Invalid form type');
        }
        
        $forms = $this->get_forms_by_type($form_type);
        wp_send_json_success($forms);
    }
    
    /**
     * AJAX: Get form fields
     */
    public function ajax_get_form_fields() {
        check_ajax_referer('reverse2pdf_nonce', 'nonce');
        
        $form_type = sanitize_text_field($_POST['form_type'] ?? '');
        $form_id = sanitize_text_field($_POST['form_id'] ?? '');
        
        if (empty($form_type) || empty($form_id)) {
            wp_send_json_error('Form type and ID required');
        }
        
        $fields = $this->get_form_fields($form_type, $form_id);
        wp_send_json_success($fields);
    }
    
    /**
     * AJAX: Setup integration
     */
    public function ajax_setup_integration() {
        check_ajax_referer('reverse2pdf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $form_type = sanitize_text_field($_POST['form_type'] ?? '');
        $form_id = sanitize_text_field($_POST['form_id'] ?? '');
        $template_id = intval($_POST['template_id'] ?? 0);
        $settings = wp_unslash($_POST['settings'] ?? '{}');
        $conditions = wp_unslash($_POST['conditions'] ?? '');
        
        if (empty($form_type) || empty($form_id) || !$template_id) {
            wp_send_json_error('Required fields missing');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_INTEGRATIONS;
        
        $data = array(
            'form_type' => $form_type,
            'form_id' => $form_id,
            'template_id' => $template_id,
            'settings' => $settings,
            'conditions' => $conditions,
            'active' => 1,
            'created_date' => current_time('mysql')
        );
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE form_type = %s AND form_id = %s AND template_id = %d",
            $form_type, $form_id, $template_id
        ));
        
        if ($existing) {
            $result = $wpdb->update($table, $data, array('id' => $existing->id));
            $integration_id = $existing->id;
        } else {
            $result = $wpdb->insert($table, $data);
            $integration_id = $wpdb->insert_id;
        }
        
        if ($result !== false) {
            // Reload active integrations
            $this->load_active_integrations();
            
            wp_send_json_success(array(
                'integration_id' => $integration_id,
                'message' => 'Integration setup successfully'
            ));
        } else {
            wp_send_json_error('Failed to setup integration');
        }
    }
    
    /**
     * Get forms by type
     */
    public function get_forms_by_type($form_type) {
        $forms = array();
        
        switch ($form_type) {
            case 'contact_form_7':
                if (class_exists('WPCF7_ContactForm')) {
                    $cf7_forms = WPCF7_ContactForm::find();
                    foreach ($cf7_forms as $form) {
                        $forms[] = array(
                            'id' => $form->id(),
                            'title' => $form->title(),
                            'active' => true
                        );
                    }
                }
                break;
                
            case 'gravity_forms':
                if (class_exists('GFFormsModel')) {
                    $gf_forms = GFFormsModel::get_forms(true);
                    foreach ($gf_forms as $form) {
                        $forms[] = array(
                            'id' => $form->id,
                            'title' => $form->title,
                            'active' => $form->is_active
                        );
                    }
                }
                break;
                
            case 'wpforms':
                if (function_exists('wpforms')) {
                    $wpf_forms = wpforms()->form->get();
                    foreach ($wpf_forms as $form) {
                        $forms[] = array(
                            'id' => $form->ID,
                            'title' => $form->post_title,
                            'active' => $form->post_status === 'publish'
                        );
                    }
                }
                break;
                
            case 'formidable':
                if (class_exists('FrmForm')) {
                    $frm_forms = FrmForm::get_published_forms();
                    foreach ($frm_forms as $form) {
                        $forms[] = array(
                            'id' => $form->id,
                            'title' => $form->name,
                            'active' => $form->status === 'published'
                        );
                    }
                }
                break;
                
            case 'ninja_forms':
                if (class_exists('Ninja_Forms')) {
                    $nf_forms = Ninja_Forms()->form()->get_forms();
                    foreach ($nf_forms as $form) {
                        $forms[] = array(
                            'id' => $form->get_id(),
                            'title' => $form->get_setting('title'),
                            'active' => $form->get_setting('status') === 'publish'
                        );
                    }
                }
                break;
                
            case 'fluent_forms':
                if (defined('FLUENTFORM')) {
                    global $wpdb;
                    $ff_forms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fluentform_forms WHERE status = 'published'");
                    foreach ($ff_forms as $form) {
                        $forms[] = array(
                            'id' => $form->id,
                            'title' => $form->title,
                            'active' => true
                        );
                    }
                }
                break;
        }
        
        return $forms;
    }
    
    /**
     * Get form fields
     */
    public function get_form_fields($form_type, $form_id) {
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
                    if ($form && isset($form['fields'])) {
                        foreach ($form['fields'] as $field) {
                            $field_name = 'field_' . $field->id;
                            $fields[] = array(
                                'name' => $field_name,
                                'label' => $field->label ?: $field_name,
                                'type' => $field->type
                            );
                            
                            // Add sub-fields for complex fields
                            if (in_array($field->type, array('name', 'address'))) {
                                foreach ($field->inputs as $input) {
                                    if (isset($input['label'])) {
                                        $fields[] = array(
                                            'name' => 'field_' . $input['id'],
                                            'label' => $input['label'],
                                            'type' => 'text'
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
                break;
                
            case 'wpforms':
                if (function_exists('wpforms')) {
                    $form = wpforms()->form->get($form_id);
                    if ($form) {
                        $form_data = json_decode($form->post_content, true);
                        if (isset($form_data['fields'])) {
                            foreach ($form_data['fields'] as $field_id => $field) {
                                $fields[] = array(
                                    'name' => 'field_' . $field_id,
                                    'label' => $field['label'] ?? 'Field ' . $field_id,
                                    'type' => $field['type'] ?? 'text'
                                );
                            }
                        }
                    }
                }
                break;
                
            case 'formidable':
                if (class_exists('FrmField')) {
                    $frm_fields = FrmField::get_all_for_form($form_id);
                    foreach ($frm_fields as $field) {
                        $fields[] = array(
                            'name' => 'field_' . $field->field_key,
                            'label' => $field->name ?: $field->field_key,
                            'type' => $field->type
                        );
                    }
                }
                break;
        }
        
        return $fields;
    }
    
    /**
     * Get supported form plugins
     */
    public function get_supported_forms() {
        return $this->supported_forms;
    }
    
    /**
     * Get active integrations
     */
    public function get_active_integrations() {
        return $this->active_integrations;
    }
    
    /**
     * Process integration settings from admin form
     */
    public function process_integration_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['reverse2pdf_integration_submit']) && wp_verify_nonce($_POST['reverse2pdf_integration_nonce'], 'save_integration')) {
            $form_type = sanitize_text_field($_POST['form_type']);
            $form_id = sanitize_text_field($_POST['form_id']);
            $template_id = intval($_POST['template_id']);
            
            if ($form_type && $form_id && $template_id) {
                $settings = array(
                    'email_attachment' => isset($_POST['email_attachment']),
                    'email_to' => sanitize_email($_POST['email_to'] ?? ''),
                    'email_subject' => sanitize_text_field($_POST['email_subject'] ?? ''),
                    'email_message' => wp_kses_post($_POST['email_message'] ?? ''),
                    'save_to_media' => isset($_POST['save_to_media']),
                    'redirect_with_pdf' => isset($_POST['redirect_with_pdf']),
                    'redirect_url' => esc_url_raw($_POST['redirect_url'] ?? ''),
                    'webhook_url' => esc_url_raw($_POST['webhook_url'] ?? ''),
                    'enable_download' => isset($_POST['enable_download'])
                );
                
                global $wpdb;
                $table = $wpdb->prefix . REVERSE2PDF_TABLE_INTEGRATIONS;
                
                $data = array(
                    'form_type' => $form_type,
                    'form_id' => $form_id,
                    'template_id' => $template_id,
                    'settings' => json_encode($settings),
                    'active' => 1,
                    'created_date' => current_time('mysql')
                );
                
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $table WHERE form_type = %s AND form_id = %s AND template_id = %d",
                    $form_type, $form_id, $template_id
                ));
                
                if ($existing) {
                    $wpdb->update($table, $data, array('id' => $existing->id));
                } else {
                    $wpdb->insert($table, $data);
                }
                
                wp_redirect(add_query_arg('message', 'integration_saved', wp_get_referer()));
                exit;
            }
        }
    }
}
?>
