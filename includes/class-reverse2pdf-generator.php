<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Generator {
    
    private $settings;
    private $upload_dir;
    
    public function __construct() {
        $this->settings = get_option('reverse2pdf_settings', array());
        $this->upload_dir = wp_upload_dir();
    }
    
    /**
     * Generate PDF from template
     */
    public function generate_pdf($template_id, $dataset_id = 0, $options = array()) {
        try {
            // Load template
            $template = $this->load_template($template_id);
            if (!$template) {
                throw new Exception('Template not found');
            }
            
            // Load dataset
            $dataset = $this->load_dataset($dataset_id, $options);
            
            // Generate HTML
            $html = $this->build_html($template, $dataset);
            
            // Create PDF
            $pdf_path = $this->create_pdf($html, $template->name);
            
            if ($pdf_path) {
                return $this->get_pdf_url($pdf_path);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('Reverse2PDF Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Load template data
     */
    private function load_template($template_id) {
        global $wpdb;
        
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " WHERE id = %d AND active = 1",
            $template_id
        ));
        
        if ($template) {
            $template->template_data = json_decode($template->template_data, true);
            $template->settings = json_decode($template->settings ?: '{}', true);
        }
        
        return $template;
    }
    
    /**
     * Load dataset
     */
    private function load_dataset($dataset_id, $options = array()) {
        $dataset = array();
        
        // Add form data if provided
        if (isset($options['form_data'])) {
            $dataset = array_merge($dataset, $options['form_data']);
        }
        
        // Add post data if dataset_id provided
        if ($dataset_id) {
            $post = get_post($dataset_id);
            if ($post) {
                $dataset = array_merge($dataset, array(
                    'post_title' => $post->post_title,
                    'post_content' => $post->post_content,
                    'post_date' => $post->post_date,
                    'post_author' => get_the_author_meta('display_name', $post->post_author)
                ));
            }
        }
        
        // Add global data
        $dataset = array_merge($dataset, array(
            'site_name' => get_bloginfo('name'),
            'site_url' => get_bloginfo('url'),
            'current_date' => current_time('Y-m-d'),
            'current_time' => current_time('H:i:s')
        ));
        
        return $dataset;
    }
    
    /**
     * Build HTML from template
     */
    private function build_html($template, $dataset) {
        $html = '<!DOCTYPE html>';
        $html .= '<html><head><meta charset="UTF-8"><title>' . esc_html($template->name) . '</title>';
        $html .= $this->get_basic_css();
        $html .= '</head><body>';
        
        if (isset($template->template_data['pages'])) {
            foreach ($template->template_data['pages'] as $page) {
                $html .= '<div class="pdf-page">';
                
                if (isset($page['elements'])) {
                    foreach ($page['elements'] as $element) {
                        $html .= $this->render_element($element, $dataset);
                    }
                }
                
                $html .= '</div>';
            }
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Render element
     */
    private function render_element($element, $dataset) {
        $type = $element['type'] ?? 'text';
        $styles = $this->build_element_styles($element);
        
        switch ($type) {
            case 'text':
                $content = $this->process_placeholders($element['content'] ?? '', $dataset);
                return '<div style="' . $styles . '">' . nl2br(esc_html($content)) . '</div>';
                
            case 'image':
                $src = $this->process_placeholders($element['src'] ?? '', $dataset);
                return '<img src="' . esc_url($src) . '" style="' . $styles . '" />';
                
            case 'line':
                $thickness = $element['thickness'] ?? 1;
                $color = $element['color'] ?? '#000000';
                return '<div style="' . $styles . ' height: ' . $thickness . 'px; background-color: ' . $color . ';"></div>';
                
            case 'rectangle':
                return '<div style="' . $styles . '"></div>';
                
            default:
                return '<div style="' . $styles . '">Element: ' . $type . '</div>';
        }
    }
    
    /**
     * Process placeholders in content
     */
    private function process_placeholders($content, $dataset) {
        foreach ($dataset as $key => $value) {
            if (is_scalar($value)) {
                $content = str_replace('{' . $key . '}', $value, $content);
            }
        }
        return $content;
    }
    
    /**
     * Build element styles
     */
    private function build_element_styles($element) {
        $styles = array();
        
        if (isset($element['x'])) $styles[] = 'position: absolute; left: ' . intval($element['x']) . 'px';
        if (isset($element['y'])) $styles[] = 'top: ' . intval($element['y']) . 'px';
        if (isset($element['width'])) $styles[] = 'width: ' . intval($element['width']) . 'px';
        if (isset($element['height'])) $styles[] = 'height: ' . intval($element['height']) . 'px';
        if (isset($element['fontSize'])) $styles[] = 'font-size: ' . intval($element['fontSize']) . 'px';
        if (isset($element['fontFamily'])) $styles[] = 'font-family: ' . sanitize_text_field($element['fontFamily']);
        if (isset($element['color'])) $styles[] = 'color: ' . sanitize_hex_color($element['color']);
        if (isset($element['textAlign'])) $styles[] = 'text-align: ' . sanitize_text_field($element['textAlign']);
        
        return implode('; ', $styles);
    }
    
    /**
     * Get basic CSS
     */
    private function get_basic_css() {
        return '<style>
            body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
            .pdf-page { position: relative; width: 595px; height: 842px; margin: 0 auto; background: white; }
        </style>';
    }
    
    /**
     * Create PDF file
     */
    private function create_pdf($html, $filename) {
        // Use DomPDF as default
        if (!class_exists('\Dompdf\Dompdf')) {
            // Fallback to basic HTML to PDF if DomPDF not available
            return $this->create_html_file($html, $filename);
        }
        
        require_once REVERSE2PDF_PLUGIN_DIR . 'vendor/dompdf/dompdf/autoload.inc.php';
        
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $pdf_dir = $this->upload_dir['basedir'] . '/reverse2pdf/pdfs';
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }
        
        $pdf_filename = sanitize_file_name($filename . '_' . time() . '.pdf');
        $pdf_path = $pdf_dir . '/' . $pdf_filename;
        
        file_put_contents($pdf_path, $dompdf->output());
        
        return $pdf_path;
    }
    
    /**
     * Create HTML file as fallback
     */
    private function create_html_file($html, $filename) {
        $html_dir = $this->upload_dir['basedir'] . '/reverse2pdf/pdfs';
        if (!file_exists($html_dir)) {
            wp_mkdir_p($html_dir);
        }
        
        $html_filename = sanitize_file_name($filename . '_' . time() . '.html');
        $html_path = $html_dir . '/' . $html_filename;
        
        file_put_contents($html_path, $html);
        
        return $html_path;
    }
    
    /**
     * Get PDF URL
     */
    private function get_pdf_url($pdf_path) {
        return str_replace($this->upload_dir['basedir'], $this->upload_dir['baseurl'], $pdf_path);
    }
}
?>
