<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Conditional_Logic {
    
    private $operators = array();
    private $functions = array();
    
    public function __construct() {
        $this->init_operators();
        $this->init_functions();
    }
    
    /**
     * Initialize supported operators
     */
    private function init_operators() {
        $this->operators = array(
            '=' => array('name' => 'Equals', 'callback' => array($this, 'op_equals')),
            '!=' => array('name' => 'Not Equals', 'callback' => array($this, 'op_not_equals')),
            '>' => array('name' => 'Greater Than', 'callback' => array($this, 'op_greater_than')),
            '<' => array('name' => 'Less Than', 'callback' => array($this, 'op_less_than')),
            '>=' => array('name' => 'Greater Than or Equal', 'callback' => array($this, 'op_greater_equal')),
            '<=' => array('name' => 'Less Than or Equal', 'callback' => array($this, 'op_less_equal')),
            'contains' => array('name' => 'Contains', 'callback' => array($this, 'op_contains')),
            'not_contains' => array('name' => 'Does Not Contain', 'callback' => array($this, 'op_not_contains')),
            'starts_with' => array('name' => 'Starts With', 'callback' => array($this, 'op_starts_with')),
            'ends_with' => array('name' => 'Ends With', 'callback' => array($this, 'op_ends_with')),
            'empty' => array('name' => 'Is Empty', 'callback' => array($this, 'op_empty')),
            'not_empty' => array('name' => 'Is Not Empty', 'callback' => array($this, 'op_not_empty')),
            'in' => array('name' => 'In List', 'callback' => array($this, 'op_in')),
            'not_in' => array('name' => 'Not In List', 'callback' => array($this, 'op_not_in')),
            'regex' => array('name' => 'Matches Regex', 'callback' => array($this, 'op_regex')),
            'between' => array('name' => 'Between', 'callback' => array($this, 'op_between'))
        );
    }
    
    /**
     * Initialize built-in functions
     */
    private function init_functions() {
        $this->functions = array(
            'length' => array($this, 'func_length'),
            'upper' => array($this, 'func_upper'),
            'lower' => array($this, 'func_lower'),
            'trim' => array($this, 'func_trim'),
            'date' => array($this, 'func_date'),
            'number' => array($this, 'func_number'),
            'round' => array($this, 'func_round'),
            'abs' => array($this, 'func_abs'),
            'sum' => array($this, 'func_sum'),
            'average' => array($this, 'func_average'),
            'count' => array($this, 'func_count'),
            'unique' => array($this, 'func_unique')
        );
    }
    
    /**
     * Evaluate conditions array
     */
    public function evaluate_conditions($conditions, $dataset) {
        if (!is_array($conditions) || empty($conditions)) {
            return true;
        }
        
        $logic = isset($conditions['logic']) ? $conditions['logic'] : 'and';
        $rules = isset($conditions['rules']) ? $conditions['rules'] : array();
        
        if (empty($rules)) {
            return true;
        }
        
        $results = array();
        
        foreach ($rules as $rule) {
            if (isset($rule['rules'])) {
                // Nested group
                $results[] = $this->evaluate_conditions($rule, $dataset);
            } else {
                // Single rule
                $results[] = $this->evaluate_single_condition($rule, $dataset);
            }
        }
        
        // Apply logic operator
        if ($logic === 'or') {
            return in_array(true, $results);
        } else {
            return !in_array(false, $results);
        }
    }
    
    /**
     * Evaluate single condition
     */
    private function evaluate_single_condition($condition, $dataset) {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? '';
        $case_sensitive = $condition['case_sensitive'] ?? false;
        
        if (empty($field)) {
            return false;
        }
        
        // Get field value from dataset
        $field_value = $this->get_field_value($field, $dataset);
        
        // Apply functions to field value if specified
        if (isset($condition['field_function'])) {
            $field_value = $this->apply_function($condition['field_function'], $field_value, $dataset);
        }
        
        // Apply functions to comparison value if specified
        if (isset($condition['value_function'])) {
            $value = $this->apply_function($condition['value_function'], $value, $dataset);
        }
        
        // Handle case sensitivity
        if (!$case_sensitive && is_string($field_value) && is_string($value)) {
            $field_value = strtolower($field_value);
            $value = strtolower($value);
        }
        
        // Evaluate using operator
        if (isset($this->operators[$operator])) {
            return call_user_func($this->operators[$operator]['callback'], $field_value, $value, $condition);
        }
        
        return false;
    }
    
    /**
     * Get field value from dataset with support for nested fields
     */
    private function get_field_value($field, $dataset) {
        // Handle nested field notation (e.g., "user.name" or "items[0].price")
        if (strpos($field, '.') !== false || strpos($field, '[') !== false) {
            return $this->get_nested_value($field, $dataset);
        }
        
        // Direct field access
        return isset($dataset[$field]) ? $dataset[$field] : '';
    }
    
    /**
     * Get nested value from dataset
     */
    private function get_nested_value($path, $data) {
        $keys = $this->parse_field_path($path);
        $current = $data;
        
        foreach ($keys as $key) {
            if (is_array($current) && isset($current[$key])) {
                $current = $current[$key];
            } elseif (is_object($current) && isset($current->$key)) {
                $current = $current->$key;
            } else {
                return '';
            }
        }
        
        return $current;
    }
    
    /**
     * Parse field path into keys array
     */
    private function parse_field_path($path) {
        $keys = array();
        $current_key = '';
        $in_brackets = false;
        
        for ($i = 0; $i < strlen($path); $i++) {
            $char = $path[$i];
            
            switch ($char) {
                case '.':
                    if (!$in_brackets && !empty($current_key)) {
                        $keys[] = $current_key;
                        $current_key = '';
                    } else {
                        $current_key .= $char;
                    }
                    break;
                    
                case '[':
                    if (!empty($current_key)) {
                        $keys[] = $current_key;
                        $current_key = '';
                    }
                    $in_brackets = true;
                    break;
                    
                case ']':
                    if ($in_brackets && !empty($current_key)) {
                        $keys[] = is_numeric($current_key) ? intval($current_key) : $current_key;
                        $current_key = '';
                    }
                    $in_brackets = false;
                    break;
                    
                default:
                    $current_key .= $char;
                    break;
            }
        }
        
        if (!empty($current_key)) {
            $keys[] = $current_key;
        }
        
        return $keys;
    }
    
    /**
     * Apply function to value
     */
    private function apply_function($function_name, $value, $dataset) {
        if (isset($this->functions[$function_name])) {
            return call_user_func($this->functions[$function_name], $value, $dataset);
        }
        
        return $value;
    }
    
    // Operator implementations
    
    public function op_equals($field_value, $value, $condition) {
        return $field_value == $value;
    }
    
    public function op_not_equals($field_value, $value, $condition) {
        return $field_value != $value;
    }
    
    public function op_greater_than($field_value, $value, $condition) {
        return is_numeric($field_value) && is_numeric($value) && floatval($field_value) > floatval($value);
    }
    
    public function op_less_than($field_value, $value, $condition) {
        return is_numeric($field_value) && is_numeric($value) && floatval($field_value) < floatval($value);
    }
    
    public function op_greater_equal($field_value, $value, $condition) {
        return is_numeric($field_value) && is_numeric($value) && floatval($field_value) >= floatval($value);
    }
    
    public function op_less_equal($field_value, $value, $condition) {
        return is_numeric($field_value) && is_numeric($value) && floatval($field_value) <= floatval($value);
    }
    
    public function op_contains($field_value, $value, $condition) {
        return strpos($field_value, $value) !== false;
    }
    
    public function op_not_contains($field_value, $value, $condition) {
        return strpos($field_value, $value) === false;
    }
    
    public function op_starts_with($field_value, $value, $condition) {
        return strpos($field_value, $value) === 0;
    }
    
    public function op_ends_with($field_value, $value, $condition) {
        return substr($field_value, -strlen($value)) === $value;
    }
    
    public function op_empty($field_value, $value, $condition) {
        return empty($field_value);
    }
    
    public function op_not_empty($field_value, $value, $condition) {
        return !empty($field_value);
    }
    
    public function op_in($field_value, $value, $condition) {
        $list = is_array($value) ? $value : array_map('trim', explode(',', $value));
        return in_array($field_value, $list);
    }
    
    public function op_not_in($field_value, $value, $condition) {
        $list = is_array($value) ? $value : array_map('trim', explode(',', $value));
        return !in_array($field_value, $list);
    }
    
    public function op_regex($field_value, $value, $condition) {
        return preg_match('/' . $value . '/', $field_value) === 1;
    }
    
    public function op_between($field_value, $value, $condition) {
        if (!is_numeric($field_value)) {
            return false;
        }
        
        $range = is_array($value) ? $value : array_map('trim', explode(',', $value));
        if (count($range) !== 2) {
            return false;
        }
        
        $min = floatval($range[0]);
        $max = floatval($range[1]);
        $field_val = floatval($field_value);
        
        return $field_val >= $min && $field_val <= $max;
    }
    
    // Function implementations
    
    public function func_length($value, $dataset) {
        return strlen($value);
    }
    
    public function func_upper($value, $dataset) {
        return strtoupper($value);
    }
    
    public function func_lower($value, $dataset) {
        return strtolower($value);
    }
    
    public function func_trim($value, $dataset) {
        return trim($value);
    }
    
    public function func_date($value, $dataset) {
        if (is_numeric($value)) {
            return date('Y-m-d', intval($value));
        }
        
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : $value;
    }
    
    public function func_number($value, $dataset) {
        return is_numeric($value) ? floatval($value) : 0;
    }
    
    public function func_round($value, $dataset) {
        return is_numeric($value) ? round(floatval($value)) : 0;
    }
    
    public function func_abs($value, $dataset) {
        return is_numeric($value) ? abs(floatval($value)) : 0;
    }
    
    public function func_sum($value, $dataset) {
        if (is_array($value)) {
            return array_sum(array_filter($value, 'is_numeric'));
        }
        
        return is_numeric($value) ? floatval($value) : 0;
    }
    
    public function func_average($value, $dataset) {
        if (is_array($value)) {
            $numbers = array_filter($value, 'is_numeric');
            return count($numbers) > 0 ? array_sum($numbers) / count($numbers) : 0;
        }
        
        return is_numeric($value) ? floatval($value) : 0;
    }
    
    public function func_count($value, $dataset) {
        if (is_array($value)) {
            return count($value);
        }
        
        return strlen($value);
    }
    
    public function func_unique($value, $dataset) {
        if (is_array($value)) {
            return array_unique($value);
        }
        
        return $value;
    }
    
    /**
     * Get available operators
     */
    public function get_operators() {
        return $this->operators;
    }
    
    /**
     * Get available functions
     */
    public function get_functions() {
        return $this->functions;
    }
    
    /**
     * Validate condition structure
     */
    public function validate_conditions($conditions) {
        if (!is_array($conditions)) {
            return false;
        }
        
        if (isset($conditions['rules'])) {
            foreach ($conditions['rules'] as $rule) {
                if (!$this->validate_single_rule($rule)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Validate single rule
     */
    private function validate_single_rule($rule) {
        if (isset($rule['rules'])) {
            // Nested rules
            return $this->validate_conditions($rule);
        }
        
        // Single rule validation
        if (empty($rule['field']) || empty($rule['operator'])) {
            return false;
        }
        
        if (!isset($this->operators[$rule['operator']])) {
            return false;
        }
        
        return true;
    }
}
?>
