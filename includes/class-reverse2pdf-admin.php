<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            'Reverse2PDF',
            'Reverse2PDF',
            'manage_options',
            'reverse2pdf',
            array($this, 'dashboard_page'),
            'dashicons-media-document',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'reverse2pdf',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'reverse2pdf',
            array($this, 'dashboard_page')
        );
        
        // Templates submenu
        add_submenu_page(
            'reverse2pdf',
            'Templates',
            'Templates',
            'manage_options',
            'reverse2pdf-templates',
            array($this, 'templates_page')
        );
        
        // Visual Builder submenu
        add_submenu_page(
            'reverse2pdf',
            'Visual Builder',
            'Visual Builder',
            'manage_options',
            'reverse2pdf-builder',
            array($this, 'builder_page')
        );
        
        // Integrations submenu
        add_submenu_page(
            'reverse2pdf',
            'Form Integrations',
            'Integrations',
            'manage_options',
            'reverse2pdf-integrations',
            array($this, 'integrations_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'reverse2pdf',
            'Settings',
            'Settings',
            'manage_options',
            'reverse2pdf-settings',
            array($this, 'settings_page')
        );
        
        // Logs submenu
        add_submenu_page(
            'reverse2pdf',
            'Logs',
            'Logs',
            'manage_options',
            'reverse2pdf-logs',
            array($this, 'logs_page')
        );
    }
    
    public function enqueue_admin_assets($hook) {
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
        
        // JavaScript
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_script('jquery-ui-resizable');
        wp_enqueue_script('jquery-ui-sortable');
        
        wp_enqueue_script(
            'reverse2pdf-admin',
            REVERSE2PDF_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-draggable'),
            REVERSE2PDF_VERSION,
            true
        );
        
        // Visual Builder scripts
        if (strpos($hook, 'reverse2pdf-builder') !== false) {
            wp_enqueue_script(
                'reverse2pdf-builder',
                REVERSE2PDF_PLUGIN_URL . 'assets/js/template-builder.js',
                array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'),
                REVERSE2PDF_VERSION,
                true
            );
        }
        
        wp_localize_script('reverse2pdf-admin', 'reverse2pdf_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reverse2pdf_nonce'),
            'plugin_url' => REVERSE2PDF_PLUGIN_URL,
            'strings' => array(
                'save' => 'Save',
                'saving' => 'Saving...',
                'saved' => 'Saved',
                'error' => 'Error',
                'confirm_delete' => 'Are you sure you want to delete this template?'
            )
        ));
    }
    
    public function admin_notices() {
        // Check for missing core files
        $missing_files = array();
        $required_files = array(
            'Enhanced Generator' => 'includes/class-reverse2pdf-enhanced-generator.php',
            'Shortcodes' => 'includes/class-reverse2pdf-shortcodes.php',
            'Integrations' => 'includes/class-reverse2pdf-integrations.php'
        );
        
        foreach ($required_files as $name => $file) {
            if (!file_exists(REVERSE2PDF_PLUGIN_DIR . $file)) {
                $missing_files[] = $name;
            }
        }
        
        if (!empty($missing_files)) {
            echo '<div class="notice notice-warning">';
            echo '<h3>⚠️ Reverse2PDF: Missing Components</h3>';
            echo '<p><strong>Missing:</strong> ' . implode(', ', $missing_files) . '</p>';
            echo '<p>Some features may not work properly. Please ensure all plugin files are uploaded correctly.</p>';
            echo '</div>';
        }
    }
    
    public function dashboard_page() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Reverse2PDF Dashboard</h1>
            
            <div class="reverse2pdf-dashboard">
                <div class="welcome-panel">
                    <div class="welcome-panel-content">
                        <h2>Welcome to Reverse2PDF Pro!</h2>
                        <p class="about-description">Create professional PDF documents with our powerful visual builder and form integrations.</p>
                        
                        <div class="welcome-panel-column-container">
                            <div class="welcome-panel-column">
                                <h3>🎨 Get Started</h3>
                                <ul>
                                    <li><a href="<?php echo admin_url('admin.php?page=reverse2pdf-builder'); ?>" class="button button-primary">Create New Template</a></li>
                                    <li><a href="<?php echo admin_url('admin.php?page=reverse2pdf-templates'); ?>">View Templates</a></li>
                                    <li><a href="<?php echo admin_url('admin.php?page=reverse2pdf-integrations'); ?>">Setup Form Integration</a></li>
                                </ul>
                            </div>
                            
                            <div class="welcome-panel-column">
                                <h3>📊 Quick Stats</h3>
                                <?php
                                global $wpdb;
                                $templates_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES);
                                $logs_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_LOGS . " WHERE DATE(created_date) = CURDATE()");
                                ?>
                                <ul>
                                    <li><strong><?php echo $templates_count; ?></strong> Templates Created</li>
                                    <li><strong><?php echo $logs_count; ?></strong> PDFs Generated Today</li>
                                    <li><strong><?php echo REVERSE2PDF_VERSION; ?></strong> Plugin Version</li>
                                </ul>
                            </div>
                            
                            <div class="welcome-panel-column">
                                <h3>🔧 System Status</h3>
                                <ul>
                                    <li><strong>PHP:</strong> <?php echo PHP_VERSION; ?> 
                                        <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? '✅' : '❌'; ?>
                                    </li>
                                    <li><strong>WordPress:</strong> <?php echo get_bloginfo('version'); ?> ✅</li>
                                    <li><strong>GD Extension:</strong> 
                                        <?php echo extension_loaded('gd') ? '✅ Available' : '⚠️ Missing'; ?>
                                    </li>
                                    <li><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="reverse2pdf-cards">
                    <div class="reverse2pdf-card">
                        <h3>📄 Recent Templates</h3>
                        <?php $this->render_recent_templates(); ?>
                    </div>
                    
                    <div class="reverse2pdf-card">
                        <h3>📈 Recent Activity</h3>
                        <?php $this->render_recent_activity(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function templates_page() {
        global $wpdb;
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;
        
        // Handle actions
        if (isset($_GET['action']) && isset($_GET['template_id'])) {
            $template_id = intval($_GET['template_id']);
            
            switch ($_GET['action']) {
                case 'delete':
                    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_template_' . $template_id)) {
                        $wpdb->delete($table, array('id' => $template_id), array('%d'));
                        echo '<div class="notice notice-success"><p>Template deleted successfully.</p></div>';
                    }
                    break;
                    
                case 'duplicate':
                    if (wp_verify_nonce($_GET['_wpnonce'], 'duplicate_template_' . $template_id)) {
                        $original = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $template_id));
                        if ($original) {
                            $wpdb->insert($table, array(
                                'name' => $original->name . ' (Copy)',
                                'description' => $original->description,
                                'template_data' => $original->template_data,
                                'settings' => $original->settings,
                                'created_by' => get_current_user_id()
                            ));
                            echo '<div class="notice notice-success"><p>Template duplicated successfully.</p></div>';
                        }
                    }
                    break;
            }
        }
        
        $templates = $wpdb->get_results("SELECT * FROM $table ORDER BY created_date DESC");
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">PDF Templates</h1>
            <a href="<?php echo admin_url('admin.php?page=reverse2pdf-builder'); ?>" class="page-title-action">Add New</a>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <button type="button" class="button" onclick="location.reload()">Refresh</button>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column">Name</th>
                        <th scope="col" class="manage-column">Description</th>
                        <th scope="col" class="manage-column">Form Integration</th>
                        <th scope="col" class="manage-column">Created</th>
                        <th scope="col" class="manage-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($templates): ?>
                        <?php foreach ($templates as $template): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=reverse2pdf-builder&template_id=' . $template->id); ?>">
                                            <?php echo esc_html($template->name); ?>
                                        </a>
                                    </strong>
                                    <?php if (!$template->active): ?>
                                        <span class="post-state">(Inactive)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($template->description); ?></td>
                                <td>
                                    <?php if ($template->form_type && $template->form_id): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                        <?php echo esc_html(ucfirst($template->form_type)) . ' #' . esc_html($template->form_id); ?>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-minus" style="color: #ccc;"></span>
                                        No integration
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($template->created_date); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=reverse2pdf-builder&template_id=' . $template->id); ?>" 
                                       class="button button-small">Edit</a>
                                    
                                    <button type="button" class="button button-small reverse2pdf-generate" 
                                            data-template-id="<?php echo $template->id; ?>">Generate PDF</button>
                                    
                                    <a href="<?php echo wp_nonce_url(
                                        admin_url('admin.php?page=reverse2pdf-templates&action=duplicate&template_id=' . $template->id),
                                        'duplicate_template_' . $template->id
                                    ); ?>" class="button button-small">Duplicate</a>
                                    
                                    <a href="<?php echo wp_nonce_url(
                                        admin_url('admin.php?page=reverse2pdf-templates&action=delete&template_id=' . $template->id),
                                        'delete_template_' . $template->id
                                    ); ?>" class="button button-small" 
                                       onclick="return confirm('Are you sure you want to delete this template?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <p>No templates found. <a href="<?php echo admin_url('admin.php?page=reverse2pdf-builder'); ?>">Create your first template</a>.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function builder_page() {
        $template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
        $template = null;
        
        if ($template_id) {
            global $wpdb;
            $template = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " WHERE id = %d",
                $template_id
            ));
        }
        ?>
        <div class="wrap">
            <h1><?php echo $template ? 'Edit Template' : 'Create New Template'; ?></h1>
            
            <div id="reverse2pdf-builder" class="reverse2pdf-builder">
                <div class="builder-header">
                    <div class="builder-controls">
                        <input type="text" id="template-name" placeholder="Template Name" 
                               value="<?php echo $template ? esc_attr($template->name) : ''; ?>" />
                        
                        <button type="button" id="save-template" class="button button-primary">
                            <span class="dashicons dashicons-yes"></span> Save Template
                        </button>
                        
                        <button type="button" id="preview-template" class="button">
                            <span class="dashicons dashicons-visibility"></span> Preview
                        </button>
                        
                        <button type="button" id="test-template" class="button">
                            <span class="dashicons dashicons-media-document"></span> Test PDF
                        </button>
                    </div>
                </div>
                
                <div class="builder-workspace">
                    <div class="builder-sidebar">
                        <div class="sidebar-section">
                            <h3>Elements</h3>
                            <div class="element-library">
                                <div class="element-group">
                                    <h4>Basic Elements</h4>
                                    <div class="element-item" data-type="text">
                                        <span class="dashicons dashicons-editor-textcolor"></span>
                                        Text
                                    </div>
                                    <div class="element-item" data-type="image">
                                        <span class="dashicons dashicons-format-image"></span>
                                        Image
                                    </div>
                                    <div class="element-item" data-type="line">
                                        <span class="dashicons dashicons-minus"></span>
                                        Line
                                    </div>
                                    <div class="element-item" data-type="rectangle">
                                        <span class="dashicons dashicons-admin-page"></span>
                                        Rectangle
                                    </div>
                                </div>
                                
                                <div class="element-group">
                                    <h4>Form Elements</h4>
                                    <div class="element-item" data-type="form-field">
                                        <span class="dashicons dashicons-edit"></span>
                                        Form Field
                                    </div>
                                    <div class="element-item" data-type="checkbox">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        Checkbox
                                    </div>
                                </div>
                                
                                <div class="element-group">
                                    <h4>Advanced</h4>
                                    <div class="element-item" data-type="table">
                                        <span class="dashicons dashicons-grid-view"></span>
                                        Table
                                    </div>
                                    <div class="element-item" data-type="qr-code">
                                        <span class="dashicons dashicons-screenoptions"></span>
                                        QR Code
                                    </div>
                                    <div class="element-item" data-type="barcode">
                                        <span class="dashicons dashicons-admin-links"></span>
                                        Barcode
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="sidebar-section">
                            <h3>Properties</h3>
                            <div id="element-properties">
                                <p>Select an element to edit its properties.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="builder-canvas">
                        <div class="canvas-container">
                            <div id="pdf-canvas" class="pdf-canvas">
                                <div class="page" data-page="1">
                                    <!-- Elements will be added here dynamically -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <input type="hidden" id="template-id" value="<?php echo $template_id; ?>" />
            <input type="hidden" id="template-data" value="<?php echo $template ? esc_attr($template->template_data) : ''; ?>" />
        </div>
        <?php
    }
    
    public function integrations_page() {
        ?>
        <div class="wrap">
            <h1>Form Integrations</h1>
            
            <div class="reverse2pdf-integrations">
                <div class="integration-cards">
                    <?php $this->render_integration_card('Contact Form 7', 'contact_form_7', 'WPCF7'); ?>
                    <?php $this->render_integration_card('Gravity Forms', 'gravity_forms', 'GFForms'); ?>
                    <?php $this->render_integration_card('WPForms', 'wpforms', 'WPForms'); ?>
                    <?php $this->render_integration_card('Formidable Forms', 'formidable', 'FrmForm'); ?>
                    <?php $this->render_integration_card('Ninja Forms', 'ninja_forms', 'Ninja_Forms'); ?>
                </div>
                
                <div class="integration-setup">
                    <h2>Setup New Integration</h2>
                    <form id="integration-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Form Plugin</th>
                                <td>
                                    <select id="form-type" name="form_type">
                                        <option value="">Select Form Plugin</option>
                                        <option value="contact_form_7">Contact Form 7</option>
                                        <option value="gravity_forms">Gravity Forms</option>
                                        <option value="wpforms">WPForms</option>
                                        <option value="formidable">Formidable Forms</option>
                                        <option value="ninja_forms">Ninja Forms</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Form</th>
                                <td>
                                    <select id="form-id" name="form_id">
                                        <option value="">Select a form</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Template</th>
                                <td>
                                    <select id="template-id" name="template_id">
                                        <option value="">Select a template</option>
                                        <?php
                                        global $wpdb;
                                        $templates = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " WHERE active = 1");
                                        foreach ($templates as $template) {
                                            echo '<option value="' . $template->id . '">' . esc_html($template->name) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">Setup Integration</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('reverse2pdf_settings');
            
            $settings = array(
                'pdf_engine' => sanitize_text_field($_POST['pdf_engine']),
                'paper_size' => sanitize_text_field($_POST['paper_size']),
                'paper_orientation' => sanitize_text_field($_POST['paper_orientation']),
                'default_font' => sanitize_text_field($_POST['default_font']),
                'enable_cache' => isset($_POST['enable_cache']),
                'enable_debug' => isset($_POST['enable_debug']),
                'auto_cleanup' => isset($_POST['auto_cleanup']),
                'cleanup_days' => intval($_POST['cleanup_days'])
            );
            
            update_option('reverse2pdf_settings', $settings);
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        
        $settings = get_option('reverse2pdf_settings', array());
        ?>
        <div class="wrap">
            <h1>Reverse2PDF Settings</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('reverse2pdf_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">PDF Engine</th>
                        <td>
                            <select name="pdf_engine">
                                <option value="dompdf" <?php selected($settings['pdf_engine'] ?? '', 'dompdf'); ?>>DomPDF</option>
                                <option value="tcpdf" <?php selected($settings['pdf_engine'] ?? '', 'tcpdf'); ?>>TCPDF</option>
                                <option value="mpdf" <?php selected($settings['pdf_engine'] ?? '', 'mpdf'); ?>>mPDF</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Paper Size</th>
                        <td>
                            <select name="paper_size">
                                <option value="A4" <?php selected($settings['paper_size'] ?? '', 'A4'); ?>>A4</option>
                                <option value="A3" <?php selected($settings['paper_size'] ?? '', 'A3'); ?>>A3</option>
                                <option value="Letter" <?php selected($settings['paper_size'] ?? '', 'Letter'); ?>>Letter</option>
                                <option value="Legal" <?php selected($settings['paper_size'] ?? '', 'Legal'); ?>>Legal</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Orientation</th>
                        <td>
                            <label>
                                <input type="radio" name="paper_orientation" value="portrait" <?php checked($settings['paper_orientation'] ?? '', 'portrait'); ?> />
                                Portrait
                            </label><br>
                            <label>
                                <input type="radio" name="paper_orientation" value="landscape" <?php checked($settings['paper_orientation'] ?? '', 'landscape'); ?> />
                                Landscape
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Font</th>
                        <td>
                            <input type="text" name="default_font" value="<?php echo esc_attr($settings['default_font'] ?? 'Arial'); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Caching</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_cache" <?php checked($settings['enable_cache'] ?? false); ?> />
                                Enable PDF caching for better performance
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Debug Mode</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_debug" <?php checked($settings['enable_debug'] ?? false); ?> />
                                Enable debug logging
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Auto Cleanup</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_cleanup" <?php checked($settings['auto_cleanup'] ?? true); ?> />
                                Automatically delete old PDF files
                            </label>
                            <br>
                            <label>
                                Delete files older than <input type="number" name="cleanup_days" value="<?php echo esc_attr($settings['cleanup_days'] ?? 7); ?>" min="1" max="365" style="width: 60px;" /> days
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function logs_page() {
        global $wpdb;
        $logs = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_LOGS . " ORDER BY created_date DESC LIMIT 100"
        );
        ?>
        <div class="wrap">
            <h1>Activity Logs</h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Template</th>
                        <th>Action</th>
                        <th>Status</th>
                        <th>Message</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->created_date); ?></td>
                                <td><?php echo esc_html($log->template_id); ?></td>
                                <td><?php echo esc_html($log->action); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($log->status); ?>">
                                        <?php echo esc_html(ucfirst($log->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($log->message); ?></td>
                                <td><?php echo $log->user_id ? get_userdata($log->user_id)->display_name : 'Guest'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No logs found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    private function render_recent_templates() {
        global $wpdb;
        $templates = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " ORDER BY created_date DESC LIMIT 5"
        );
        
        if ($templates) {
            echo '<ul>';
            foreach ($templates as $template) {
                echo '<li>';
                echo '<a href="' . admin_url('admin.php?page=reverse2pdf-builder&template_id=' . $template->id) . '">';
                echo esc_html($template->name);
                echo '</a>';
                echo '<small> - ' . esc_html($template->created_date) . '</small>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No templates created yet.</p>';
        }
    }
    
    private function render_recent_activity() {
        global $wpdb;
        $logs = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_LOGS . " ORDER BY created_date DESC LIMIT 5"
        );
        
        if ($logs) {
            echo '<ul>';
            foreach ($logs as $log) {
                echo '<li>';
                echo esc_html($log->action) . ' - ';
                echo '<span class="status-' . esc_attr($log->status) . '">' . esc_html($log->status) . '</span>';
                echo '<small> - ' . esc_html($log->created_date) . '</small>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No activity yet.</p>';
        }
    }
    
    private function render_integration_card($name, $type, $class_check) {
        $is_active = class_exists($class_check);
        ?>
        <div class="integration-card <?php echo $is_active ? 'active' : 'inactive'; ?>">
            <h3><?php echo esc_html($name); ?></h3>
            <p class="status">
                <?php if ($is_active): ?>
                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                    Plugin Active
                <?php else: ?>
                    <span class="dashicons dashicons-dismiss" style="color: red;"></span>
                    Plugin Not Installed
                <?php endif; ?>
            </p>
            <?php if ($is_active): ?>
                <button type="button" class="button setup-integration" data-type="<?php echo esc_attr($type); ?>">
                    Setup Integration
                </button>
            <?php endif; ?>
        </div>
        <?php
    }
}
?>
