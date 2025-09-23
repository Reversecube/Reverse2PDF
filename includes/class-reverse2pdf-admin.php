<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_submenus'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    public function add_submenus() {
        // Templates page
        add_submenu_page(
            'reverse2pdf',
            'Templates',
            'Templates',
            'manage_options',
            'reverse2pdf-templates',
            array($this, 'templates_page')
        );
        
        // Settings page
        add_submenu_page(
            'reverse2pdf',
            'Settings',
            'Settings',
            'manage_options',
            'reverse2pdf-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_init() {
        // Register settings
        register_setting('reverse2pdf_settings', 'reverse2pdf_options');
    }
    
    public function templates_page() {
        ?>
        <div class="wrap">
            <h1>PDF Templates</h1>
            <p>Create and manage your PDF templates here.</p>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <a href="#" class="button button-primary">Add New Template</a>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">
                            <p>No templates found. Create your first template to get started.</p>
                            <a href="#" class="button button-primary">Create Template</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Reverse2PDF Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('reverse2pdf_settings');
                do_settings_sections('reverse2pdf_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">PDF Engine</th>
                        <td>
                            <select name="reverse2pdf_options[pdf_engine]">
                                <option value="dompdf">DomPDF (Recommended)</option>
                                <option value="tcpdf">TCPDF</option>
                                <option value="mpdf">mPDF</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Paper Size</th>
                        <td>
                            <select name="reverse2pdf_options[paper_size]">
                                <option value="A4">A4</option>
                                <option value="A3">A3</option>
                                <option value="Letter">Letter</option>
                                <option value="Legal">Legal</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Font</th>
                        <td>
                            <input type="text" name="reverse2pdf_options[default_font]" value="Arial" />
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
?>
