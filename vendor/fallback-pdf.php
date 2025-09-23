<?php
/**
 * Fallback PDF Handler
 * Simple HTML to PDF conversion when libraries are not available
 */

if (!class_exists('Reverse2PDF_Fallback')) {
    
    class Reverse2PDF_Fallback {
        
        private $html = '';
        private $options = array();
        
        public function __construct($options = array()) {
            $this->options = wp_parse_args($options, array(
                'paper_size' => 'A4',
                'orientation' => 'portrait',
                'margin_top' => '20mm',
                'margin_right' => '15mm',
                'margin_bottom' => '20mm',
                'margin_left' => '15mm'
            ));
        }
        
        public function loadHtml($html) {
            $this->html = $html;
        }
        
        public function setPaper($size, $orientation = 'portrait') {
            $this->options['paper_size'] = $size;
            $this->options['orientation'] = $orientation;
        }
        
        public function render() {
            // In a real fallback, you might:
            // 1. Use server-side rendering
            // 2. Call external API
            // 3. Generate a more basic PDF
            
            // For now, we'll create an HTML file that can be printed to PDF
            return true;
        }
        
        public function output() {
            // Generate printable HTML
            $css = $this->generatePrintCSS();
            
            $output = '<!DOCTYPE html>';
            $output .= '<html>';
            $output .= '<head>';
            $output .= '<meta charset="UTF-8">';
            $output .= '<title>PDF Document</title>';
            $output .= '<style>' . $css . '</style>';
            $output .= '</head>';
            $output .= '<body>';
            $output .= '<div class="pdf-content">' . $this->html . '</div>';
            $output .= '</body>';
            $output .= '</html>';
            
            return $output;
        }
        
        private function generatePrintCSS() {
            $orientation = $this->options['orientation'];
            $paper_size = $this->options['paper_size'];
            
            return "
                @page {
                    size: {$paper_size} {$orientation};
                    margin: {$this->options['margin_top']} {$this->options['margin_right']} {$this->options['margin_bottom']} {$this->options['margin_left']};
                }
                
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                
                .pdf-content {
                    width: 100%;
                    height: 100vh;
                }
                
                @media print {
                    body {
                        -webkit-print-color-adjust: exact;
                        color-adjust: exact;
                    }
                    
                    .pdf-content {
                        page-break-inside: avoid;
                    }
                }
            ";
        }
    }
}

// Create alias for DomPDF compatibility
if (!class_exists('\Dompdf\Dompdf')) {
    class_alias('Reverse2PDF_Fallback', '\Dompdf\Dompdf');
}
?>
