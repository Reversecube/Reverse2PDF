<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Enhanced_Generator {
    
    private $settings;
    private $logger;
    
    public function __construct() {
        $this->settings = get_option('reverse2pdf_settings', array());
        
        if (class_exists('Reverse2PDF_Logger')) {
            $this->logger = new Reverse2PDF_Logger();
        }
    }
    
    public function generate_pdf($template_id, $dataset_id = 0, $form_data = array()) {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        try {
            // Get template
            $template = $this->get_template($template_id);
            if (!$template) {
                throw new Exception('Template not found');
            }
            
            // Process template data
            $template_data = json_decode($template->template_data, true);
            if (!$template_data) {
                throw new Exception('Invalid template data');
            }
            
            // Process form data and merge with template
            $processed_data = $this->process_form_data($form_data);
            
            // Apply conditional logic
            $template_data = $this->apply_conditional_logic($template_data, $processed_data);
            
            // Generate HTML content
            $html_content = $this->generate_html_content($template_data, $processed_data, $template);
            
            // Create PDF file
            $pdf_url = $this->create_pdf_file($html_content, $template->name, $template_id);
            
            // Log success
            $execution_time = microtime(true) - $start_time;
            $memory_usage = memory_get_usage() - $start_memory;
            
            $this->log_activity($template_id, 'pdf_generated', 'success', 'PDF generated successfully', array(
                'execution_time' => $execution_time,
                'memory_usage' => $memory_usage,
                'form_data_keys' => array_keys($processed_data)
            ));
            
            return $pdf_url;
            
        } catch (Exception $e) {
            // Log error
            $this->log_activity($template_id, 'pdf_generation_failed', 'error', $e->getMessage());
            
            if (isset($this->settings['enable_debug']) && $this->settings['enable_debug']) {
                error_log('Reverse2PDF Error: ' . $e->getMessage());
            }
            
            return false;
        }
    }
    
    private function get_template($template_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " WHERE id = %d AND active = 1",
            $template_id
        ));
    }
    
    private function process_form_data($form_data) {
        $processed = array();
        
        // Add default variables
        $processed['date'] = current_time('Y-m-d');
        $processed['time'] = current_time('H:i:s');
        $processed['datetime'] = current_time('Y-m-d H:i:s');
        $processed['site_name'] = get_bloginfo('name');
        $processed['site_url'] = get_bloginfo('url');
        $processed['admin_email'] = get_bloginfo('admin_email');
        
        // Add user data if logged in
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $processed['user_id'] = $user->ID;
            $processed['user_login'] = $user->user_login;
            $processed['user_email'] = $user->user_email;
            $processed['user_display_name'] = $user->display_name;
            $processed['user_first_name'] = $user->first_name;
            $processed['user_last_name'] = $user->last_name;
        }
        
        // Process form data
        if (is_array($form_data)) {
            foreach ($form_data as $key => $value) {
                if (is_string($value)) {
                    $processed[$key] = sanitize_text_field($value);
                } elseif (is_array($value)) {
                    $processed[$key] = implode(', ', array_map('sanitize_text_field', $value));
                } else {
                    $processed[$key] = sanitize_text_field((string) $value);
                }
            }
        }
        
        // Apply filters
        return apply_filters('reverse2pdf_processed_form_data', $processed, $form_data);
    }
    
    private function apply_conditional_logic($template_data, $form_data) {
        if (!isset($template_data['pages']) || !is_array($template_data['pages'])) {
            return $template_data;
        }
        
        foreach ($template_data['pages'] as $page_index => &$page) {
            if (!isset($page['elements']) || !is_array($page['elements'])) {
                continue;
            }
            
            $filtered_elements = array();
            
            foreach ($page['elements'] as $element) {
                if ($this->should_show_element($element, $form_data)) {
                    // Process loops and iterations
                    if (isset($element['loop']) && $element['loop']) {
                        $loop_elements = $this->process_loop_element($element, $form_data);
                        $filtered_elements = array_merge($filtered_elements, $loop_elements);
                    } else {
                        $filtered_elements[] = $element;
                    }
                }
            }
            
            $page['elements'] = $filtered_elements;
        }
        
        return $template_data;
    }
    
    private function should_show_element($element, $form_data) {
        if (!isset($element['conditions']) || empty($element['conditions'])) {
            return true;
        }
        
        $conditions = $element['conditions'];
        if (!is_array($conditions)) {
            return true;
        }
        
        foreach ($conditions as $condition) {
            if (!$this->evaluate_condition($condition, $form_data)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function evaluate_condition($condition, $form_data) {
        if (!isset($condition['field']) || !isset($condition['operator']) || !isset($condition['value'])) {
            return true;
        }
        
        $field = $condition['field'];
        $operator = $condition['operator'];
        $expected_value = $condition['value'];
        $actual_value = $form_data[$field] ?? '';
        
        switch ($operator) {
            case '=':
            case 'equals':
                return $actual_value == $expected_value;
                
            case '!=':
            case 'not_equals':
                return $actual_value != $expected_value;
                
            case 'contains':
                return strpos($actual_value, $expected_value) !== false;
                
            case 'not_contains':
                return strpos($actual_value, $expected_value) === false;
                
            case 'empty':
                return empty($actual_value);
                
            case 'not_empty':
                return !empty($actual_value);
                
            case '>':
            case 'greater_than':
                return floatval($actual_value) > floatval($expected_value);
                
            case '<':
            case 'less_than':
                return floatval($actual_value) < floatval($expected_value);
                
            case '>=':
            case 'greater_equal':
                return floatval($actual_value) >= floatval($expected_value);
                
            case '<=':
            case 'less_equal':
                return floatval($actual_value) <= floatval($expected_value);
                
            default:
                return true;
        }
    }
    
    private function process_loop_element($element, $form_data) {
        $loop_elements = array();
        $loop_config = $element['loop'];
        
        if (isset($loop_config['type'])) {
            switch ($loop_config['type']) {
                case 'for':
                    $loop_elements = $this->process_for_loop($element, $loop_config, $form_data);
                    break;
                    
                case 'foreach':
                    $loop_elements = $this->process_foreach_loop($element, $loop_config, $form_data);
                    break;
                    
                case 'while':
                    $loop_elements = $this->process_while_loop($element, $loop_config, $form_data);
                    break;
            }
        }
        
        return $loop_elements;
    }
    
    private function process_for_loop($element, $loop_config, $form_data) {
        $elements = array();
        $start = intval($loop_config['start'] ?? 1);
        $end = intval($loop_config['end'] ?? 10);
        $step = intval($loop_config['step'] ?? 1);
        
        $current_y = $element['y'];
        
        for ($i = $start; $i <= $end; $i += $step) {
            $loop_element = $element;
            $loop_element['id'] = $element['id'] . '_loop_' . $i;
            $loop_element['y'] = $current_y;
            
            // Replace loop variables
            $loop_element['content'] = str_replace('{i}', $i, $loop_element['content']);
            $loop_element['content'] = str_replace('{index}', $i, $loop_element['content']);
            
            $elements[] = $loop_element;
            $current_y += intval($element['height'] ?? 25) + 5;
        }
        
        return $elements;
    }
    
    private function process_foreach_loop($element, $loop_config, $form_data) {
        $elements = array();
        $data_source = $loop_config['data_source'] ?? '';
        
        if (isset($form_data[$data_source]) && is_array($form_data[$data_source])) {
            $current_y = $element['y'];
            
            foreach ($form_data[$data_source] as $index => $item) {
                $loop_element = $element;
                $loop_element['id'] = $element['id'] . '_loop_' . $index;
                $loop_element['y'] = $current_y;
                
                // Replace loop variables
                $loop_element['content'] = str_replace('{item}', $item, $loop_element['content']);
                $loop_element['content'] = str_replace('{index}', $index, $loop_element['content']);
                
                $elements[] = $loop_element;
                $current_y += intval($element['height'] ?? 25) + 5;
            }
        }
        
        return $elements;
    }
    
    private function process_while_loop($element, $loop_config, $form_data) {
        $elements = array();
        $max_iterations = 100; // Safety limit
        $iterations = 0;
        $current_y = $element['y'];
        
        while ($iterations < $max_iterations) {
            // Evaluate while condition
            if (isset($loop_config['condition']) && !$this->evaluate_condition($loop_config['condition'], $form_data)) {
                break;
            }
            
            $loop_element = $element;
            $loop_element['id'] = $element['id'] . '_loop_' . $iterations;
            $loop_element['y'] = $current_y;
            
            // Replace loop variables
            $loop_element['content'] = str_replace('{iteration}', $iterations, $loop_element['content']);
            
            $elements[] = $loop_element;
            $current_y += intval($element['height'] ?? 25) + 5;
            $iterations++;
        }
        
        return $elements;
    }
    
    private function generate_html_content($template_data, $form_data, $template) {
        $template_settings = json_decode($template->settings, true) ?: array();
        
        // Get paper settings
        $paper_size = $template_settings['paper_size'] ?? $this->settings['paper_size'] ?? 'A4';
        $orientation = $template_settings['paper_orientation'] ?? $this->settings['paper_orientation'] ?? 'portrait';
        
        // Calculate dimensions
        $dimensions = $this->get_paper_dimensions($paper_size, $orientation);
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_html($template->name) . '</title>
    <style>
        @page {
            size: ' . $paper_size . ' ' . $orientation . ';
            margin: ' . ($this->settings['margin_top'] ?? '20mm') . ' ' . 
                      ($this->settings['margin_right'] ?? '15mm') . ' ' . 
                      ($this->settings['margin_bottom'] ?? '20mm') . ' ' . 
                      ($this->settings['margin_left'] ?? '15mm') . ';
        }
        
        body {
            font-family: ' . ($this->settings['default_font'] ?? 'Arial') . ', sans-serif;
            font-size: ' . ($this->settings['base_font_size'] ?? '12px') . ';
            line-height: ' . ($this->settings['line_height'] ?? '1.4') . ';
            margin: 0;
            padding: 0;
        }
        
        .page {
            position: relative;
            width: ' . $dimensions['width'] . 'px;
            height: ' . $dimensions['height'] . 'px;
            page-break-after: auto;
        }
        
        .element {
            position: absolute;
        }
        
        .element-text {
            overflow: hidden;
            word-wrap: break-word;
        }
        
        .element-image {
            overflow: hidden;
        }
        
        .element-line {
            border-top: 1px solid #000;
        }
        
        .element-rectangle {
            border: 1px solid #000;
        }
        
        @media print {
            .page {
                page-break-after: always;
            }
            .page:last-child {
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>';
        
        // Process pages
        if (isset($template_data['pages']) && is_array($template_data['pages'])) {
            foreach ($template_data['pages'] as $page_index => $page) {
                $html .= '<div class="page" data-page="' . ($page_index + 1) . '">';
                
                if (isset($page['elements']) && is_array($page['elements'])) {
                    foreach ($page['elements'] as $element) {
                        $html .= $this->render_element($element, $form_data);
                    }
                }
                
                $html .= '</div>';
            }
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    private function render_element($element, $form_data) {
        $element_type = $element['type'] ?? 'text';
        $element_id = $element['id'] ?? 'element_' . uniqid();
        
        // Base styles
        $styles = array(
            'left: ' . intval($element['x'] ?? 0) . 'px',
            'top: ' . intval($element['y'] ?? 0) . 'px',
            'width: ' . intval($element['width'] ?? 100) . 'px',
            'height: ' . intval($element['height'] ?? 25) . 'px'
        );
        
        // Add element-specific styles
        if (isset($element['fontSize'])) {
            $styles[] = 'font-size: ' . intval($element['fontSize']) . 'px';
        }
        
        if (isset($element['fontWeight'])) {
            $styles[] = 'font-weight: ' . esc_attr($element['fontWeight']);
        }
        
        if (isset($element['color'])) {
            $styles[] = 'color: ' . esc_attr($element['color']);
        }
        
        if (isset($element['backgroundColor'])) {
            $styles[] = 'background-color: ' . esc_attr($element['backgroundColor']);
        }
        
        if (isset($element['textAlign'])) {
            $styles[] = 'text-align: ' . esc_attr($element['textAlign']);
        }
        
        if (isset($element['borderWidth']) && $element['borderWidth'] > 0) {
            $styles[] = 'border: ' . intval($element['borderWidth']) . 'px solid ' . ($element['borderColor'] ?? '#000');
        }
        
        $style_attr = 'style="' . implode('; ', $styles) . '"';
        $class_attr = 'class="element element-' . esc_attr($element_type) . '"';
        
        // Process content
        $content = $element['content'] ?? '';
        $content = $this->replace_placeholders($content, $form_data);
        
        // Render based on element type
        switch ($element_type) {
            case 'text':
                return '<div ' . $class_attr . ' ' . $style_attr . '>' . wp_kses_post($content) . '</div>';
                
            case 'image':
                $src = $this->process_image_content($content, $form_data);
                return '<div ' . $class_attr . ' ' . $style_attr . '><img src="' . esc_url($src) . '" style="width: 100%; height: 100%; object-fit: contain;" /></div>';
                
            case 'line':
                return '<div ' . $class_attr . ' ' . $style_attr . '></div>';
                
            case 'rectangle':
                return '<div ' . $class_attr . ' ' . $style_attr . '>' . wp_kses_post($content) . '</div>';
                
            case 'qr-code':
                return $this->render_qr_code($element, $content, $style_attr, $class_attr);
                
            case 'barcode':
                return $this->render_barcode($element, $content, $style_attr, $class_attr);
                
            case 'table':
                return $this->render_table($element, $form_data, $style_attr, $class_attr);
                
            default:
                return '<div ' . $class_attr . ' ' . $style_attr . '>' . wp_kses_post($content) . '</div>';
        }
    }
    
    private function replace_placeholders($content, $form_data) {
        // Replace form data placeholders
        foreach ($form_data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        
        // Process mathematical expressions
        $content = $this->process_math_expressions($content, $form_data);
        
        // Process conditional expressions
        $content = $this->process_conditional_expressions($content, $form_data);
        
        return $content;
    }
    
    private function process_math_expressions($content, $form_data) {
        // Simple math expression processing
        preg_match_all('/\{math:([^}]+)\}/', $content, $matches);
        
        foreach ($matches[0] as $index => $match) {
            $expression = $matches[1][$index];
            
            // Replace variables in expression
            foreach ($form_data as $key => $value) {
                if (is_numeric($value)) {
                    $expression = str_replace($key, $value, $expression);
                }
            }
            
            // Evaluate safe math expression
            $result = $this->evaluate_math_expression($expression);
            $content = str_replace($match, $result, $content);
        }
        
        return $content;
    }
    
    private function evaluate_math_expression($expression) {
        // Basic math evaluation (safe)
        $expression = preg_replace('/[^0-9\+\-\*\/\(\)\.\s]/', '', $expression);
        
        try {
            $result = eval("return $expression;");
            return is_numeric($result) ? number_format($result, 2) : '0.00';
        } catch (Exception $e) {
            return '0.00';
        }
    }
    
    private function process_conditional_expressions($content, $form_data) {
        // Process conditional expressions like {if:field=value}content{/if}
        preg_match_all('/\{if:([^}]+)\}(.*?)\{\/if\}/s', $content, $matches);
        
        foreach ($matches[0] as $index => $match) {
            $condition = $matches[1][$index];
            $conditional_content = $matches[2][$index];
            
            if ($this->evaluate_simple_condition($condition, $form_data)) {
                $content = str_replace($match, $conditional_content, $content);
            } else {
                $content = str_replace($match, '', $content);
            }
        }
        
        return $content;
    }
    
    private function evaluate_simple_condition($condition, $form_data) {
        if (strpos($condition, '=') !== false) {
            list($field, $value) = explode('=', $condition, 2);
            return isset($form_data[trim($field)]) && $form_data[trim($field)] == trim($value);
        }
        
        return false;
    }
    
    private function process_image_content($content, $form_data) {
        // If content is a placeholder, try to get image URL from form data
        if (preg_match('/\{([^}]+)\}/', $content, $matches)) {
            $field = $matches[1];
            if (isset($form_data[$field])) {
                return $form_data[$field];
            }
        }
        
        // Return as-is if it looks like a URL
        if (filter_var($content, FILTER_VALIDATE_URL)) {
            return $content;
        }
        
        // Default placeholder image
        return REVERSE2PDF_PLUGIN_URL . 'assets/images/placeholder.png';
    }
    
    private function render_qr_code($element, $content, $style_attr, $class_attr) {
        // For now, return a placeholder. In a full implementation, you'd generate actual QR codes
        $qr_data = urlencode($content);
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . $qr_data;
        
        return '<div ' . $class_attr . ' ' . $style_attr . '><img src="' . esc_url($qr_url) . '" style="width: 100%; height: 100%;" /></div>';
    }
    
    private function render_barcode($element, $content, $style_attr, $class_attr) {
        // For now, return a placeholder. In a full implementation, you'd generate actual barcodes
        return '<div ' . $class_attr . ' ' . $style_attr . ' style="border: 1px solid #000; text-align: center; padding: 5px;"><div style="background: repeating-linear-gradient(90deg, #000 0px, #000 2px, #fff 2px, #fff 4px); height: 60%; margin-bottom: 5px;"></div><small>' . esc_html($content) . '</small></div>';
    }
    
    private function render_table($element, $form_data, $style_attr, $class_attr) {
        $table_data = $element['table_data'] ?? array();
        
        $html = '<div ' . $class_attr . ' ' . $style_attr . '><table style="width: 100%; border-collapse: collapse;">';
        
        foreach ($table_data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $cell_content = $this->replace_placeholders($cell, $form_data);
                $html .= '<td style="border: 1px solid #000; padding: 5px;">' . wp_kses_post($cell_content) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</table></div>';
        
        return $html;
    }
    
    private function get_paper_dimensions($paper_size, $orientation) {
        $dimensions = array(
            'A4' => array('width' => 595, 'height' => 842),
            'A3' => array('width' => 842, 'height' => 1191),
            'Letter' => array('width' => 612, 'height' => 792),
            'Legal' => array('width' => 612, 'height' => 1008)
        );
        
        $size = $dimensions[$paper_size] ?? $dimensions['A4'];
        
        if ($orientation === 'landscape') {
            return array('width' => $size['height'], 'height' => $size['width']);
        }
        
        return $size;
    }
    
    private function create_pdf_file($html_content, $template_name, $template_id) {
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/reverse2pdf/pdfs';
        
        // Ensure directory exists
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }
        
        // Create filename
        $safe_filename = sanitize_file_name($template_name) . '_' . $template_id . '_' . time();
        
        // For now, create HTML file (in production, you'd use DomPDF, TCPDF, or mPDF)
        $html_file = $pdf_dir . '/' . $safe_filename . '.html';
        
        if (file_put_contents($html_file, $html_content)) {
            $file_url = $upload_dir['baseurl'] . '/reverse2pdf/pdfs/' . $safe_filename . '.html';
            return $file_url;
        }
        
        return false;
    }
    
    private function log_activity($template_id, $action, $status, $message, $data = array()) {
        if (!$this->logger) {
            return;
        }
        
        $this->logger->log($template_id, $action, $status, $message, $data);
    }
}
?>
