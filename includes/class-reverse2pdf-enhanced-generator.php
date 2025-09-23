<?php
/**
 * Enhanced Reverse2PDF Generator Class
 * Complete PDF generation with all E2Pdf features including conditional logic,
 * mathematical expressions, loops, and advanced formatting
 */

if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Enhanced_Generator extends Reverse2PDF_Generator {
    
    private $conditional_logic;
    private $current_dataset;
    private $loops_stack;
    private $variables;
    private $math_parser;
    
    public function __construct() {
        parent::__construct();
        $this->conditional_logic = new Reverse2PDF_Conditional_Logic();
        $this->loops_stack = array();
        $this->variables = array();
    }
    
    /**
     * Enhanced PDF generation with full feature support
     */
    public function generate_pdf_enhanced($template_id, $dataset_id = 0, $options = array()) {
        $template = $this->get_template($template_id);
        if (!$template) {
            return false;
        }
        
        // Set default options
        $options = wp_parse_args($options, array(
            'flatten' => true,
            'password' => '',
            'permissions' => array('print', 'copy'),
            'watermark' => '',
            'header' => '',
            'footer' => '',
            'page_numbering' => false,
            'rtl_support' => false,
            'font_subsetting' => true,
            'image_dpi' => 150,
            'compression' => true,
            'metadata' => array(),
            'digital_signature' => false,
            'form_data' => array()
        ));
        
        // Load dataset
        $this->current_dataset = $this->load_dataset($dataset_id, $template_id, $options['form_data']);
        
        // Process template with enhanced features
        $processed_template = $this->process_enhanced_template($template, $options);
        
        // Generate PDF with advanced options
        return $this->create_enhanced_pdf($processed_template, $template->name, $options);
    }
    
    /**
     * Process template with all E2Pdf features
     */
    private function process_enhanced_template($template, $options) {
        $template_data = json_decode($template->template_data, true);
        if (!$template_data) {
            return $template;
        }
        
        // Initialize processing context
        $this->variables = $this->load_global_variables();
        
        $processed_data = array();
        
        // Handle both single page and multi-page templates
        if (isset($template_data['pages'])) {
            // Multi-page template
            foreach ($template_data['pages'] as $page_index => $page) {
                $processed_page = $this->process_page($page, $page_index, $options);
                if ($processed_page !== false) {
                    $processed_data['pages'][] = $processed_page;
                }
            }
        } elseif (isset($template_data['elements'])) {
            // Single page template - convert to multi-page format
            $page = array('elements' => $template_data['elements']);
            $processed_page = $this->process_page($page, 0, $options);
            if ($processed_page !== false) {
                $processed_data['pages'] = array($processed_page);
            }
        }
        
        $template->template_data = json_encode($processed_data);
        return $template;
    }
    
    /**
     * Process individual page with conditional logic
     */
    private function process_page($page, $page_index, $options) {
        // Check page conditions
        if (isset($page['conditions']) && !empty($page['conditions'])) {
            if (!$this->conditional_logic->evaluate_conditions($page['conditions'], $this->current_dataset)) {
                return false; // Skip this page
            }
        }
        
        $processed_elements = array();
        
        if (isset($page['elements'])) {
            foreach ($page['elements'] as $element) {
                $processed_element = $this->process_element($element, $page_index, $options);
                if ($processed_element !== false) {
                    if (is_array($processed_element) && isset($processed_element[0])) {
                        // Multiple elements returned (from loops)
                        $processed_elements = array_merge($processed_elements, $processed_element);
                    } else {
                        $processed_elements[] = $processed_element;
                    }
                }
            }
        }
        
        $page['elements'] = $processed_elements;
        return $page;
    }
    
    /**
     * Process individual element with all features
     */
    private function process_element($element, $page_index, $options) {
        // Check element conditions
        if (isset($element['conditions']) && !empty($element['conditions'])) {
            if (!$this->conditional_logic->evaluate_conditions($element['conditions'], $this->current_dataset)) {
                return false; // Skip this element
            }
        }
        
        // Handle loops
        if (isset($element['loop']) && !empty($element['loop'])) {
            return $this->process_loop_element($element, $page_index, $options);
        }
        
        // Process element content
        $element = $this->process_element_content($element);
        
        // Handle dynamic positioning
        if (isset($element['dynamic_position']) && $element['dynamic_position']) {
            $element = $this->calculate_dynamic_position($element);
        }
        
        // Handle element scripting
        if (isset($element['script']) && !empty($element['script'])) {
            $element = $this->execute_element_script($element);
        }
        
        return $element;
    }
    
    /**
     * Process loop elements (for, foreach, while)
     */
    private function process_loop_element($element, $page_index, $options) {
        $loop_config = $element['loop'];
        $loop_type = $loop_config['type'];
        $elements = array();
        
        switch ($loop_type) {
            case 'for':
                $elements = $this->process_for_loop($element, $loop_config);
                break;
            case 'foreach':
                $elements = $this->process_foreach_loop($element, $loop_config);
                break;
            case 'while':
                $elements = $this->process_while_loop($element, $loop_config);
                break;
            case 'repeat':
                $elements = $this->process_repeat_loop($element, $loop_config);
                break;
        }
        
        return $elements;
    }
    
    /**
     * Process FOR loop
     */
    private function process_for_loop($element, $config) {
        $start = intval($this->parse_dynamic_value($config['start']));
        $end = intval($this->parse_dynamic_value($config['end']));
        $step = intval($config['step'] ?? 1);
        
        if ($step <= 0) $step = 1;
        if ($start > $end && $step > 0) return array($element);
        
        $elements = array();
        $original_x = $element['x'] ?? 0;
        $original_y = $element['y'] ?? 0;
        $x_offset = 0;
        $y_offset = 0;
        
        for ($i = $start; $i <= $end; $i += $step) {
            // Set loop variables
            $this->variables['i'] = $i;
            $this->variables['loop_index'] = $i - $start;
            $this->variables['loop_iteration'] = ($i - $start) / $step + 1;
            
            $loop_element = $element;
            unset($loop_element['loop']); // Remove loop config
            
            // Update position
            if (isset($config['offset_x'])) {
                $loop_element['x'] = $original_x + $x_offset;
                $x_offset += intval($config['offset_x']);
            }
            
            if (isset($config['offset_y'])) {
                $loop_element['y'] = $original_y + $y_offset;
                $y_offset += intval($config['offset_y']);
            }
            
            // Process element content with loop variables
            $loop_element = $this->process_element_content($loop_element);
            
            $elements[] = $loop_element;
        }
        
        return $elements;
    }
    
    /**
     * Process FOREACH loop
     */
    private function process_foreach_loop($element, $config) {
        $data_source = $this->parse_dynamic_value($config['data_source']);
        
        if (!is_array($data_source)) {
            // Try to parse as JSON
            if (is_string($data_source)) {
                $decoded = json_decode($data_source, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data_source = $decoded;
                } else {
                    // Try to parse as comma-separated values
                    $data_source = array_map('trim', explode(',', $data_source));
                }
            } else {
                return array($element); // Return original element if no array
            }
        }
        
        $elements = array();
        $original_x = $element['x'] ?? 0;
        $original_y = $element['y'] ?? 0;
        $x_offset = 0;
        $y_offset = 0;
        
        foreach ($data_source as $index => $item) {
            // Set loop variables
            $this->variables['item'] = $item;
            $this->variables['index'] = $index;
            $this->variables['loop_index'] = $index;
            $this->variables['loop_iteration'] = $index + 1;
            
            if (is_array($item)) {
                foreach ($item as $key => $value) {
                    $this->variables['item_' . $key] = $value;
                    $this->variables[$key] = $value; // Also available without prefix
                }
            } else {
                $this->variables['item_value'] = $item;
            }
            
            $loop_element = $element;
            unset($loop_element['loop']); // Remove loop config
            
            // Update position
            if (isset($config['offset_x'])) {
                $loop_element['x'] = $original_x + $x_offset;
                $x_offset += intval($config['offset_x']);
            }
            
            if (isset($config['offset_y'])) {
                $loop_element['y'] = $original_y + $y_offset;
                $y_offset += intval($config['offset_y']);
            }
            
            // Process element content with loop variables
            $loop_element = $this->process_element_content($loop_element);
            
            $elements[] = $loop_element;
        }
        
        return $elements;
    }
    
    /**
     * Process WHILE loop
     */
    private function process_while_loop($element, $config) {
        $condition = $config['condition'] ?? '';
        $max_iterations = intval($config['max_iterations'] ?? 100); // Prevent infinite loops
        
        $elements = array();
        $iteration = 0;
        $original_x = $element['x'] ?? 0;
        $original_y = $element['y'] ?? 0;
        $x_offset = 0;
        $y_offset = 0;
        
        while ($iteration < $max_iterations) {
            // Set iteration variables
            $this->variables['iteration'] = $iteration;
            $this->variables['loop_iteration'] = $iteration + 1;
            
            // Evaluate condition
            if (!$this->conditional_logic->evaluate_simple_condition($condition, array_merge($this->current_dataset, $this->variables))) {
                break;
            }
            
            $loop_element = $element;
            unset($loop_element['loop']); // Remove loop config
            
            // Update position
            if (isset($config['offset_x'])) {
                $loop_element['x'] = $original_x + $x_offset;
                $x_offset += intval($config['offset_x']);
            }
            
            if (isset($config['offset_y'])) {
                $loop_element['y'] = $original_y + $y_offset;
                $y_offset += intval($config['offset_y']);
            }
            
            // Process element content with loop variables
            $loop_element = $this->process_element_content($loop_element);
            
            $elements[] = $loop_element;
            $iteration++;
        }
        
        return $elements;
    }
    
    /**
     * Process REPEAT loop (simple repetition)
     */
    private function process_repeat_loop($element, $config) {
        $count = intval($this->parse_dynamic_value($config['count']));
        
        if ($count <= 0) {
            return array($element);
        }
        
        $elements = array();
        $original_x = $element['x'] ?? 0;
        $original_y = $element['y'] ?? 0;
        $x_offset = 0;
        $y_offset = 0;
        
        for ($i = 0; $i < $count; $i++) {
            // Set loop variables
            $this->variables['repeat_index'] = $i;
            $this->variables['repeat_iteration'] = $i + 1;
            
            $loop_element = $element;
            unset($loop_element['loop']); // Remove loop config
            
            // Update position
            if (isset($config['offset_x'])) {
                $loop_element['x'] = $original_x + $x_offset;
                $x_offset += intval($config['offset_x']);
            }
            
            if (isset($config['offset_y'])) {
                $loop_element['y'] = $original_y + $y_offset;
                $y_offset += intval($config['offset_y']);
            }
            
            // Process element content with loop variables
            $loop_element = $this->process_element_content($loop_element);
            
            $elements[] = $loop_element;
        }
        
        return $elements;
    }
    
    /**
     * Process element content with dynamic values
     */
    private function process_element_content($element) {
        // Process text content
        if (isset($element['content'])) {
            $element['content'] = $this->parse_dynamic_content($element['content']);
        }
        
        // Process image source
        if (isset($element['src'])) {
            $element['src'] = $this->parse_dynamic_content($element['src']);
        }
        
        // Process field value
        if (isset($element['value'])) {
            $element['value'] = $this->parse_dynamic_content($element['value']);
        }
        
        // Process field mapping
        if (isset($element['field_mapping'])) {
            $element = $this->apply_field_mapping($element);
        }
        
        // Process calculations
        if (isset($element['calculations'])) {
            $element = $this->process_calculations($element);
        }
        
        // Process dynamic styling
        if (isset($element['dynamic_style'])) {
            $element = $this->apply_dynamic_styling($element);
        }
        
        return $element;
    }
    
    /**
     * Parse dynamic content with shortcodes and variables
     */
    private function parse_dynamic_content($content) {
        if (!is_string($content)) {
            return $content;
        }
        
        // Replace variables
        foreach ($this->variables as $var_name => $var_value) {
            if (is_scalar($var_value)) {
                $content = str_replace('{' . $var_name . '}', $var_value, $content);
            }
        }
        
        // Replace dataset fields
        if ($this->current_dataset) {
            foreach ($this->current_dataset as $field_name => $field_value) {
                if (is_scalar($field_value)) {
                    $content = str_replace('{' . $field_name . '}', $field_value, $content);
                }
            }
        }
        
        // Process shortcodes
        $content = do_shortcode($content);
        
        // Process mathematical expressions
        $content = $this->process_math_expressions($content);
        
        // Process conditional expressions
        $content = $this->process_conditional_expressions($content);
        
        // Process date formatting
        $content = $this->process_date_expressions($content);
        
        // Process number formatting
        $content = $this->process_number_expressions($content);
        
        return $content;
    }
    
    /**
     * Parse dynamic value (for non-string contexts)
     */
    private function parse_dynamic_value($value) {
        if (is_string($value) && strpos($value, '{') !== false) {
            return $this->parse_dynamic_content($value);
        }
        return $value;
    }
    
    /**
     * Process mathematical expressions in content
     */
    private function process_math_expressions($content) {
        // Pattern for math expressions: {= expression =}
        $pattern = '/\{=\s*([^=}]+)\s*=\}/';
        
        return preg_replace_callback($pattern, function($matches) {
            $expression = trim($matches[1]);
            
            // Replace variables in expression
            foreach ($this->variables as $var_name => $var_value) {
                if (is_numeric($var_value)) {
                    $expression = preg_replace('/\b' . preg_quote($var_name, '/') . '\b/', $var_value, $expression);
                }
            }
            
            // Replace dataset fields in expression
            if ($this->current_dataset) {
                foreach ($this->current_dataset as $field_name => $field_value) {
                    if (is_numeric($field_value)) {
                        $expression = preg_replace('/\b' . preg_quote($field_name, '/') . '\b/', $field_value, $expression);
                    }
                }
            }
            
            // Evaluate safe mathematical expression
            try {
                $result = $this->evaluate_math_expression($expression);
                return number_format($result, 2, '.', '');
            } catch (Exception $e) {
                return $matches[0]; // Return original if evaluation fails
            }
        }, $content);
    }
    
    /**
     * Safely evaluate mathematical expression
     */
    private function evaluate_math_expression($expression) {
        // Only allow safe mathematical operations and functions
        $allowed_chars = '0123456789+-*/.() ';
        $allowed_functions = array('abs', 'ceil', 'floor', 'round', 'sqrt', 'pow', 'min', 'max');
        
        // Remove any disallowed characters
        $cleaned_expression = '';
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];
            if (strpos($allowed_chars, $char) !== false) {
                $cleaned_expression .= $char;
            } elseif (ctype_alpha($char)) {
                // Check if it's part of an allowed function
                $remaining = substr($expression, $i);
                foreach ($allowed_functions as $func) {
                    if (strpos($remaining, $func) === 0) {
                        $cleaned_expression .= $func;
                        $i += strlen($func) - 1;
                        break;
                    }
                }
            }
        }
        
        // Remove extra spaces
        $cleaned_expression = preg_replace('/\s+/', '', $cleaned_expression);
        
        if (empty($cleaned_expression)) {
            return 0;
        }
        
        // Handle division by zero
        if (strpos($cleaned_expression, '/0') !== false) {
            return 0;
        }
        
        // Use eval with extreme caution - only for mathematical expressions
        $result = @eval("return $cleaned_expression;");
        
        return is_numeric($result) ? $result : 0;
    }
    
    /**
     * Process conditional expressions in content
     */
    private function process_conditional_expressions($content) {
        // Pattern for conditional expressions: {if condition}content{endif}
        $pattern = '/\{if\s+([^}]+)\}(.*?)\{endif\}/s';
        
        return preg_replace_callback($pattern, function($matches) {
            $condition = trim($matches[1]);
            $inner_content = $matches[2];
            
            // Handle {else} within the content
            if (strpos($inner_content, '{else}') !== false) {
                list($true_content, $false_content) = explode('{else}', $inner_content, 2);
            } else {
                $true_content = $inner_content;
                $false_content = '';
            }
            
            if ($this->conditional_logic->evaluate_simple_condition($condition, array_merge($this->current_dataset, $this->variables))) {
                return $this->parse_dynamic_content($true_content);
            } else {
                return $this->parse_dynamic_content($false_content);
            }
        }, $content);
    }
    
    /**
     * Process date formatting expressions
     */
    private function process_date_expressions($content) {
        // Pattern for date expressions: {date:format:value}
        $pattern = '/\{date:([^:}]+)(?::([^}]+))?\}/';
        
        return preg_replace_callback($pattern, function($matches) {
            $format = $matches[1];
            $value = isset($matches[2]) ? $matches[2] : 'now';
            
            // Replace variables in value
            $value = $this->parse_dynamic_content('{' . $value . '}');
            
            // Parse date
            if ($value === 'now') {
                $timestamp = time();
            } elseif (is_numeric($value)) {
                $timestamp = intval($value);
            } else {
                $timestamp = strtotime($value);
            }
            
            if ($timestamp === false) {
                return $matches[0]; // Return original if parsing fails
            }
            
            return date($format, $timestamp);
        }, $content);
    }
    
    /**
     * Process number formatting expressions
     */
    private function process_number_expressions($content) {
        // Pattern for number expressions: {number:format:decimals:value}
        $pattern = '/\{number:([^:}]+):([^:}]+):([^}]+)\}/';
        
        return preg_replace_callback($pattern, function($matches) {
            $format = $matches[1]; // currency, percentage, decimal
            $decimals = intval($matches[2]);
            $value = $this->parse_dynamic_content('{' . $matches[3] . '}');
            
            $number = floatval($value);
            
            switch ($format) {
                case 'currency':
                    $currency_symbol = get_woocommerce_currency_symbol() ?? '$';
                    return $currency_symbol . number_format($number, $decimals, '.', ',');
                    
                case 'percentage':
                    return number_format($number, $decimals, '.', ',') . '%';
                    
                case 'decimal':
                default:
                    return number_format($number, $decimals, '.', ',');
            }
        }, $content);
    }
    
    /**
     * Apply field mapping to element
     */
    private function apply_field_mapping($element) {
        $mapping = $element['field_mapping'];
        
        foreach ($mapping as $property => $field_name) {
            if (isset($this->current_dataset[$field_name])) {
                $value = $this->current_dataset[$field_name];
                
                // Apply formatting if specified
                if (isset($mapping['formatting'][$property])) {
                    $value = $this->apply_formatting($value, $mapping['formatting'][$property]);
                }
                
                $element[$property] = $value;
            }
        }
        
        return $element;
    }
    
    /**
     * Apply formatting to field value
     */
    private function apply_formatting($value, $formatting) {
        switch ($formatting['type']) {
            case 'currency':
                $symbol = $formatting['symbol'] ?? '$';
                $decimals = $formatting['decimals'] ?? 2;
                $decimal_sep = $formatting['decimal_separator'] ?? '.';
                $thousands_sep = $formatting['thousands_separator'] ?? ',';
                return $symbol . number_format(floatval($value), $decimals, $decimal_sep, $thousands_sep);
                
            case 'date':
                $format = $formatting['format'] ?? 'Y-m-d';
                if (is_numeric($value)) {
                    return date($format, $value);
                } else {
                    $timestamp = strtotime($value);
                    return $timestamp ? date($format, $timestamp) : $value;
                }
                
            case 'number':
                $decimals = $formatting['decimals'] ?? 0;
                $decimal_sep = $formatting['decimal_separator'] ?? '.';
                $thousands_sep = $formatting['thousands_separator'] ?? ',';
                return number_format(floatval($value), $decimals, $decimal_sep, $thousands_sep);
                
            case 'percentage':
                $decimals = $formatting['decimals'] ?? 2;
                return number_format(floatval($value), $decimals) . '%';
                
            case 'uppercase':
                return strtoupper($value);
                
            case 'lowercase':
                return strtolower($value);
                
            case 'capitalize':
                return ucwords($value);
                
            case 'title_case':
                return ucwords(strtolower($value));
                
            case 'truncate':
                $length = $formatting['length'] ?? 100;
                $suffix = $formatting['suffix'] ?? '...';
                return strlen($value) > $length ? substr($value, 0, $length) . $suffix : $value;
                
            case 'strip_tags':
                return strip_tags($value);
                
            case 'nl2br':
                return nl2br($value);
                
            case 'replace':
                $search = $formatting['search'] ?? '';
                $replace = $formatting['replace'] ?? '';
                return str_replace($search, $replace, $value);
                
            case 'regex_replace':
                $pattern = $formatting['pattern'] ?? '';
                $replacement = $formatting['replacement'] ?? '';
                return preg_replace($pattern, $replacement, $value);
                
            default:
                return $value;
        }
    }
    
    /**
     * Process calculations on element
     */
    private function process_calculations($element) {
        $calculations = $element['calculations'];
        
        foreach ($calculations as $property => $calculation) {
            $result = $this->evaluate_calculation($calculation);
            $element[$property] = $result;
        }
        
        return $element;
    }
    
    /**
     * Evaluate calculation
     */
    private function evaluate_calculation($calculation) {
        $expression = $calculation['expression'] ?? '';
        
        // Replace placeholders in expression
        foreach ($this->current_dataset as $key => $value) {
            if (is_numeric($value)) {
                $expression = str_replace('{' . $key . '}', $value, $expression);
            }
        }
        
        foreach ($this->variables as $key => $value) {
            if (is_numeric($value)) {
                $expression = str_replace('{' . $key . '}', $value, $expression);
            }
        }
        
        return $this->evaluate_math_expression($expression);
    }
    
    /**
     * Apply dynamic styling
     */
    private function apply_dynamic_styling($element) {
        $dynamic_style = $element['dynamic_style'];
        
        foreach ($dynamic_style as $property => $rules) {
            foreach ($rules as $rule) {
                if ($this->conditional_logic->evaluate_conditions($rule['condition'], $this->current_dataset)) {
                    $element[$property] = $rule['value'];
                    break; // Apply first matching rule
                }
            }
        }
        
        return $element;
    }
    
    /**
     * Calculate dynamic position
     */
    private function calculate_dynamic_position($element) {
        // This could implement auto-layout logic
        // For now, just return the element unchanged
        return $element;
    }
    
    /**
     * Execute element script
     */
    private function execute_element_script($element) {
        // This could implement a safe scripting environment
        // For now, just return the element unchanged
        return $element;
    }
    
    /**
     * Create enhanced PDF with advanced features
     */
    private function create_enhanced_pdf($template, $filename, $options) {
        try {
            // Use the parent's create_pdf method but with enhanced HTML
            $html = $this->build_enhanced_html($template, $options);
            
            // Apply advanced PDF options
            $pdf_path = $this->create_pdf_with_options($html, $filename, $options);
            
            if ($pdf_path) {
                // Apply post-processing options
                $pdf_path = $this->apply_pdf_post_processing($pdf_path, $options);
                return $this->get_pdf_url($pdf_path);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('Reverse2PDF Enhanced Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Build enhanced HTML with all features
     */
    private function build_enhanced_html($template, $options) {
        $template_data = json_decode($template->template_data, true);
        
        $html = '<!DOCTYPE html>';
        $html .= '<html' . ($options['rtl_support'] ? ' dir="rtl"' : '') . '>';
        $html .= '<head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<title>' . esc_html($template->name) . '</title>';
        $html .= $this->get_enhanced_css($options);
        $html .= '</head>';
        $html .= '<body>';
        
                // Add header if specified
        if (!empty($options['header'])) {
            $html .= '<div class="pdf-header">' . $this->parse_dynamic_content($options['header']) . '</div>';
        }
        
        // Process pages
        if (isset($template_data['pages'])) {
            foreach ($template_data['pages'] as $page_index => $page) {
                $html .= '<div class="pdf-page" data-page="' . ($page_index + 1) . '">';
                
                // Add watermark if specified
                if (!empty($options['watermark'])) {
                    $html .= '<div class="pdf-watermark">' . esc_html($options['watermark']) . '</div>';
                }
                
                // Process page elements
                if (isset($page['elements'])) {
                    foreach ($page['elements'] as $element) {
                        $html .= $this->render_element($element, 0, $this->current_dataset);
                    }
                }
                
                // Add page numbering if enabled
                if ($options['page_numbering']) {
                    $html .= '<div class="page-number">Page ' . ($page_index + 1) . ' of ' . count($template_data['pages']) . '</div>';
                }
                
                $html .= '</div>';
                
                // Add page break except for last page
                if ($page_index < count($template_data['pages']) - 1) {
                    $html .= '<div class="page-break"></div>';
                }
            }
        }
        
        // Add footer if specified
        if (!empty($options['footer'])) {
            $html .= '<div class="pdf-footer">' . $this->parse_dynamic_content($options['footer']) . '</div>';
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Get enhanced CSS with advanced styling
     */
    private function get_enhanced_css($options) {
        $css = '<style>
            @page {
                margin: ' . ($options['margin'] ?? $this->settings['margin_top'] ?? '20mm') . ';
                size: ' . ($this->settings['paper_size'] ?? 'A4') . ' ' . ($this->settings['paper_orientation'] ?? 'portrait') . ';
            }
            
            body {
                font-family: ' . ($options['default_font'] ?? $this->settings['default_font'] ?? 'Arial, sans-serif') . ';
                font-size: ' . ($options['base_font_size'] ?? $this->settings['base_font_size'] ?? '12px') . ';
                line-height: ' . ($options['line_height'] ?? $this->settings['line_height'] ?? '1.4') . ';
                color: ' . ($options['text_color'] ?? '#333') . ';
                margin: 0;
                padding: 0;
                direction: ' . ($options['rtl_support'] ? 'rtl' : 'ltr') . ';
            }
            
            .pdf-page {
                position: relative;
                min-height: 100vh;
                width: 100%;
                page-break-after: always;
                overflow: hidden;
            }
            
            .pdf-page:last-child {
                page-break-after: avoid;
            }
            
            .page-break {
                page-break-before: always;
                height: 0;
            }
            
            .pdf-header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: ' . ($options['header_height'] ?? '30mm') . ';
                background: ' . ($options['header_bg'] ?? 'transparent') . ';
                padding: 10px;
                border-bottom: ' . ($options['header_border'] ?? 'none') . ';
                z-index: 1000;
            }
            
            .pdf-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: ' . ($options['footer_height'] ?? '20mm') . ';
                background: ' . ($options['footer_bg'] ?? 'transparent') . ';
                padding: 10px;
                border-top: ' . ($options['footer_border'] ?? 'none') . ';
                z-index: 1000;
            }
            
            .pdf-watermark {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: ' . ($options['watermark_size'] ?? '48px') . ';
                color: ' . ($options['watermark_color'] ?? 'rgba(0,0,0,0.1)') . ';
                z-index: -1;
                pointer-events: none;
                font-weight: bold;
                white-space: nowrap;
            }
            
            .page-number {
                position: absolute;
                bottom: 10px;
                right: 10px;
                font-size: 10px;
                color: #666;
            }
            
            /* Enhanced element styles */
            .element-text {
                position: absolute;
                word-wrap: break-word;
                overflow-wrap: break-word;
                hyphens: auto;
            }
            
            .element-image {
                position: absolute;
                max-width: 100%;
                height: auto;
            }
            
            .element-field {
                position: absolute;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            
            .element-table {
                position: absolute;
                border-collapse: collapse;
                width: 100%;
            }
            
            .element-table th,
            .element-table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
                vertical-align: top;
                word-wrap: break-word;
            }
            
            .element-table th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            
            .element-line {
                position: absolute;
                display: block;
            }
            
            .element-rectangle {
                position: absolute;
                display: block;
            }
            
            .element-qr,
            .element-barcode {
                position: absolute;
                max-width: 100%;
                height: auto;
            }
            
            /* RTL support */
            .rtl {
                direction: rtl;
                text-align: right;
            }
            
            .rtl .element-text {
                text-align: right;
            }
            
            /* Print optimizations */
            @media print {
                body {
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                }
                
                .pdf-page {
                    page-break-inside: avoid;
                }
            }
            
            /* High DPI support */
            @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
                .element-image,
                .element-qr,
                .element-barcode {
                    image-rendering: -webkit-optimize-contrast;
                    image-rendering: crisp-edges;
                }
            }
        </style>';
        
        // Add custom CSS if provided
        if (isset($options['custom_css']) && !empty($options['custom_css'])) {
            $css .= '<style>' . $options['custom_css'] . '</style>';
        }
        
        return $css;
    }
    
    /**
     * Create PDF with advanced options
     */
    private function create_pdf_with_options($html, $filename, $options) {
        $pdf_engine = $this->settings['pdf_engine'] ?? 'dompdf';
        
        switch ($pdf_engine) {
            case 'dompdf':
                return $this->create_enhanced_dompdf($html, $filename, $options);
            case 'tcpdf':
                return $this->create_enhanced_tcpdf($html, $filename, $options);
            case 'mpdf':
                return $this->create_enhanced_mpdf($html, $filename, $options);
            default:
                return $this->create_enhanced_dompdf($html, $filename, $options);
        }
    }
    
    /**
     * Create PDF using DomPDF with enhanced features
     */
    private function create_enhanced_dompdf($html, $filename, $options) {
        if (!class_exists('Dompdf\Dompdf')) {
            return $this->create_html_fallback($html, $filename);
        }
        
        use Dompdf\Dompdf;
        use Dompdf\Options;
        
        $dompdf_options = new Options();
        $dompdf_options->set('defaultFont', $options['default_font'] ?? $this->settings['default_font'] ?? 'Arial');
        $dompdf_options->set('isHtml5ParserEnabled', true);
        $dompdf_options->set('isRemoteEnabled', true);
        $dompdf_options->set('debugPng', $this->settings['enable_debug'] ?? false);
        $dompdf_options->set('debugKeepTemp', false);
        $dompdf_options->set('debugCss', $this->settings['enable_debug'] ?? false);
        $dompdf_options->set('isPhpEnabled', false);
        $dompdf_options->set('dpi', intval($options['image_dpi'] ?? $this->settings['image_dpi'] ?? 150));
        
        if ($options['font_subsetting'] ?? $this->settings['font_subsetting'] ?? true) {
            $dompdf_options->set('fontSubsetting', true);
        }
        
        if ($options['compression'] ?? $this->settings['enable_compression'] ?? true) {
            $dompdf_options->set('isJavascriptEnabled', false);
        }
        
        $dompdf = new Dompdf($dompdf_options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper(
            $this->settings['paper_size'] ?? 'A4', 
            $this->settings['paper_orientation'] ?? 'portrait'
        );
        
        $dompdf->render();
        
        $pdf_content = $dompdf->output();
        
        // Save to file
        $pdf_dir = $this->upload_dir['basedir'] . '/' . ($this->settings['upload_folder'] ?? 'reverse2pdf') . '/';
        
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }
        
        $pdf_filename = sanitize_file_name($filename . '_enhanced_' . time() . '.pdf');
        $pdf_path = $pdf_dir . $pdf_filename;
        
        file_put_contents($pdf_path, $pdf_content);
        
        return $pdf_path;
    }
    
    /**
     * Apply PDF post-processing options
     */
    private function apply_pdf_post_processing($pdf_path, $options) {
        // This would handle password protection, digital signatures, etc.
        // For now, just return the original path
        return $pdf_path;
    }
    
    /**
     * Load dataset for enhanced processing
     */
    private function load_dataset($dataset_id, $template_id, $form_data = array()) {
        $dataset = array();
        
        // Add form data
        if (!empty($form_data)) {
            $dataset = array_merge($dataset, $form_data);
        }
        
        // Add post data if available
        if ($dataset_id) {
            $post = get_post($dataset_id);
            if ($post) {
                $dataset = array_merge($dataset, array(
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'post_content' => apply_filters('the_content', $post->post_content),
                    'post_excerpt' => $post->post_excerpt,
                    'post_date' => $post->post_date,
                    'post_author' => get_the_author_meta('display_name', $post->post_author),
                    'post_status' => $post->post_status,
                    'post_type' => $post->post_type
                ));
                
                // Add post meta
                $meta_fields = get_post_meta($post->ID);
                foreach ($meta_fields as $key => $value) {
                    $dataset[$key] = is_array($value) && count($value) === 1 ? $value[0] : $value;
                }
                
                // Add featured image
                $featured_image = get_the_post_thumbnail_url($post->ID, 'full');
                if ($featured_image) {
                    $dataset['featured_image'] = $featured_image;
                }
                
                // Add categories and tags
                $categories = get_the_category($post->ID);
                if ($categories) {
                    $dataset['post_categories'] = array_map(function($cat) { return $cat->name; }, $categories);
                    $dataset['post_category'] = $categories[0]->name;
                }
                
                $tags = get_the_tags($post->ID);
                if ($tags) {
                    $dataset['post_tags'] = array_map(function($tag) { return $tag->name; }, $tags);
                }
            }
        }
        
        // Add user data
        $current_user = wp_get_current_user();
        if ($current_user->ID) {
            $dataset = array_merge($dataset, array(
                'current_user_id' => $current_user->ID,
                'current_user_login' => $current_user->user_login,
                'current_user_email' => $current_user->user_email,
                'current_user_display_name' => $current_user->display_name,
                'current_user_first_name' => $current_user->first_name,
                'current_user_last_name' => $current_user->last_name,
                'current_user_roles' => $current_user->roles
            ));
            
            // Add user meta
            $user_meta = get_user_meta($current_user->ID);
            foreach ($user_meta as $key => $value) {
                $dataset['user_' . $key] = is_array($value) && count($value) === 1 ? $value[0] : $value;
            }
        }
        
        return $dataset;
    }
    
    /**
     * Load global variables
     */
    private function load_global_variables() {
        $variables = array(
            'site_name' => get_bloginfo('name'),
            'site_url' => get_bloginfo('url'),
            'site_description' => get_bloginfo('description'),
            'admin_email' => get_option('admin_email'),
            'current_date' => current_time('Y-m-d'),
            'current_time' => current_time('H:i:s'),
            'current_datetime' => current_time('Y-m-d H:i:s'),
            'current_year' => current_time('Y'),
            'current_month' => current_time('m'),
            'current_month_name' => current_time('F'),
            'current_day' => current_time('j'),
            'current_weekday' => current_time('l'),
            'current_timestamp' => current_time('timestamp')
        );
        
        // Add WooCommerce data if available
        if (class_exists('WooCommerce')) {
            $variables['wc_currency'] = get_woocommerce_currency();
            $variables['wc_currency_symbol'] = get_woocommerce_currency_symbol();
            $variables['wc_shop_url'] = get_permalink(wc_get_page_id('shop'));
            $variables['wc_cart_url'] = wc_get_cart_url();
            $variables['wc_checkout_url'] = wc_get_checkout_url();
            $variables['wc_account_url'] = wc_get_page_permalink('myaccount');
        }
        
        return $variables;
    }
}
?>
