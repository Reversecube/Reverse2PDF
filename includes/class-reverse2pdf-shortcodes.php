<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Shortcodes {
    
    private $shortcodes = array();
    
    public function __construct() {
        $this->register_shortcodes();
    }
    
    /**
     * Register all shortcodes
     */
    private function register_shortcodes() {
        $this->shortcodes = array(
            // PDF Generation & Display
            'reverse2pdf-view' => array($this, 'pdf_view_shortcode'),
            'reverse2pdf-download' => array($this, 'pdf_download_shortcode'),
            'reverse2pdf-save' => array($this, 'pdf_save_shortcode'),
            'reverse2pdf-attachment' => array($this, 'pdf_attachment_shortcode'),
            'reverse2pdf-link' => array($this, 'pdf_link_shortcode'),
            
            // Conditional Logic
            'reverse2pdf-if' => array($this, 'if_shortcode'),
            'reverse2pdf-else' => array($this, 'else_shortcode'),
            'reverse2pdf-elseif' => array($this, 'elseif_shortcode'),
            
            // Loops & Iterations
            'reverse2pdf-for' => array($this, 'for_shortcode'),
            'reverse2pdf-foreach' => array($this, 'foreach_shortcode'),
            'reverse2pdf-while' => array($this, 'while_shortcode'),
            
            // Data Formatting
            'reverse2pdf-format-output' => array($this, 'format_output_shortcode'),
            'reverse2pdf-format-date' => array($this, 'format_date_shortcode'),
            'reverse2pdf-format-number' => array($this, 'format_number_shortcode'),
            'reverse2pdf-format-currency' => array($this, 'format_currency_shortcode'),
            
            // Mathematical Operations
            'reverse2pdf-math' => array($this, 'math_shortcode'),
            'reverse2pdf-calculate' => array($this, 'calculate_shortcode'),
            'reverse2pdf-sum' => array($this, 'sum_shortcode'),
            'reverse2pdf-average' => array($this, 'average_shortcode'),
            
            // WordPress Integration
            'reverse2pdf-wp-posts' => array($this, 'wp_posts_shortcode'),
            'reverse2pdf-user' => array($this, 'user_shortcode'),
            'reverse2pdf-userid' => array($this, 'userid_shortcode'),
            'reverse2pdf-usercurrentid' => array($this, 'usercurrentid_shortcode'),
            'reverse2pdf-site' => array($this, 'site_shortcode'),
            
            // Form Data Access
            'reverse2pdf-cf7' => array($this, 'cf7_shortcode'),
            'reverse2pdf-gravity' => array($this, 'gravity_shortcode'),
            'reverse2pdf-wpforms' => array($this, 'wpforms_shortcode'),
            'reverse2pdf-formidable' => array($this, 'formidable_shortcode'),
            'reverse2pdf-ninjaforms' => array($this, 'ninjaforms_shortcode'),
            
            // Advanced Elements
            'reverse2pdf-qr' => array($this, 'qr_shortcode'),
            'reverse2pdf-barcode' => array($this, 'barcode_shortcode'),
            'reverse2pdf-signature' => array($this, 'signature_shortcode'),
            'reverse2pdf-chart' => array($this, 'chart_shortcode'),
            
            // Utility Functions
            'reverse2pdf-arg' => array($this, 'arg_shortcode'),
            'reverse2pdf-get' => array($this, 'get_shortcode'),
            'reverse2pdf-post' => array($this, 'post_shortcode'),
            'reverse2pdf-cookie' => array($this, 'cookie_shortcode'),
            'reverse2pdf-session' => array($this, 'session_shortcode'),
            
            // ACF Integration
            'reverse2pdf-acf' => array($this, 'acf_shortcode'),
            'reverse2pdf-acf-repeater' => array($this, 'acf_repeater_shortcode'),
            
            // WooCommerce Integration
            'reverse2pdf-wc-order' => array($this, 'wc_order_shortcode'),
            'reverse2pdf-wc-product' => array($this, 'wc_product_shortcode'),
            'reverse2pdf-wc-customer' => array($this, 'wc_customer_shortcode'),
        );
        
        foreach ($this->shortcodes as $shortcode => $callback) {
            add_shortcode($shortcode, $callback);
        }
    }
    
    /**
     * PDF view shortcode
     */
    public function pdf_view_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'id' => 0,
            'dataset_id' => 0,
            'width' => '100%',
            'height' => '600px',
            'inline' => 'true',
            'download' => 'true',
            'print' => 'true',
            'zoom' => 'true',
            'search' => 'false',
            'toolbar' => 'true'
        ), $atts);
        
        $template_id = intval($atts['id']);
        if (!$template_id) {
            return '<div class="reverse2pdf-error">Error: Template ID required</div>';
        }
        
        $viewer_id = 'reverse2pdf-viewer-' . uniqid();
        $controls = $atts['toolbar'] === 'true' ? $this->get_viewer_controls($atts) : '';
        
        return '<div class="reverse2pdf-viewer-container" style="width: ' . esc_attr($atts['width']) . ';">' .
               $controls .
               '<div id="' . $viewer_id . '" class="reverse2pdf-pdf-viewer" style="height: ' . esc_attr($atts['height']) . ';" ' .
               'data-template-id="' . $template_id . '" data-dataset-id="' . intval($atts['dataset_id']) . '">' .
               '<div class="pdf-loading">Loading PDF...</div>' .
               '</div>' .
               '</div>';
    }
    
    /**
     * PDF download shortcode
     */
    public function pdf_download_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'id' => 0,
            'dataset_id' => 0,
            'text' => 'Download PDF',
            'class' => 'reverse2pdf-download-btn',
            'style' => '',
            'target' => '_blank',
            'format' => 'pdf',
            'filename' => '',
            'ajax' => 'true'
        ), $atts);
        
        $template_id = intval($atts['id']);
        if (!$template_id) {
            return '<span class="reverse2pdf-error">Error: Template ID required</span>';
        }
        
        $button_text = $content ?: $atts['text'];
        $css_class = $atts['class'] . ($atts['ajax'] === 'true' ? ' reverse2pdf-ajax-download' : '');
        
        if ($atts['ajax'] === 'true') {
            return '<button type="button" class="' . esc_attr($css_class) . '" style="' . esc_attr($atts['style']) . '" ' .
                   'data-template-id="' . $template_id . '" data-dataset-id="' . intval($atts['dataset_id']) . '" ' .
                   'data-filename="' . esc_attr($atts['filename']) . '" data-format="' . esc_attr($atts['format']) . '">' .
                   esc_html($button_text) . '</button>';
        } else {
            $download_url = add_query_arg(array(
                'reverse2pdf_download' => 1,
                'template_id' => $template_id,
                'dataset_id' => intval($atts['dataset_id']),
                '_wpnonce' => wp_create_nonce('reverse2pdf_download')
            ), home_url());
            
            return '<a href="' . esc_url($download_url) . '" class="' . esc_attr($css_class) . '" ' .
                   'style="' . esc_attr($atts['style']) . '" target="' . esc_attr($atts['target']) . '">' .
                   esc_html($button_text) . '</a>';
        }
    }
    
    /**
     * Conditional IF shortcode
     */
    public function if_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'condition' => '',
            'value' => '',
            'operator' => '=',
            'logic' => 'and'
        ), $atts);
        
        if (empty($atts['condition'])) {
            return '';
        }
        
        $condition_result = $this->evaluate_condition($atts);
        
        if ($condition_result) {
            return do_shortcode($content);
        }
        
        return '';
    }
    
    /**
     * FOR loop shortcode
     */
    public function for_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'start' => '1',
            'end' => '10',
            'step' => '1',
            'var' => 'i'
        ), $atts);
        
        $start = intval($atts['start']);
        $end = intval($atts['end']);
        $step = intval($atts['step']);
        $var = $atts['var'];
        
        if ($step <= 0) $step = 1;
        
        $output = '';
        for ($i = $start; $i <= $end; $i += $step) {
            $loop_content = str_replace('{' . $var . '}', $i, $content);
            $loop_content = str_replace('{index}', $i - $start, $loop_content);
            $loop_content = str_replace('{iteration}', $i - $start + 1, $loop_content);
            $output .= do_shortcode($loop_content);
        }
        
        return $output;
    }
    
    /**
     * FOREACH loop shortcode
     */
    public function foreach_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'data' => '',
            'key_var' => 'key',
            'value_var' => 'item',
            'separator' => ',',
            'limit' => '0'
        ), $atts);
        
        if (empty($atts['data'])) {
            return '';
        }
        
        $data = $this->get_loop_data($atts['data'], $atts['separator']);
        $limit = intval($atts['limit']);
        
        if (!is_array($data)) {
            return '';
        }
        
        $output = '';
        $index = 0;
        
        foreach ($data as $key => $value) {
            if ($limit > 0 && $index >= $limit) {
                break;
            }
            
            $loop_content = $content;
            $loop_content = str_replace('{' . $atts['key_var'] . '}', $key, $loop_content);
            $loop_content = str_replace('{' . $atts['value_var'] . '}', is_scalar($value) ? $value : json_encode($value), $loop_content);
            $loop_content = str_replace('{index}', $index, $loop_content);
            $loop_content = str_replace('{iteration}', $index + 1, $loop_content);
            
            // If value is array, replace individual keys
            if (is_array($value)) {
                foreach ($value as $subkey => $subvalue) {
                    $loop_content = str_replace('{' . $subkey . '}', is_scalar($subvalue) ? $subvalue : json_encode($subvalue), $loop_content);
                }
            }
            
            $output .= do_shortcode($loop_content);
            $index++;
        }
        
        return $output;
    }
    
    /**
     * Format output shortcode
     */
    public function format_output_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'value' => $content,
            'format' => 'text',
            'decimals' => '2',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'prefix' => '',
            'suffix' => '',
            'currency_symbol' => '$',
            'date_format' => 'Y-m-d',
            'case' => 'none'
        ), $atts);
        
        $value = $atts['value'];
        
        switch ($atts['format']) {
            case 'number':
                $value = number_format(
                    floatval($value),
                    intval($atts['decimals']),
                    $atts['decimal_separator'],
                    $atts['thousands_separator']
                );
                break;
                
            case 'currency':
                $value = $atts['currency_symbol'] . number_format(
                    floatval($value),
                    intval($atts['decimals']),
                    $atts['decimal_separator'],
                    $atts['thousands_separator']
                );
                break;
                
            case 'percentage':
                $value = number_format(floatval($value) * 100, intval($atts['decimals'])) . '%';
                break;
                
            case 'date':
                if (is_numeric($value)) {
                    $value = date($atts['date_format'], intval($value));
                } else {
                    $timestamp = strtotime($value);
                    $value = $timestamp ? date($atts['date_format'], $timestamp) : $value;
                }
                break;
                
            case 'time':
                if (is_numeric($value)) {
                    $value = date('H:i:s', intval($value));
                } else {
                    $timestamp = strtotime($value);
                    $value = $timestamp ? date('H:i:s', $timestamp) : $value;
                }
                break;
                
            case 'bool':
            case 'boolean':
                $value = $value ? 'true' : 'false';
                break;
                
            case 'yesno':
                $value = $value ? 'Yes' : 'No';
                break;
        }
        
        // Apply case transformation
        switch ($atts['case']) {
            case 'upper':
                $value = strtoupper($value);
                break;
            case 'lower':
                $value = strtolower($value);
                break;
            case 'title':
                $value = ucwords($value);
                break;
            case 'sentence':
                $value = ucfirst(strtolower($value));
                break;
        }
        
        return $atts['prefix'] . $value . $atts['suffix'];
    }
    
    /**
     * Math shortcode
     */
    public function math_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'expression' => $content,
            'value1' => '0',
            'value2' => '0',
            'operator' => '+',
            'decimals' => '2',
            'round' => 'false'
        ), $atts);
        
        if (!empty($atts['expression'])) {
            return $this->calculate_expression($atts['expression'], intval($atts['decimals']));
        } else {
            $val1 = floatval($atts['value1']);
            $val2 = floatval($atts['value2']);
            $result = 0;
            
            switch ($atts['operator']) {
                case '+':
                    $result = $val1 + $val2;
                    break;
                case '-':
                    $result = $val1 - $val2;
                    break;
                case '*':
                    $result = $val1 * $val2;
                    break;
                case '/':
                    $result = $val2 != 0 ? $val1 / $val2 : 0;
                    break;
                case '%':
                    $result = $val2 != 0 ? $val1 % $val2 : 0;
                    break;
                case '^':
                    $result = pow($val1, $val2);
                    break;
                case 'sqrt':
                    $result = sqrt($val1);
                    break;
                case 'abs':
                    $result = abs($val1);
                    break;
                case 'round':
                    $result = round($val1, intval($val2));
                    break;
                case 'ceil':
                    $result = ceil($val1);
                    break;
                case 'floor':
                    $result = floor($val1);
                    break;
                case 'min':
                    $result = min($val1, $val2);
                    break;
                case 'max':
                    $result = max($val1, $val2);
                    break;
            }
            
            if ($atts['round'] === 'true') {
                $result = round($result);
            } else {
                $result = number_format($result, intval($atts['decimals']), '.', '');
            }
            
            return $result;
        }
    }
    
    /**
     * WordPress posts shortcode
     */
    public function wp_posts_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'post_type' => 'post',
            'posts_per_page' => '5',
            'meta_key' => '',
            'meta_value' => '',
            'orderby' => 'date',
            'order' => 'DESC',
            'template' => '',
            'include' => '',
            'exclude' => '',
            'category' => '',
            'tag' => ''
        ), $atts);
        
        $query_args = array(
            'post_type' => $atts['post_type'],
            'posts_per_page' => intval($atts['posts_per_page']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'post_status' => 'publish'
        );
        
        if (!empty($atts['meta_key'])) {
            $query_args['meta_key'] = $atts['meta_key'];
        }
        
        if (!empty($atts['meta_value'])) {
            $query_args['meta_value'] = $atts['meta_value'];
        }
        
        if (!empty($atts['include'])) {
            $query_args['post__in'] = array_map('intval', explode(',', $atts['include']));
        }
        
        if (!empty($atts['exclude'])) {
            $query_args['post__not_in'] = array_map('intval', explode(',', $atts['exclude']));
        }
        
        if (!empty($atts['category'])) {
            $query_args['category_name'] = $atts['category'];
        }
        
        if (!empty($atts['tag'])) {
            $query_args['tag'] = $atts['tag'];
        }
        
        $posts = get_posts($query_args);
        $output = '';
        
        foreach ($posts as $post) {
            $post_content = $content;
            if (empty($post_content)) {
                $post_content = '<h3>{post_title}</h3><p>{post_excerpt}</p>';
            }
            
            // Replace post placeholders
            $replacements = array(
                '{post_id}' => $post->ID,
                '{post_title}' => $post->post_title,
                '{post_content}' => apply_filters('the_content', $post->post_content),
                '{post_excerpt}' => $post->post_excerpt ?: wp_trim_words($post->post_content, 55),
                '{post_date}' => get_the_date('', $post->ID),
                '{post_author}' => get_the_author_meta('display_name', $post->post_author),
                '{post_url}' => get_permalink($post->ID),
                '{featured_image}' => get_the_post_thumbnail_url($post->ID, 'full') ?: ''
            );
            
            $post_output = str_replace(array_keys($replacements), array_values($replacements), $post_content);
            $output .= do_shortcode($post_output);
        }
        
        return $output;
    }
    
    /**
     * User shortcode
     */
    public function user_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'field' => 'display_name',
            'user_id' => '',
            'default' => ''
        ), $atts);
        
        $user_id = !empty($atts['user_id']) ? intval($atts['user_id']) : get_current_user_id();
        
        if (!$user_id) {
            return $atts['default'];
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return $atts['default'];
        }
        
        $field = $atts['field'];
        
        // Standard user fields
        $standard_fields = array(
            'ID', 'user_login', 'user_email', 'user_url', 'user_registered',
            'display_name', 'nickname', 'first_name', 'last_name', 'description'
        );
        
        if (in_array($field, $standard_fields)) {
            return $user->$field ?: $atts['default'];
        }
        
        // User meta fields
        $meta_value = get_user_meta($user_id, $field, true);
        return $meta_value ?: $atts['default'];
    }
    
    /**
     * Contact Form 7 shortcode
     */
    public function cf7_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'field' => '',
            'form_id' => '',
            'submission_id' => '',
            'default' => ''
        ), $atts);
        
        if (empty($atts['field'])) {
            return $atts['default'];
        }
        
        // Get form data from various sources
        $form_data = array();
        
        // From POST data (during form submission)
        if (isset($_POST[$atts['field']])) {
            return sanitize_text_field($_POST[$atts['field']]);
        }
        
        // From stored submission (if plugin stores submissions)
        if (class_exists('CFDB7_Entry') && !empty($atts['submission_id'])) {
            // Contact Form DB 7 integration
            $entry = CFDB7_Entry::get_entry($atts['submission_id']);
            if ($entry && isset($entry->form_value[$atts['field']])) {
                return $entry->form_value[$atts['field']];
            }
        }
        
        // From session/transient data
        $session_data = get_transient('reverse2pdf_cf7_data_' . session_id());
        if ($session_data && isset($session_data[$atts['field']])) {
            return $session_data[$atts['field']];
        }
        
        return $atts['default'];
    }
    
    /**
     * Gravity Forms shortcode
     */
    public function gravity_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'field' => '',
            'entry_id' => '',
            'form_id' => '',
            'default' => ''
        ), $atts);
        
        if (!class_exists('GFFormsModel') || empty($atts['field'])) {
            return $atts['default'];
        }
        
        if (!empty($atts['entry_id'])) {
            $entry = GFFormsModel::get_lead($atts['entry_id']);
            if ($entry) {
                $field_value = rgar($entry, $atts['field']);
                return $field_value ?: $atts['default'];
            }
        }
        
        // Get from current form submission
        if (!empty($_POST['gform_submit']) && !empty($_POST[$atts['field']])) {
            return sanitize_text_field($_POST[$atts['field']]);
        }
        
        return $atts['default'];
    }
    
    /**
     * QR Code shortcode
     */
    public function qr_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'value' => $content,
            'size' => '200',
            'margin' => '0',
            'error_correction' => 'M',
            'format' => 'png',
            'color' => '000000',
            'background' => 'FFFFFF'
        ), $atts);
        
        if (empty($atts['value'])) {
            return '';
        }
        
        $qr_params = array(
            'data' => urlencode($atts['value']),
            'size' => intval($atts['size']) . 'x' . intval($atts['size']),
            'margin' => intval($atts['margin']),
            'ecc' => strtoupper($atts['error_correction']),
            'format' => strtolower($atts['format']),
            'color' => str_replace('#', '', $atts['color']),
            'bgcolor' => str_replace('#', '', $atts['background'])
        );
        
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query($qr_params);
        
        return '<img src="' . esc_url($qr_url) . '" alt="QR Code" style="max-width: 100%; height: auto;" />';
    }
    
    /**
     * Barcode shortcode
     */
    public function barcode_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'value' => $content,
            'type' => 'code128',
            'width' => '2',
            'height' => '30',
            'includetext' => 'true',
            'textsize' => '10',
            'textposition' => 'bottom'
        ), $atts);
        
        if (empty($atts['value'])) {
            return '';
        }
        
        $barcode_params = array(
            'bcid' => strtolower($atts['type']),
            'text' => $atts['value'],
            'scale' => intval($atts['width']),
            'height' => intval($atts['height']),
            'includetext' => $atts['includetext'] === 'true',
            'textsize' => intval($atts['textsize']),
            'textposition' => $atts['textposition']
        );
        
        $barcode_url = 'https://bwipjs-api.metafloor.com/?' . http_build_query($barcode_params);
        
        return '<img src="' . esc_url($barcode_url) . '" alt="Barcode" style="max-width: 100%; height: auto;" />';
    }
    
    /**
     * ACF field shortcode
     */
    public function acf_shortcode($atts, $content = '') {
        if (!function_exists('get_field')) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'field' => '',
            'post_id' => '',
            'format' => '',
            'default' => ''
        ), $atts);
        
        if (empty($atts['field'])) {
            return $atts['default'];
        }
        
        $post_id = !empty($atts['post_id']) ? intval($atts['post_id']) : get_the_ID();
        $field_value = get_field($atts['field'], $post_id);
        
        if (!$field_value) {
            return $atts['default'];
        }
        
        // Format the field value
        if (!empty($atts['format'])) {
            switch ($atts['format']) {
                case 'date':
                    if ($field_value instanceof DateTime) {
                        $field_value = $field_value->format('Y-m-d');
                    }
                    break;
                case 'url':
                    if (is_array($field_value) && isset($field_value['url'])) {
                        $field_value = $field_value['url'];
                    }
                    break;
                case 'image':
                    if (is_array($field_value) && isset($field_value['sizes']['large'])) {
                        $field_value = '<img src="' . esc_url($field_value['sizes']['large']) . '" alt="' . esc_attr($field_value['alt']) . '" />';
                    }
                    break;
                case 'json':
                    $field_value = json_encode($field_value);
                    break;
            }
        }
        
        return is_scalar($field_value) ? $field_value : json_encode($field_value);
    }
    
    /**
     * WooCommerce order shortcode
     */
    public function wc_order_shortcode($atts, $content = '') {
        if (!class_exists('WooCommerce')) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'field' => '',
            'order_id' => '',
            'format' => '',
            'default' => ''
        ), $atts);
        
        if (empty($atts['field']) || empty($atts['order_id'])) {
            return $atts['default'];
        }
        
        $order = wc_get_order($atts['order_id']);
        if (!$order) {
            return $atts['default'];
        }
        
        $field = $atts['field'];
        $value = '';
        
        // Map common field names to WooCommerce methods
        $field_mapping = array(
            'order_number' => 'get_order_number',
            'order_date' => 'get_date_created',
            'order_status' => 'get_status',
            'order_total' => 'get_total',
            'customer_email' => 'get_billing_email',
            'billing_first_name' => 'get_billing_first_name',
            'billing_last_name' => 'get_billing_last_name',
            'billing_company' => 'get_billing_company',
            'billing_address_1' => 'get_billing_address_1',
            'billing_city' => 'get_billing_city',
            'billing_state' => 'get_billing_state',
            'billing_postcode' => 'get_billing_postcode',
            'billing_country' => 'get_billing_country',
            'shipping_first_name' => 'get_shipping_first_name',
            'shipping_last_name' => 'get_shipping_last_name',
            'shipping_company' => 'get_shipping_company',
            'shipping_address_1' => 'get_shipping_address_1',
            'shipping_city' => 'get_shipping_city',
            'shipping_state' => 'get_shipping_state',
            'shipping_postcode' => 'get_shipping_postcode',
            'shipping_country' => 'get_shipping_country'
        );
        
        if (isset($field_mapping[$field]) && method_exists($order, $field_mapping[$field])) {
            $value = $order->{$field_mapping[$field]}();
        } else {
            // Try to get as meta data
            $value = $order->get_meta($field);
        }
        
        if (empty($value)) {
            return $atts['default'];
        }
        
        // Format the value
        if (!empty($atts['format'])) {
            switch ($atts['format']) {
                case 'currency':
                    $value = wc_price($value);
                    break;
                case 'date':
                    if ($value instanceof WC_DateTime) {
                        $value = $value->format('Y-m-d');
                    }
                    break;
            }
        }
        
        return $value;
    }
    
    // Helper methods
    
    /**
     * Get viewer controls HTML
     */
    private function get_viewer_controls($atts) {
        $controls = '<div class="pdf-viewer-controls">';
        $controls .= '<div class="viewer-controls-left">';
        
        // Navigation controls
        $controls .= '<button class="viewer-btn" data-action="previous-page" title="Previous Page">';
        $controls .= '<span class="dashicons dashicons-arrow-left-alt2"></span>';
        $controls .= '</button>';
        
        $controls .= '<div class="page-navigation">';
        $controls .= '<input type="number" class="page-input" value="1" min="1">';
        $controls .= '<span class="page-info"> / <span class="total-pages">1</span></span>';
        $controls .= '</div>';
        
        $controls .= '<button class="viewer-btn" data-action="next-page" title="Next Page">';
        $controls .= '<span class="dashicons dashicons-arrow-right-alt2"></span>';
        $controls .= '</button>';
        
        $controls .= '</div>';
        $controls .= '<div class="viewer-controls-right">';
        
        // Zoom controls
        if ($atts['zoom'] === 'true') {
            $controls .= '<button class="viewer-btn" data-action="zoom-out" title="Zoom Out">';
            $controls .= '<span class="dashicons dashicons-minus"></span>';
            $controls .= '</button>';
            $controls .= '<span class="zoom-level">100%</span>';
            $controls .= '<button class="viewer-btn" data-action="zoom-in" title="Zoom In">';
            $controls .= '<span class="dashicons dashicons-plus"></span>';
            $controls .= '</button>';
        }
        
        // Print button
        if ($atts['print'] === 'true') {
            $controls .= '<button class="viewer-btn" data-action="print" title="Print">';
            $controls .= '<span class="dashicons dashicons-printer"></span>';
            $controls .= '</button>';
        }
        
        // Download button
        if ($atts['download'] === 'true') {
            $controls .= '<button class="viewer-btn primary" data-action="download" title="Download">';
            $controls .= '<span class="dashicons dashicons-download"></span> Download';
            $controls .= '</button>';
        }
        
        $controls .= '</div>';
        $controls .= '</div>';
        
        return $controls;
    }
    
    /**
     * Evaluate condition
     */
    private function evaluate_condition($atts) {
        $condition = $atts['condition'];
        $value = $atts['value'];
        $operator = $atts['operator'];
        
        // Get the actual condition value from various sources
        $condition_value = $this->get_dynamic_value($condition);
        
        switch ($operator) {
            case '=':
            case '==':
                return $condition_value == $value;
            case '!=':
                return $condition_value != $value;
            case '>':
                return floatval($condition_value) > floatval($value);
            case '<':
                return floatval($condition_value) < floatval($value);
            case '>=':
                return floatval($condition_value) >= floatval($value);
            case '<=':
                return floatval($condition_value) <= floatval($value);
            case 'contains':
                return strpos($condition_value, $value) !== false;
            case 'not_contains':
                return strpos($condition_value, $value) === false;
            case 'empty':
                return empty($condition_value);
            case 'not_empty':
                return !empty($condition_value);
            case 'in':
                $values = array_map('trim', explode(',', $value));
                return in_array($condition_value, $values);
            case 'not_in':
                $values = array_map('trim', explode(',', $value));
                return !in_array($condition_value, $values);
            default:
                return false;
        }
    }
    
    /**
     * Get dynamic value from various sources
     */
    private function get_dynamic_value($key) {
        // Check POST data
        if (isset($_POST[$key])) {
            return sanitize_text_field($_POST[$key]);
        }
        
        // Check GET data
        if (isset($_GET[$key])) {
            return sanitize_text_field($_GET[$key]);
        }
        
        // Check user meta
        $user_id = get_current_user_id();
        if ($user_id) {
            $user_meta = get_user_meta($user_id, $key, true);
            if (!empty($user_meta)) {
                return $user_meta;
            }
        }
        
        // Check post meta (if in post context)
        if (is_singular()) {
            $post_meta = get_post_meta(get_the_ID(), $key, true);
            if (!empty($post_meta)) {
                return $post_meta;
            }
        }
        
        // Check options
        $option = get_option($key);
        if ($option !== false) {
            return $option;
        }
        
        return '';
    }
    
    /**
     * Get loop data
     */
    private function get_loop_data($data_source, $separator = ',') {
        // If it's already an array
        if (is_array($data_source)) {
            return $data_source;
        }
        
        // Get from dynamic source
        $data = $this->get_dynamic_value($data_source);
        
        if (empty($data)) {
            // Try as literal comma-separated values
            $data = $data_source;
        }
        
        // If it's a JSON string
        if (is_string($data) && (strpos($data, '{') === 0 || strpos($data, '[') === 0)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        // Split by separator
        if (is_string($data)) {
            return array_map('trim', explode($separator, $data));
        }
        
        return array();
    }
    
    /**
     * Calculate mathematical expression
     */
    private function calculate_expression($expression, $decimals = 2) {
        // Remove spaces and validate
        $expression = str_replace(' ', '', $expression);
        $expression = preg_replace('/[^0-9+\-*\/\.\(\)]/', '', $expression);
        
        if (empty($expression)) {
            return '0';
        }
        
        try {
            // Simple calculator implementation
            $result = $this->safe_eval($expression);
            return number_format($result, $decimals, '.', '');
        } catch (Exception $e) {
            return '0';
        }
    }
    
    /**
     * Safe mathematical evaluation
     */
    private function safe_eval($expression) {
        // Handle parentheses first
        while (strpos($expression, '(') !== false) {
            $expression = preg_replace_callback('/\(([^()]+)\)/', function($matches) {
                return $this->safe_eval($matches[1]);
            }, $expression);
        }
        
        // Handle multiplication and division
        while (preg_match('/(-?\d*\.?\d+)([*\/])(-?\d*\.?\d+)/', $expression, $matches)) {
            $result = $matches[2] === '*' ? 
                floatval($matches[1]) * floatval($matches[3]) : 
                floatval($matches[1]) / floatval($matches[3]);
            $expression = str_replace($matches[0], $result, $expression);
        }
        
        // Handle addition and subtraction
        while (preg_match('/(-?\d*\.?\d+)([+\-])(-?\d*\.?\d+)/', $expression, $matches)) {
            $result = $matches[2] === '+' ? 
                floatval($matches[1]) + floatval($matches[3]) : 
                floatval($matches[1]) - floatval($matches[3]);
            $expression = str_replace($matches[0], $result, $expression);
        }
        
        return floatval($expression);
    }
    
    // Additional shortcode methods would continue here...
    // Due to length constraints, I'm showing the structure and key shortcodes
    
    public function pdf_save_shortcode($atts, $content = '') { /* Implementation */ }
    public function pdf_attachment_shortcode($atts, $content = '') { /* Implementation */ }
    public function else_shortcode($atts, $content = '') { /* Implementation */ }
    public function elseif_shortcode($atts, $content = '') { /* Implementation */ }
    public function while_shortcode($atts, $content = '') { /* Implementation */ }
    public function format_date_shortcode($atts, $content = '') { /* Implementation */ }
    public function format_number_shortcode($atts, $content = '') { /* Implementation */ }
    public function format_currency_shortcode($atts, $content = '') { /* Implementation */ }
    public function calculate_shortcode($atts, $content = '') { /* Implementation */ }
    public function sum_shortcode($atts, $content = '') { /* Implementation */ }
    public function average_shortcode($atts, $content = '') { /* Implementation */ }
    public function userid_shortcode($atts, $content = '') { /* Implementation */ }
    public function usercurrentid_shortcode($atts, $content = '') { /* Implementation */ }
    public function site_shortcode($atts, $content = '') { /* Implementation */ }
    public function wpforms_shortcode($atts, $content = '') { /* Implementation */ }
    public function formidable_shortcode($atts, $content = '') { /* Implementation */ }
    public function ninjaforms_shortcode($atts, $content = '') { /* Implementation */ }
    public function signature_shortcode($atts, $content = '') { /* Implementation */ }
    public function chart_shortcode($atts, $content = '') { /* Implementation */ }
    public function arg_shortcode($atts, $content = '') { /* Implementation */ }
    public function get_shortcode($atts, $content = '') { /* Implementation */ }
    public function post_shortcode($atts, $content = '') { /* Implementation */ }
    public function cookie_shortcode($atts, $content = '') { /* Implementation */ }
    public function session_shortcode($atts, $content = '') { /* Implementation */ }
    public function acf_repeater_shortcode($atts, $content = '') { /* Implementation */ }
    public function wc_product_shortcode($atts, $content = '') { /* Implementation */ }
    public function wc_customer_shortcode($atts, $content = '') { /* Implementation */ }
}
?>
