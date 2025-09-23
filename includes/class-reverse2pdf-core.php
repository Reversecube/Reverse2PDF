<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Core {
    
    private $version = '2.0.0';
    private $components = array();
    private $loaded_files = array();
    
    public function __construct() {
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
            'class-reverse2pdf-integrations.php'
        );
        
        foreach ($includes as $file) {
            $path = REVERSE2PDF_PLUGIN_DIR . 'includes/' . $file;
            if (file_exists($path)) {
                require_once $path;
                $this->loaded_files[] = $file;
            }
        }
    }
    
    private function init_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        add_action('wp_ajax_reverse2pdf_generate', array($this, 'ajax_generate_pdf'));
        add_action('wp_ajax_nopriv_reverse2pdf_generate', array($this, 'ajax_generate_pdf'));
    }
    
    private function init_components() {
        if (class_exists('Reverse2PDF_Admin')) {
            $this->components['admin'] = new Reverse2PDF_Admin();
        }
        
        if (class_exists('Reverse2PDF_Shortcodes')) {
            $this->components['shortcodes'] = new Reverse2PDF_Shortcodes();
        }
        
        if (class_exists('Reverse2PDF_Integrations')) {
            $this->components['integrations'] = new Reverse2PDF_Integrations();
        }
    }
    
    public function admin_scripts($hook) {
        if (strpos($hook, 'reverse2pdf') === false) {
            return;
        }
        
        wp_enqueue_style(
            'reverse2pdf-admin',
            REVERSE2PDF_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            REVERSE2PDF_VERSION
        );
        
        wp_enqueue_script(
            'reverse2pdf-admin',
            REVERSE2PDF_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            REVERSE2PDF_VERSION,
            true
        );
        
        wp_localize_script('reverse2pdf-admin', 'reverse2pdf_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reverse2pdf_nonce')
        ));
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
    }
    
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
}
?>
