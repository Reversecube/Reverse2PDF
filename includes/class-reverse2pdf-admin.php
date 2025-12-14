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
        // Main menu page (this automatically creates the first submenu item)
        add_menu_page(
            'Reverse2PDF',
            'Reverse2PDF',
            'manage_options',
            'reverse2pdf',
            array($this, 'dashboard_page'),
            'dashicons-media-document',
            30
        );
        
        // Rename the first submenu item to "Dashboard"
        add_submenu_page(
            'reverse2pdf',
            'Dashboard',
            'Dashboard', 
            'manage_options',
            'reverse2pdf',  // SAME slug as parent menu
            array($this, 'dashboard_page')
        );
        
        // Other submenus with DIFFERENT slugs
        add_submenu_page(
            'reverse2pdf',
            'Templates',
            'Templates',
            'manage_options',
            'reverse2pdf-templates',
            array($this, 'templates_page')
        );
        add_submenu_page(
            'reverse2pdf',
            'Visual Builder',
            'Visual Builder',
            'manage_options',
            'reverse2pdf-builder',
            array($this, 'builder_page')
        );
        add_submenu_page(
            'reverse2pdf',
            'Form Integrations',
            'Integrations',
            'manage_options',
            'reverse2pdf-integrations',
            array($this, 'integrations_page')
        );
        add_submenu_page(
            'reverse2pdf',
            'Settings',
            'Settings',
            'manage_options',
            'reverse2pdf-settings',
            array($this, 'settings_page')
        );
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
        global $wpdb;

        // Get statistics
        $stats = array(
            'templates' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES),
            'pdfs_today' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_LOGS . " WHERE DATE(created_date) = CURDATE() AND action = 'pdf_generated'"),
            'integrations' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_INTEGRATIONS . " WHERE active = 1"),
            'total_pdfs' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_LOGS . " WHERE action = 'pdf_generated'")
        );
        ?>
        <div class="wrap reverse2pdf-page">
            <div class="reverse2pdf-dashboard">
                <!-- Hero Section -->
                <div class="reverse2pdf-hero">
                    <div class="reverse2pdf-hero-content">
                        <h1>🚀 Reverse2PDF Pro</h1>
                        <p>The most advanced PDF generation platform for WordPress. Create stunning documents with ease.</p>

                        <div class="reverse2pdf-stats-row">
                            <div class="reverse2pdf-hero-stat">
                                <span class="reverse2pdf-hero-stat-number"><?php echo number_format($stats['templates']); ?></span>
                                <span class="reverse2pdf-hero-stat-label">Templates</span>
                            </div>
                            <div class="reverse2pdf-hero-stat">
                                <span class="reverse2pdf-hero-stat-number"><?php echo number_format($stats['pdfs_today']); ?></span>
                                <span class="reverse2pdf-hero-stat-label">PDFs Today</span>
                            </div>
                            <div class="reverse2pdf-hero-stat">
                                <span class="reverse2pdf-hero-stat-number"><?php echo number_format($stats['integrations']); ?></span>
                                <span class="reverse2pdf-hero-stat-label">Integrations</span>
                            </div>
                            <div class="reverse2pdf-hero-stat">
                                <span class="reverse2pdf-hero-stat-number"><?php echo number_format($stats['total_pdfs']); ?></span>
                                <span class="reverse2pdf-hero-stat-label">Total Generated</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reverse2pdf-container">
                    <!-- Quick Actions -->
                    <div class="reverse2pdf-actions-grid">
                        <a href="<?php echo admin_url('admin.php?page=reverse2pdf-builder'); ?>" class="reverse2pdf-action-card">
                            <span class="reverse2pdf-action-icon">✨</span>
                            <h3 class="reverse2pdf-action-title">Create Template</h3>
                            <p class="reverse2pdf-action-description">Design professional PDFs with our drag-and-drop visual builder</p>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=reverse2pdf-templates'); ?>" class="reverse2pdf-action-card">
                            <span class="reverse2pdf-action-icon">📄</span>
                            <h3 class="reverse2pdf-action-title">Manage Templates</h3>
                            <p class="reverse2pdf-action-description">View, edit, and organize all your PDF templates</p>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=reverse2pdf-integrations'); ?>" class="reverse2pdf-action-card">
                            <span class="reverse2pdf-action-icon">🔗</span>
                            <h3 class="reverse2pdf-action-title">Form Integrations</h3>
                            <p class="reverse2pdf-action-description">Connect forms to automatically generate PDFs on submission</p>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=reverse2pdf-settings'); ?>" class="reverse2pdf-action-card">
                            <span class="reverse2pdf-action-icon">⚙️</span>
                            <h3 class="reverse2pdf-action-title">Settings</h3>
                            <p class="reverse2pdf-action-description">Configure PDF generation and advanced plugin options</p>
                        </a>
                    </div>

                    <!-- Content Grid -->
                    <div class="reverse2pdf-content-grid">
                        <!-- Recent Templates -->
                        <div class="reverse2pdf-card">
                            <div class="reverse2pdf-card-header">
                                <h3 class="reverse2pdf-card-title">
                                    <span>📄</span> Recent Templates
                                </h3>
                            </div>
                            <div class="reverse2pdf-card-body">
                                <?php
                                $recent_templates = $wpdb->get_results(
                                    "SELECT * FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " ORDER BY created_date DESC LIMIT 5"
                                );

                                if ($recent_templates) {
                                    foreach ($recent_templates as $template) {
                                        echo '<div class="reverse2pdf-system-item">';
                                        echo '<div class="reverse2pdf-system-label">' . esc_html($template->name) . '</div>';
                                        echo '<div class="reverse2pdf-system-value">';
                                        echo '<a href="' . admin_url('admin.php?page=reverse2pdf-builder&template_id=' . $template->id) . '" class="reverse2pdf-btn">Edit</a>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<div class="reverse2pdf-empty-state">';
                                    echo '<div class="reverse2pdf-empty-icon">📄</div>';
                                    echo '<h4 class="reverse2pdf-empty-title">No Templates Yet</h4>';
                                    echo '<p class="reverse2pdf-empty-text">Create your first template to get started</p>';
                                    echo '<a href="' . admin_url('admin.php?page=reverse2pdf-builder') . '" class="reverse2pdf-btn">Create Template</a>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>

                        <!-- System Status -->
                        <div class="reverse2pdf-card">
                            <div class="reverse2pdf-card-header">
                                <h3 class="reverse2pdf-card-title">
                                    <span>🏥</span> System Health
                                </h3>
                            </div>
                            <div class="reverse2pdf-card-body">
                                <div class="reverse2pdf-system-item">
                                    <div class="reverse2pdf-system-label">PHP Version</div>
                                    <div class="reverse2pdf-system-value">
                                        <span class="<?php echo version_compare(PHP_VERSION, '7.4', '>=') ? 'reverse2pdf-status-good' : 'reverse2pdf-status-warning'; ?>">
                                            <?php echo PHP_VERSION; ?>
                                            <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? '✅' : '⚠️'; ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="reverse2pdf-system-item">
                                    <div class="reverse2pdf-system-label">WordPress Version</div>
                                    <div class="reverse2pdf-system-value">
                                        <span class="reverse2pdf-status-good">
                                            <?php echo get_bloginfo('version'); ?> ✅
                                        </span>
                                    </div>
                                </div>

                                <div class="reverse2pdf-system-item">
                                    <div class="reverse2pdf-system-label">Memory Limit</div>
                                    <div class="reverse2pdf-system-value">
                                        <span class="reverse2pdf-status-good">
                                            <?php echo ini_get('memory_limit'); ?> ✅
                                        </span>
                                    </div>
                                </div>

                                <div class="reverse2pdf-system-item">
                                    <div class="reverse2pdf-system-label">GD Extension</div>
                                    <div class="reverse2pdf-system-value">
                                        <span class="<?php echo extension_loaded('gd') ? 'reverse2pdf-status-good' : 'reverse2pdf-status-warning'; ?>">
                                            <?php echo extension_loaded('gd') ? 'Available ✅' : 'Missing ⚠️'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_recent_templates_premium() {
        global $wpdb;
        $templates = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " ORDER BY created_date DESC LIMIT 5"
        );
        
        if ($templates) {
            foreach ($templates as $template) {
                echo '<div class="template-item">';
                echo '<div class="template-info">';
                echo '<h4>' . esc_html($template->name) . '</h4>';
                echo '<p>' . esc_html($template->description) . '</p>';
                echo '</div>';
                echo '<a href="' . admin_url('admin.php?page=reverse2pdf-builder&template_id=' . $template->id) . '" class="reverse2pdf-btn reverse2pdf-btn-sm">Edit</a>';
                echo '</div>';
            }
        } else {
            echo '<div class="reverse2pdf-empty-state">';
            echo '<div class="reverse2pdf-empty-icon">📄</div>';
            echo '<h4 class="reverse2pdf-empty-title">No Templates Yet</h4>';
            echo '<p class="reverse2pdf-empty-text">Create your first template to get started</p>';
            echo '<a href="' . admin_url('admin.php?page=reverse2pdf-builder') . '" class="reverse2pdf-btn">Create Template</a>';
            echo '</div>';
        }
    }

    private function render_system_health() {
        ?>
        <div class="health-item">
            <span>PHP Version</span>
            <span class="health-status <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? 'good' : 'warning'; ?>">
                <?php echo PHP_VERSION; ?>
            </span>
        </div>
        <div class="health-item">
            <span>WordPress</span>
            <span class="health-status good"><?php echo get_bloginfo('version'); ?></span>
        </div>
        <div class="health-item">
            <span>Memory Limit</span>
            <span class="health-status good"><?php echo ini_get('memory_limit'); ?></span>
        </div>
        <div class="health-item">
            <span>GD Extension</span>
            <span class="health-status <?php echo extension_loaded('gd') ? 'good' : 'warning'; ?>">
                <?php echo extension_loaded('gd') ? 'Available' : 'Missing'; ?>
            </span>
        </div>
        <?php
    }

    private function render_quick_setup() {
        global $wpdb;
        $templates_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES);
        $integrations_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_INTEGRATIONS);
        
        $progress = 0;
        if ($templates_count > 0) $progress += 33;
        if ($integrations_count > 0) $progress += 33;
        $progress += 34; // Plugin installed
        ?>
        
        <div class="setup-progress">
            <div class="setup-progress-bar" style="width: <?php echo $progress; ?>%"></div>
        </div>
        
        <div class="setup-step <?php echo $progress >= 34 ? 'completed' : 'pending'; ?>">
            <div class="setup-step-icon"><?php echo $progress >= 34 ? '✓' : '1'; ?></div>
            <div>
                <strong>Plugin Installed</strong><br>
                <small>Reverse2PDF is active and ready</small>
            </div>
        </div>
        
        <div class="setup-step <?php echo $templates_count > 0 ? 'completed' : ($progress >= 34 ? 'current' : 'pending'); ?>">
            <div class="setup-step-icon"><?php echo $templates_count > 0 ? '✓' : '2'; ?></div>
            <div>
                <strong>Create Template</strong><br>
                <small><?php echo $templates_count > 0 ? $templates_count . ' templates created' : 'Design your first PDF template'; ?></small>
            </div>
        </div>
        
        <div class="setup-step <?php echo $integrations_count > 0 ? 'completed' : 'pending'; ?>">
            <div class="setup-step-icon"><?php echo $integrations_count > 0 ? '✓' : '3'; ?></div>
            <div>
                <strong>Connect Forms</strong><br>
                <small><?php echo $integrations_count > 0 ? $integrations_count . ' integrations active' : 'Connect forms for automation'; ?></small>
            </div>
        </div>
        <?php
    }

    private function render_activity_timeline() {
        global $wpdb;
        $logs = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_LOGS . " ORDER BY created_date DESC LIMIT 10"
        );
        
        if ($logs) {
            echo '<div class="activity-timeline">';
            foreach ($logs as $log) {
                $status_class = $log->status === 'success' ? 'success' : ($log->status === 'error' ? 'error' : 'warning');
                echo '<div class="activity-item ' . $status_class . '">';
                echo '<div class="activity-title">' . esc_html($log->action) . '</div>';
                echo '<div class="activity-time">' . esc_html($log->created_date) . '</div>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<div class="reverse2pdf-empty-state">';
            echo '<div class="reverse2pdf-empty-icon">📊</div>';
            echo '<h4 class="reverse2pdf-empty-title">No Activity Yet</h4>';
            echo '<p class="reverse2pdf-empty-text">Start creating PDFs to see activity here</p>';
            echo '</div>';
        }
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
            $table_name = $wpdb->prefix . (defined('REVERSE2PDF_TABLE_TEMPLATES') ? REVERSE2PDF_TABLE_TEMPLATES : 'reverse2pdf_templates');
            $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $template_id));
        }

        // INLINE PRO BUILDER - NO EXTERNAL TEMPLATE FILE NEEDED
        ?>
        <div class="wrap r2pdf-builder-wrap">
            <!-- Top Header Bar -->
            <div class="r2pdf-builder-header">
                <div class="r2pdf-header-left">
                    <div class="r2pdf-header-title">
                        <span class="dashicons dashicons-pdf"></span>
                        <h1>PDF Template Builder</h1>
                    </div>
                    <input type="text" id="template-name" class="r2pdf-template-name" placeholder="Enter template name..." value="<?php echo $template ? esc_attr($template->name) : ''; ?>">
                </div>

                <div class="r2pdf-header-right">
                    <button class="r2pdf-btn r2pdf-btn-icon" id="undo-btn" title="Undo">
                        <span class="dashicons dashicons-undo"></span>
                    </button>
                    <button class="r2pdf-btn r2pdf-btn-icon" id="redo-btn" title="Redo">
                        <span class="dashicons dashicons-redo"></span>
                    </button>
                    <button class="r2pdf-btn r2pdf-btn-light" id="preview-pdf-btn">
                        <span class="dashicons dashicons-visibility"></span>
                        Preview
                    </button>
                    <button class="r2pdf-btn r2pdf-btn-success" id="save-template-btn">
                        <span class="dashicons dashicons-saved"></span>
                        Save Template
                    </button>
                    <button class="r2pdf-btn r2pdf-btn-primary" id="export-pdf-btn">
                        <span class="dashicons dashicons-download"></span>
                        Export PDF
                    </button>
                </div>
            </div>

            <!-- Main Layout -->
            <div class="r2pdf-builder-main">
                <!-- Left Sidebar - Elements & Tools -->
                <div class="r2pdf-sidebar r2pdf-sidebar-left">
                    <!-- Tab Navigation -->
                    <div class="r2pdf-sidebar-tabs">
                        <button class="r2pdf-tab-btn active" data-tab="elements">
                            <span class="dashicons dashicons-editor-table"></span>
                            Elements
                        </button>
                        <button class="r2pdf-tab-btn" data-tab="pages">
                            <span class="dashicons dashicons-media-document"></span>
                            Pages
                        </button>
                        <button class="r2pdf-tab-btn" data-tab="mapping">
                            <span class="dashicons dashicons-networking"></span>
                            Mapping
                        </button>
                        <button class="r2pdf-tab-btn" data-tab="settings">
                            <span class="dashicons dashicons-admin-settings"></span>
                            Settings
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="r2pdf-sidebar-content">
                        <!-- Elements Tab -->
                        <div class="r2pdf-tab-content active" data-tab="elements">
                            <!-- Text Elements -->
                            <div class="element-category">
                                <div class="category-header">
                                    <span class="dashicons dashicons-editor-textcolor"></span>
                                    <span class="category-title">TEXT ELEMENTS</span>
                                </div>
                                <div class="element-grid">
                                    <div class="element-item" data-type="text" draggable="true">
                                        <span class="dashicons dashicons-editor-paragraph"></span>
                                        <span class="element-label">Text</span>
                                    </div>
                                    <div class="element-item" data-type="heading" draggable="true">
                                        <span class="dashicons dashicons-heading"></span>
                                        <span class="element-label">Heading</span>
                                    </div>
                                    <div class="element-item" data-type="label" draggable="true">
                                        <span class="dashicons dashicons-tag"></span>
                                        <span class="element-label">Label</span>
                                    </div>
                                    <div class="element-item" data-type="paragraph" draggable="true">
                                        <span class="dashicons dashicons-text"></span>
                                        <span class="element-label">Paragraph</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Fields -->
                            <div class="element-category">
                                <div class="category-header">
                                    <span class="dashicons dashicons-feedback"></span>
                                    <span class="category-title">FORM FIELDS</span>
                                </div>
                                <div class="element-grid">
                                    <div class="element-item" data-type="input" draggable="true">
                                        <span class="dashicons dashicons-edit"></span>
                                        <span class="element-label">Input Field</span>
                                    </div>
                                    <div class="element-item" data-type="textarea" draggable="true">
                                        <span class="dashicons dashicons-text-page"></span>
                                        <span class="element-label">Textarea</span>
                                    </div>
                                    <div class="element-item" data-type="checkbox" draggable="true">
                                        <span class="dashicons dashicons-yes"></span>
                                        <span class="element-label">Checkbox</span>
                                    </div>
                                    <div class="element-item" data-type="radio" draggable="true">
                                        <span class="dashicons dashicons-marker"></span>
                                        <span class="element-label">Radio</span>
                                    </div>
                                    <div class="element-item" data-type="select" draggable="true">
                                        <span class="dashicons dashicons-menu"></span>
                                        <span class="element-label">Select</span>
                                    </div>
                                    <div class="element-item" data-type="signature" draggable="true">
                                        <span class="dashicons dashicons-edit-large"></span>
                                        <span class="element-label">Signature</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Media -->
                            <div class="element-category">
                                <div class="category-header">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <span class="category-title">MEDIA</span>
                                </div>
                                <div class="element-grid">
                                    <div class="element-item" data-type="image" draggable="true">
                                        <span class="dashicons dashicons-format-image"></span>
                                        <span class="element-label">Image</span>
                                    </div>
                                    <div class="element-item" data-type="logo" draggable="true">
                                        <span class="dashicons dashicons-wordpress-alt"></span>
                                        <span class="element-label">Logo</span>
                                    </div>
                                    <div class="element-item" data-type="qrcode" draggable="true">
                                        <span class="dashicons dashicons-grid-view"></span>
                                        <span class="element-label">QR Code</span>
                                    </div>
                                    <div class="element-item" data-type="barcode" draggable="true">
                                        <span class="dashicons dashicons-code-standards"></span>
                                        <span class="element-label">Barcode</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Shapes -->
                            <div class="element-category">
                                <div class="category-header">
                                    <span class="dashicons dashicons-admin-customizer"></span>
                                    <span class="category-title">SHAPES</span>
                                </div>
                                <div class="element-grid">
                                    <div class="element-item" data-type="line" draggable="true">
                                        <span class="dashicons dashicons-minus"></span>
                                        <span class="element-label">Line</span>
                                    </div>
                                    <div class="element-item" data-type="rectangle" draggable="true">
                                        <span class="dashicons dashicons-table-col-after"></span>
                                        <span class="element-label">Rectangle</span>
                                    </div>
                                    <div class="element-item" data-type="circle" draggable="true">
                                        <span class="dashicons dashicons-marker"></span>
                                        <span class="element-label">Circle</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamic Data -->
                            <div class="element-category">
                                <div class="category-header">
                                    <span class="dashicons dashicons-database"></span>
                                    <span class="category-title">DYNAMIC DATA</span>
                                </div>
                                <div class="element-grid">
                                    <div class="element-item" data-type="shortcode" draggable="true">
                                        <span class="dashicons dashicons-shortcode"></span>
                                        <span class="element-label">Shortcode</span>
                                    </div>
                                    <div class="element-item" data-type="post-title" draggable="true">
                                        <span class="dashicons dashicons-admin-post"></span>
                                        <span class="element-label">Post Title</span>
                                    </div>
                                    <div class="element-item" data-type="custom-field" draggable="true">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                        <span class="element-label">Custom Field</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pages Tab -->
                        <div class="r2pdf-tab-content" data-tab="pages">
                            <div class="pages-header">
                                <h3>Document Pages</h3>
                                <button class="r2pdf-btn r2pdf-btn-small" id="add-page-btn">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    Add Page
                                </button>
                            </div>
                            <div class="pages-list" id="pages-list"></div>
                        </div>

                        <!-- Mapping Tab -->
                        <div class="r2pdf-tab-content" data-tab="mapping">
                            <div class="mapping-header">
                                <h3>Form Field Mapping</h3>
                                <select id="form-integration" class="r2pdf-select">
                                    <option value="">Select Form Plugin</option>
                                    <option value="cf7">Contact Form 7</option>
                                    <option value="elementor">Elementor Forms</option>
                                    <option value="wpforms">WPForms</option>
                                    <option value="gravity">Gravity Forms</option>
                                    <option value="ninja">Ninja Forms</option>
                                    <option value="woocommerce">WooCommerce</option>
                                </select>
                            </div>
                            <div class="mapping-list" id="mapping-list">
                                <p class="mapping-empty">Select a form plugin to start mapping</p>
                            </div>
                        </div>

                        <!-- Settings Tab -->
                        <div class="r2pdf-tab-content" data-tab="settings">
                            <div class="settings-section">
                                <h3>PDF Settings</h3>
                                <div class="settings-group">
                                    <label>Page Size</label>
                                    <select id="page-size" class="r2pdf-select">
                                        <option value="A4">A4</option>
                                        <option value="Letter">Letter</option>
                                        <option value="Legal">Legal</option>
                                        <option value="A3">A3</option>
                                    </select>
                                </div>
                                <div class="settings-group">
                                    <label>Orientation</label>
                                    <select id="orientation" class="r2pdf-select">
                                        <option value="portrait">Portrait</option>
                                        <option value="landscape">Landscape</option>
                                    </select>
                                </div>
                                <div class="settings-group">
                                    <label><input type="checkbox" id="flatten-pdf"> Flatten PDF</label>
                                </div>
                                <div class="settings-group">
                                    <label>Dynamic Filename</label>
                                    <input type="text" id="pdf-filename" class="r2pdf-input" placeholder="{post_title}_{date}.pdf">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Center - Canvas -->
                <div class="r2pdf-canvas-container">
                    <div class="r2pdf-canvas-toolbar">
                        <div class="toolbar-group">
                            <button class="toolbar-btn" id="zoom-out"><span class="dashicons dashicons-minus"></span></button>
                            <input type="text" id="zoom-level" value="100%" readonly>
                            <button class="toolbar-btn" id="zoom-in"><span class="dashicons dashicons-plus"></span></button>
                            <button class="toolbar-btn" id="zoom-fit"><span class="dashicons dashicons-editor-expand"></span></button>
                        </div>
                        <div class="toolbar-group">
                            <button class="toolbar-btn" id="align-left"><span class="dashicons dashicons-editor-alignleft"></span></button>
                            <button class="toolbar-btn" id="align-center"><span class="dashicons dashicons-editor-aligncenter"></span></button>
                            <button class="toolbar-btn" id="align-right"><span class="dashicons dashicons-editor-alignright"></span></button>
                        </div>
                        <div class="toolbar-group">
                            <button class="toolbar-btn" id="duplicate-element"><span class="dashicons dashicons-admin-page"></span></button>
                            <button class="toolbar-btn" id="delete-element"><span class="dashicons dashicons-trash"></span></button>
                        </div>
                    </div>

                    <div class="r2pdf-canvas-workspace">
                        <div class="pdf-canvas" id="pdf-canvas" 
                             data-template-id="<?php echo $template_id; ?>"
                             data-template='<?php echo $template ? json_encode($template) : '{}'; ?>'>
                            <div class="canvas-page" data-page="1">
                                <div class="canvas-empty-state">
                                    <span class="dashicons dashicons-welcome-widgets-menus"></span>
                                    <h3>Start Building Your PDF</h3>
                                    <p>Drag elements from left sidebar</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar - Properties -->
                <div class="r2pdf-sidebar r2pdf-sidebar-right">
                    <div class="r2pdf-sidebar-header">
                        <h2><span class="dashicons dashicons-admin-settings"></span> Properties</h2>
                    </div>
                    <div class="r2pdf-sidebar-content" id="properties-panel">
                        <div class="properties-empty">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <p>Select an element to edit properties</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }


// Add this method to the existing class-reverse2pdf-admin.php file

    public function handle_ajax_requests() {
        // Save template AJAX
        add_action('wp_ajax_reverse2pdf_save_template', array($this, 'ajax_save_template'));
        
        // Generate PDF AJAX  
        add_action('wp_ajax_reverse2pdf_generate_pdf', array($this, 'ajax_generate_pdf'));
        
        // Get templates AJAX
        add_action('wp_ajax_reverse2pdf_get_templates', array($this, 'ajax_get_templates'));
    }

   public function ajax_save_template() {
    // Security check
    check_ajax_referer('reverse2pdf_builder_nonce', 'nonce');

    // Permission check
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }

    // Get data
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
    $template_name = isset($_POST['template_name']) ? sanitize_text_field($_POST['template_name']) : 'Untitled';
    $template_data = isset($_POST['template_data']) ? wp_kses_post($_POST['template_data']) : '';
    $page_size = isset($_POST['page_size']) ? sanitize_text_field($_POST['page_size']) : 'A4';
    $orientation = isset($_POST['orientation']) ? sanitize_text_field($_POST['orientation']) : 'portrait';

    if (empty($template_data)) {
        wp_send_json_error('No template data');
    }

    global $wpdb;
    $table = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;

    $data = array(
        'name' => $template_name,
        'template_data' => $template_data,
        'page_size' => $page_size,
        'orientation' => $orientation,
        'updated_at' => current_time('mysql')
    );

    if ($template_id > 0) {
            // Update existing
            $result = $wpdb->update($table, $data, array('id' => $template_id));

            if ($result !== false) {
                wp_send_json_success(array(
                    'message' => 'Template updated successfully',
                    'template_id' => $template_id
                ));
            } else {
                wp_send_json_error('Failed to update template');
            }
        } else {
            // Create new
            $data['created_at'] = current_time('mysql');
            $data['author_id'] = get_current_user_id();

            $result = $wpdb->insert($table, $data);

            if ($result) {
                wp_send_json_success(array(
                    'message' => 'Template created successfully',
                    'template_id' => $wpdb->insert_id
                ));
            } else {
                wp_send_json_error('Failed to create template');
            }
        }
    }

    public function ajax_generate_pdf() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'reverse2pdf_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        $form_data = $_POST['form_data'] ?? array();
        
        if (!$template_id) {
            wp_send_json_error('Template ID required');
        }
        
        try {
            // Use the generator class
            $generator = new Reverse2PDF_Generator();
            $pdf_url = $generator->generate_pdf($template_id, $form_data);
            
            if ($pdf_url) {
                wp_send_json_success(array('pdf_url' => $pdf_url));
            } else {
                wp_send_json_error('PDF generation failed');
            }
        } catch (Exception $e) {
            wp_send_json_error('PDF generation error: ' . $e->getMessage());
        }
    }

    public function ajax_get_templates() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'reverse2pdf_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $templates = $wpdb->get_results(
            "SELECT id, name FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " WHERE active = 1 ORDER BY name"
        );
        
        wp_send_json_success($templates);
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