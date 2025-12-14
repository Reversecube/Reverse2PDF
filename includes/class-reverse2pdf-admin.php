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
            $template = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " WHERE id = %d",
                $template_id
            ));
        }
        
        // Enqueue required scripts
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_script('jquery-ui-resizable');
        ?>
        <div class="wrap reverse2pdf-builder-wrap">
            <style>
                /* Complete Enhanced Styles for Perfect Functionality */
                .reverse2pdf-builder-wrap {
                    margin: -10px -20px -20px -20px;
                    background: #f8fafc;
                    min-height: 100vh;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                }
                
                .builder-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 20px 30px;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                    position: sticky;
                    top: 32px;
                    z-index: 100;
                }
                
                .builder-header h1 {
                    margin: 0 0 15px 0;
                    font-size: 1.8rem;
                    font-weight: 700;
                    color: white;
                }
                
                .builder-controls {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    flex-wrap: wrap;
                }
                
                .builder-input {
                    background: rgba(255,255,255,0.15);
                    border: 2px solid rgba(255,255,255,0.3);
                    color: white;
                    padding: 10px 15px;
                    border-radius: 8px;
                    font-size: 16px;
                    min-width: 250px;
                    backdrop-filter: blur(10px);
                    transition: all 0.3s ease;
                }
                
                .builder-input:focus {
                    background: rgba(255,255,255,0.25);
                    border-color: rgba(255,255,255,0.6);
                    outline: none;
                    box-shadow: 0 0 0 4px rgba(255,255,255,0.1);
                }
                
                .builder-input::placeholder {
                    color: rgba(255,255,255,0.7);
                }
                
                .builder-btn {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 10px 20px;
                    background: rgba(255,255,255,0.2);
                    color: white;
                    border: 2px solid rgba(255,255,255,0.3);
                    border-radius: 8px;
                    font-weight: 600;
                    text-decoration: none;
                    transition: all 0.3s ease;
                    backdrop-filter: blur(10px);
                    cursor: pointer;
                    font-size: 14px;
                }
                
                .builder-btn:hover {
                    background: rgba(255,255,255,0.3);
                    border-color: rgba(255,255,255,0.5);
                    transform: translateY(-1px);
                    color: white;
                    text-decoration: none;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                }
                
                .builder-btn:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                    transform: none !important;
                }
                
                .builder-btn.primary {
                    background: rgba(255,255,255,0.9);
                    color: #667eea;
                    border-color: transparent;
                }
                
                .builder-btn.primary:hover {
                    background: white;
                    color: #4f46e5;
                }
                
                .builder-workspace {
                    display: grid;
                    grid-template-columns: 340px 1fr;
                    height: calc(100vh - 140px);
                    gap: 0;
                }
                
                .builder-sidebar {
                    background: white;
                    border-right: 1px solid #e5e7eb;
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                }
                
                .sidebar-header {
                    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                    padding: 20px;
                    border-bottom: 2px solid #e5e7eb;
                    font-weight: 700;
                    color: #1f2937;
                    font-size: 14px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .sidebar-content {
                    flex: 1;
                    overflow-y: auto;
                    padding: 20px;
                }
                
                .element-library {
                    margin-bottom: 30px;
                }
                
                .element-group-title {
                    font-size: 12px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    color: #6b7280;
                    margin-bottom: 15px;
                    padding-bottom: 8px;
                    border-bottom: 1px solid #f3f4f6;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .element-item {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 12px 15px;
                    margin-bottom: 8px;
                    background: #f8fafc;
                    border: 2px solid transparent;
                    border-radius: 10px;
                    cursor: grab;
                    transition: all 0.3s ease;
                    user-select: none;
                    font-weight: 500;
                    position: relative;
                    overflow: hidden;
                }
                
                .element-item:active {
                    cursor: grabbing;
                }
                
                .element-item::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    transition: left 0.3s ease;
                    z-index: 1;
                }
                
                .element-item:hover::before {
                    left: 0;
                }
                
                .element-item:hover {
                    color: white;
                    border-color: #667eea;
                    transform: translateX(8px) scale(1.02);
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                }
                
                .element-item > * {
                    position: relative;
                    z-index: 2;
                }
                
                .element-icon {
                    font-size: 18px;
                    width: 20px;
                    height: 20px;
                    text-align: center;
                }
                
                .element-description {
                    font-size: 11px;
                    color: #9ca3af;
                    font-weight: 400;
                    margin-top: 2px;
                }
                
                .element-item:hover .element-description {
                    color: rgba(255,255,255,0.8);
                }
                
                .properties-panel {
                    background: white;
                    border-radius: 10px;
                    padding: 20px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    border: 1px solid #e5e7eb;
                }
                
                .properties-title {
                    font-size: 16px;
                    font-weight: 700;
                    color: #1f2937;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #f3f4f6;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .property-group {
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                    border-bottom: 1px solid #f3f4f6;
                }
                
                .property-group:last-child {
                    border-bottom: none;
                    margin-bottom: 0;
                }
                
                .property-group h6 {
                    margin-bottom: 12px;
                    font-size: 13px;
                    font-weight: 600;
                    color: #374151;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }
                
                .property-label {
                    font-size: 11px;
                    font-weight: 600;
                    color: #6b7280;
                    margin-bottom: 4px;
                    display: block;
                }
                
                .property-input, .property-select, .property-textarea {
                    width: 100%;
                    padding: 8px 12px;
                    border: 2px solid #e5e7eb;
                    border-radius: 6px;
                    font-size: 13px;
                    transition: all 0.3s ease;
                    background: white;
                }
                
                .property-input:focus, .property-select:focus, .property-textarea:focus {
                    border-color: #667eea;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
                }
                
                .property-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 10px;
                }
                
                .builder-canvas {
                    background: 
                        radial-gradient(circle at 20px 20px, #d1d5db 1px, transparent 1px),
                        linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
                    background-size: 20px 20px, 100% 100%;
                    padding: 30px;
                    overflow: auto;
                    display: flex;
                    justify-content: center;
                    align-items: flex-start;
                    min-height: 100%;
                }
                
                .canvas-container {
                    position: relative;
                }
                
                .pdf-canvas {
                    background: white;
                    box-shadow: 
                        0 25px 50px -12px rgba(0, 0, 0, 0.25),
                        0 0 0 1px rgba(0, 0, 0, 0.05);
                    border-radius: 12px;
                    position: relative;
                    transition: all 0.3s ease;
                    overflow: visible;
                }
                
                .pdf-page {
                    background: white;
                    position: relative;
                    margin-bottom: 20px;
                    width: 595px;
                    min-height: 842px;
                    border-radius: 8px;
                    border: 1px solid rgba(0,0,0,0.05);
                    transition: all 0.3s ease;
                    overflow: visible;
                }
                
                .pdf-page:last-child {
                    margin-bottom: 0;
                }
                
                .pdf-page.drop-hover {
                    border: 2px dashed #667eea !important;
                    background: rgba(102, 126, 234, 0.02) !important;
                }
                
                .page-number {
                    position: absolute;
                    top: 10px;
                    right: 15px;
                    background: rgba(0, 0, 0, 0.7);
                    color: white;
                    padding: 6px 12px;
                    border-radius: 6px;
                    font-size: 12px;
                    font-weight: 600;
                    z-index: 10;
                    pointer-events: none;
                }
                
                .page-controls {
                    position: absolute;
                    top: 10px;
                    left: 15px;
                    display: flex;
                    gap: 5px;
                    z-index: 10;
                }
                
                .page-control-btn {
                    background: rgba(0, 0, 0, 0.7);
                    color: white;
                    border: none;
                    width: 24px;
                    height: 24px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                }
                
                .page-control-btn:hover {
                    background: rgba(0, 0, 0, 0.9);
                    transform: scale(1.1);
                }
                
                .pdf-element {
                    position: absolute !important;
                    border: 2px dashed transparent;
                    cursor: move;
                    min-width: 20px;
                    min-height: 20px;
                    transition: all 0.3s ease;
                    z-index: 5;
                    overflow: visible;
                }
                
                .pdf-element:hover {
                    border-color: rgba(102, 126, 234, 0.5) !important;
                    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
                }
                
                .pdf-element.selected {
                    border-color: #667eea !important;
                    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2) !important;
                }
                
                .element-controls {
                    position: absolute;
                    top: -35px;
                    right: 0;
                    display: none;
                    gap: 4px;
                    z-index: 15;
                }
                
                .pdf-element.selected .element-controls {
                    display: flex !important;
                }
                
                .control-btn {
                    width: 26px;
                    height: 26px;
                    border: none;
                    background: #667eea;
                    color: white;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                }
                
                .control-btn:hover {
                    background: #4f46e5;
                    transform: scale(1.1);
                }
                
                .empty-state {
                    text-align: center;
                    padding: 60px 30px;
                    color: #6b7280;
                }
                
                .empty-icon {
                    font-size: 4rem;
                    margin-bottom: 20px;
                    opacity: 0.5;
                }
                
                .empty-title {
                    font-size: 1.5rem;
                    font-weight: 600;
                    color: #374151;
                    margin-bottom: 10px;
                }
                
                .empty-text {
                    font-size: 1rem;
                    margin-bottom: 25px;
                    max-width: 400px;
                    margin-left: auto;
                    margin-right: auto;
                }
                
                /* jQuery UI Enhancements */
                .ui-draggable-dragging {
                    z-index: 1000 !important;
                    opacity: 0.8 !important;
                }
                
                .ui-resizable-handle {
                    background: #667eea !important;
                    border: 1px solid #4f46e5 !important;
                }
                
                .ui-resizable-se {
                    width: 12px !important;
                    height: 12px !important;
                    right: 1px !important;
                    bottom: 1px !important;
                }
                
                .ui-resizable-s, .ui-resizable-e, .ui-resizable-n, .ui-resizable-w {
                    background: #667eea !important;
                }
                
                /* Loading Animation */
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                
                .reverse2pdf-spin {
                    animation: spin 1s linear infinite;
                }
                
                /* Responsive Design */
                @media (max-width: 768px) {
                    .builder-workspace {
                        grid-template-columns: 1fr;
                        grid-template-rows: auto 1fr;
                    }
                    
                    .builder-sidebar {
                        border-right: none;
                        border-bottom: 1px solid #e5e7eb;
                        max-height: 400px;
                    }
                }
            </style>
            
            <!-- Builder Header -->
            <div class="builder-header">
                <h1><?php echo $template ? '✏️ Edit: ' . esc_html($template->name) : '🎨 Create PDF Template'; ?></h1>
                <div class="builder-controls">
                    <input type="text" id="template-name" class="builder-input" 
                        placeholder="Enter template name..." 
                        value="<?php echo $template ? esc_attr($template->name) : ''; ?>" />
                    
                    <button type="button" id="save-template" class="builder-btn primary">
                        <span class="dashicons dashicons-yes"></span> 
                        Save Template
                    </button>
                    
                    <button type="button" id="test-template" class="builder-btn">
                        <span class="dashicons dashicons-media-document"></span> 
                        Test PDF
                    </button>
                    
                    <button type="button" id="add-page" class="builder-btn">
                        <span class="dashicons dashicons-plus-alt2"></span> 
                        Add Page
                    </button>
                </div>
            </div>
            
            <!-- Builder Workspace -->
            <div class="builder-workspace">
                <!-- Sidebar -->
                <div class="builder-sidebar">
                    <div class="sidebar-header">
                        <span class="dashicons dashicons-admin-tools"></span>
                        PDF Design Studio
                    </div>
                    
                    <div class="sidebar-content">
                        <!-- Element Library -->
                        <div class="element-library">
                            <div class="element-group">
                                <div class="element-group-title">
                                    📝 Text Elements
                                </div>
                                
                                <div class="element-item" data-type="text">
                                    <span class="element-icon dashicons dashicons-editor-textcolor"></span>
                                    <div>
                                        <div>Text</div>
                                        <div class="element-description">Simple text content</div>
                                    </div>
                                </div>
                                
                                <div class="element-item" data-type="heading">
                                    <span class="element-icon dashicons dashicons-heading"></span>
                                    <div>
                                        <div>Heading</div>
                                        <div class="element-description">Large title text</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="element-group">
                                <div class="element-group-title">
                                    🖼️ Media
                                </div>
                                
                                <div class="element-item" data-type="image">
                                    <span class="element-icon dashicons dashicons-format-image"></span>
                                    <div>
                                        <div>Image</div>
                                        <div class="element-description">Upload images</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="element-group">
                                <div class="element-group-title">
                                    📋 Form Fields
                                </div>
                                
                                <div class="element-item" data-type="form-field">
                                    <span class="element-icon dashicons dashicons-edit"></span>
                                    <div>
                                        <div>Form Field</div>
                                        <div class="element-description">Dynamic data</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="element-group">
                                <div class="element-group-title">
                                    🔧 Shapes
                                </div>
                                
                                <div class="element-item" data-type="line">
                                    <span class="element-icon dashicons dashicons-minus"></span>
                                    <div>
                                        <div>Line</div>
                                        <div class="element-description">Horizontal line</div>
                                    </div>
                                </div>
                                
                                <div class="element-item" data-type="rectangle">
                                    <span class="element-icon dashicons dashicons-admin-page"></span>
                                    <div>
                                        <div>Rectangle</div>
                                        <div class="element-description">Shape box</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Properties Panel -->
                        <div class="properties-panel">
                            <div class="properties-title">
                                <span class="dashicons dashicons-admin-settings"></span>
                                Properties
                            </div>
                            <div id="element-properties">
                                <div class="empty-state" style="padding: 30px 15px;">
                                    <div style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;">⚙️</div>
                                    <div style="font-size: 14px; color: #6b7280;">
                                        Click an element to edit properties
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Canvas Area -->
                <div class="builder-canvas">
                    <div class="canvas-container">
                        <div id="pdf-canvas" class="pdf-canvas">
                            <div class="pdf-page" data-page="1">
                                <div class="page-number">Page 1</div>
                                <div class="page-controls">
                                    <button type="button" class="page-control-btn delete-page-btn" title="Delete Page">✕</button>
                                </div>
                                <?php if (!($template && !empty($template->template_data))): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">📄</div>
                                    <div class="empty-title">Start Building</div>
                                    <div class="empty-text">
                                        Drag elements from the left sidebar to create your PDF template
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hidden Fields -->
            <input type="hidden" id="template-id" value="<?php echo $template_id; ?>" />
            <input type="hidden" id="template-data" value="<?php echo $template ? esc_attr($template->template_data) : ''; ?>" />
        </div>

        <script>
        jQuery(document).ready(function($) {
            console.log('🚀 Initializing Reverse2PDF Builder');
            
            let selectedElement = null;
            let elementCounter = 0;
            
            // Initialize drag and drop
            initializeDragDrop();
            
            function initializeDragDrop() {
                console.log('📋 Setting up drag and drop');
                
                // Make elements draggable
                $('.element-item').draggable({
                    helper: function() {
                        const $helper = $(this).clone();
                        $helper.css({
                            'width': $(this).width(),
                            'opacity': '0.8',
                            'z-index': '9999',
                            'pointer-events': 'none'
                        });
                        return $helper;
                    },
                    appendTo: 'body',
                    zIndex: 9999,
                    cursor: 'grabbing',
                    start: function(event, ui) {
                        console.log('🎯 Drag started:', $(this).data('type'));
                        $(this).addClass('dragging');
                    }
                });
                
                // Make pages droppable
                $('.pdf-page').droppable({
                    accept: '.element-item',
                    tolerance: 'pointer',
                    over: function(event, ui) {
                        console.log('📍 Drag over page');
                        $(this).addClass('drop-hover');
                    },
                    out: function(event, ui) {
                        $(this).removeClass('drop-hover');
                    },
                    drop: function(event, ui) {
                        console.log('🎯 Element dropped!');
                        $(this).removeClass('drop-hover');
                        
                        const elementType = ui.draggable.data('type');
                        const pageOffset = $(this).offset();
                        const dropX = event.pageX - pageOffset.left - 10;
                        const dropY = event.pageY - pageOffset.top - 10;
                        
                        addElementToCanvas(elementType, dropX, dropY, $(this));
                    }
                });
            }
            
            function addElementToCanvas(type, x, y, $page) {
                console.log('➕ Adding element:', type, 'at', x, y);
                
                elementCounter++;
                const elementId = 'element_' + elementCounter + '_' + Date.now();
                
                // Element properties
                const props = getElementDefaults(type);
                
                // Create element HTML
                const $element = $(`
                    <div class="pdf-element" 
                        data-id="${elementId}" 
                        data-type="${type}"
                        data-content="${props.content}"
                        style="left: ${Math.max(0, x)}px; top: ${Math.max(0, y)}px; width: ${props.width}px; height: ${props.height}px;">
                        <div class="element-content">${props.html}</div>
                        <div class="element-controls">
                            <button type="button" class="control-btn duplicate-btn" title="Duplicate">⧉</button>
                            <button type="button" class="control-btn delete-btn" title="Delete">✕</button>
                        </div>
                    </div>
                `);
                
                $page.append($element);
                
                // Make element interactive
                makeElementInteractive($element);
                
                // Select the new element
                selectElement($element);
                
                showNotification('✅ Added ' + type + ' element', 'success');
            }
            
            function getElementDefaults(type) {
                const defaults = {
                    'text': {
                        width: 200, height: 30, content: 'Sample Text',
                        html: '<div style="padding: 5px; font-size: 14px;">Sample Text</div>'
                    },
                    'heading': {
                        width: 300, height: 40, content: 'Heading',
                        html: '<div style="padding: 5px; font-size: 24px; font-weight: bold;">Heading</div>'
                    },
                    'image': {
                        width: 150, height: 100, content: 'image.jpg',
                        html: '<div style="background: #f3f4f6; border: 2px dashed #d1d5db; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #9ca3af; width: 100%; height: 100%;">📷 Image</div>'
                    },
                    'form-field': {
                        width: 200, height: 25, content: '{field_name}',
                        html: '<div style="padding: 5px; background: #f9fafb; border: 1px solid #d1d5db; font-size: 14px;">{field_name}</div>'
                    },
                    'line': {
                        width: 200, height: 2, content: '',
                        html: '<div style="width: 100%; height: 2px; background: #000;"></div>'
                    },
                    'rectangle': {
                        width: 150, height: 80, content: '',
                        html: '<div style="width: 100%; height: 100%; border: 2px solid #000; box-sizing: border-box;"></div>'
                    }
                };
                
                return defaults[type] || defaults['text'];
            }
            
            function makeElementInteractive($element) {
                // Click to select
                $element.on('click', function(e) {
                    e.stopPropagation();
                    selectElement($(this));
                });
                
                // Make draggable within page
                $element.draggable({
                    containment: 'parent',
                    grid: [5, 5],
                    start: function() {
                        selectElement($(this));
                    },
                    stop: function() {
                        updatePropertiesPanel();
                        saveTemplateData();
                    }
                });
                
                // Make resizable
                $element.resizable({
                    containment: 'parent',
                    grid: [5, 5],
                    handles: 'n, e, s, w, ne, nw, se, sw',
                    minWidth: 20,
                    minHeight: 10,
                    stop: function() {
                        updatePropertiesPanel();
                        saveTemplateData();
                    }
                });
            }
            
            function selectElement($element) {
                // Remove previous selection
                $('.pdf-element').removeClass('selected');
                
                // Select new element
                $element.addClass('selected');
                selectedElement = $element;
                
                console.log('🎯 Selected element:', $element.data('type'));
                
                // Update properties panel
                updatePropertiesPanel();
            }
            
            function updatePropertiesPanel() {
                if (!selectedElement) {
                    $('#element-properties').html(`
                        <div class="empty-state" style="padding: 30px 15px;">
                            <div style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;">⚙️</div>
                            <div style="font-size: 14px; color: #6b7280;">
                                Click an element to edit properties
                            </div>
                        </div>
                    `);
                    return;
                }
                
                const type = selectedElement.data('type');
                const content = selectedElement.data('content') || '';
                
                let html = `
                    <div class="property-group">
                        <h6><span class="dashicons dashicons-move"></span> Position & Size</h6>
                        <div class="property-grid">
                            <div>
                                <label class="property-label">X</label>
                                <input type="number" class="property-input" data-property="x" value="${parseInt(selectedElement.css('left'))}" min="0">
                            </div>
                            <div>
                                <label class="property-label">Y</label>
                                <input type="number" class="property-input" data-property="y" value="${parseInt(selectedElement.css('top'))}" min="0">
                            </div>
                            <div>
                                <label class="property-label">Width</label>
                                <input type="number" class="property-input" data-property="width" value="${selectedElement.width()}" min="20">
                            </div>
                            <div>
                                <label class="property-label">Height</label>
                                <input type="number" class="property-input" data-property="height" value="${selectedElement.height()}" min="10">
                            </div>
                        </div>
                    </div>
                `;
                
                // Content properties for text elements
                if (['text', 'heading', 'form-field'].includes(type)) {
                    html += `
                        <div class="property-group">
                            <h6><span class="dashicons dashicons-editor-textcolor"></span> Content</h6>
                            <label class="property-label">Text</label>
                            <textarea class="property-textarea" data-property="content" rows="3">${content}</textarea>
                        </div>
                    `;
                }
                
                $('#element-properties').html(html);
                
                // Bind property change events
                $('.property-input, .property-textarea').on('input change', function() {
                    updateElementProperty($(this));
                });
            }
            
            function updateElementProperty($input) {
                if (!selectedElement) return;
                
                const property = $input.data('property');
                const value = $input.val();
                
                switch(property) {
                    case 'x':
                        selectedElement.css('left', Math.max(0, value) + 'px');
                        break;
                    case 'y':
                        selectedElement.css('top', Math.max(0, value) + 'px');
                        break;
                    case 'width':
                        selectedElement.width(Math.max(20, value));
                        break;
                    case 'height':
                        selectedElement.height(Math.max(10, value));
                        break;
                    case 'content':
                        selectedElement.data('content', value);
                        if (['text', 'heading', 'form-field'].includes(selectedElement.data('type'))) {
                            selectedElement.find('.element-content').text(value);
                        }
                        break;
                }
                
                saveTemplateData();
            }
            
            // Clear selection when clicking page
            $(document).on('click', '.pdf-page', function(e) {
                if (e.target === this) {
                    $('.pdf-element').removeClass('selected');
                    selectedElement = null;
                    updatePropertiesPanel();
                }
            });
            
            // Delete element
            $(document).on('click', '.delete-btn', function(e) {
                e.stopPropagation();
                if (confirm('Delete this element?')) {
                    $(this).closest('.pdf-element').remove();
                    selectedElement = null;
                    updatePropertiesPanel();
                    saveTemplateData();
                    showNotification('🗑️ Element deleted', 'success');
                }
            });
            
            // Duplicate element
            $(document).on('click', '.duplicate-btn', function(e) {
                e.stopPropagation();
                const $original = $(this).closest('.pdf-element');
                const $clone = $original.clone();
                
                // Update position
                $clone.css({
                    left: (parseInt($original.css('left')) + 20) + 'px',
                    top: (parseInt($original.css('top')) + 20) + 'px'
                });
                
                // Update ID
                elementCounter++;
                const newId = 'element_' + elementCounter + '_' + Date.now();
                $clone.data('id', newId).attr('data-id', newId);
                
                $original.parent().append($clone);
                makeElementInteractive($clone);
                selectElement($clone);
                saveTemplateData();
                showNotification('📋 Element duplicated', 'success');
            });
            
            // Save template
            $('#save-template').on('click', function() {
                const templateName = $('#template-name').val().trim();
                if (!templateName) {
                    showNotification('⚠️ Please enter template name', 'warning');
                    $('#template-name').focus();
                    return;
                }
                
                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update reverse2pdf-spin"></span> Saving...');
                
                const templateData = collectTemplateData();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'reverse2pdf_save_template',
                        template_id: $('#template-id').val() || 0,
                        template_name: templateName,
                        template_data: JSON.stringify(templateData),
                        nonce: '<?php echo wp_create_nonce('reverse2pdf_nonce'); ?>'
                    },
                    success: function(response) {
                        console.log('💾 Save response:', response);
                        if (response.success) {
                            showNotification('✅ Template saved successfully!', 'success');
                            if (!$('#template-id').val()) {
                                $('#template-id').val(response.data.template_id);
                            }
                        } else {
                            showNotification('❌ Save failed: ' + (response.data || 'Unknown error'), 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('💥 Save error:', error);
                        showNotification('❌ Save request failed', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
            
            // Test PDF
            $('#test-template').on('click', function() {
                const templateId = $('#template-id').val();
                if (!templateId) {
                    showNotification('⚠️ Save template first', 'warning');
                    return;
                }
                
                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update reverse2pdf-spin"></span> Generating...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'reverse2pdf_generate_pdf',
                        template_id: templateId,
                        form_data: {
                            field_name: 'Test Value',
                            name: 'John Doe',
                            email: 'john@example.com'
                        },
                        nonce: '<?php echo wp_create_nonce('reverse2pdf_nonce'); ?>'
                    },
                    success: function(response) {
                        console.log('📄 PDF response:', response);
                        if (response.success) {
                            showNotification('🎉 PDF generated successfully!', 'success');
                            window.open(response.data.pdf_url, '_blank');
                        } else {
                            showNotification('❌ PDF failed: ' + (response.data || 'Unknown error'), 'error');
                        }
                    },
                    error: function() {
                        showNotification('❌ PDF request failed', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
            
            // Add page
            $('#add-page').on('click', function() {
                const pageNum = $('.pdf-page').length + 1;
                const $newPage = $(`
                    <div class="pdf-page" data-page="${pageNum}">
                        <div class="page-number">Page ${pageNum}</div>
                        <div class="page-controls">
                            <button type="button" class="page-control-btn delete-page-btn" title="Delete Page">✕</button>
                        </div>
                    </div>
                `);
                
                $('#pdf-canvas').append($newPage);
                
                // Make new page droppable
                $newPage.droppable({
                    accept: '.element-item',
                    tolerance: 'pointer',
                    over: function() { $(this).addClass('drop-hover'); },
                    out: function() { $(this).removeClass('drop-hover'); },
                    drop: function(event, ui) {
                        $(this).removeClass('drop-hover');
                        const elementType = ui.draggable.data('type');
                        const pageOffset = $(this).offset();
                        const dropX = event.pageX - pageOffset.left - 10;
                        const dropY = event.pageY - pageOffset.top - 10;
                        addElementToCanvas(elementType, dropX, dropY, $(this));
                    }
                });
                
                saveTemplateData();
                showNotification('📄 New page added', 'success');
            });
            
            // Delete page
            $(document).on('click', '.delete-page-btn', function(e) {
                e.stopPropagation();
                if ($('.pdf-page').length <= 1) {
                    showNotification('⚠️ Cannot delete last page', 'warning');
                    return;
                }
                
                if (confirm('Delete this page?')) {
                    $(this).closest('.pdf-page').remove();
                    renumberPages();
                    saveTemplateData();
                    showNotification('🗑️ Page deleted', 'success');
                }
            });
            
            // Utility functions
            function collectTemplateData() {
                const pages = [];
                $('.pdf-page').each(function(index) {
                    const elements = [];
                    $(this).find('.pdf-element').each(function() {
                        const $el = $(this);
                        elements.push({
                            id: $el.data('id'),
                            type: $el.data('type'),
                            x: parseInt($el.css('left')),
                            y: parseInt($el.css('top')),
                            width: $el.width(),
                            height: $el.height(),
                            content: $el.data('content') || ''
                        });
                    });
                    
                    pages.push({
                        id: 'page_' + (index + 1),
                        elements: elements
                    });
                });
                
                return { pages: pages };
            }
            
            function saveTemplateData() {
                const data = collectTemplateData();
                $('#template-data').val(JSON.stringify(data));
            }
            
            function renumberPages() {
                $('.pdf-page').each(function(index) {
                    const pageNum = index + 1;
                    $(this).data('page', pageNum);
                    $(this).find('.page-number').text('Page ' + pageNum);
                });
            }
            
            function showNotification(message, type) {
                const typeClass = type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success');
                const $notice = $(`
                    <div class="notice notice-${typeClass} is-dismissible" style="position: fixed; top: 50px; right: 20px; z-index: 9999; max-width: 350px;">
                        <p><strong>${message}</strong></p>
                    </div>
                `);
                
                $('body').append($notice);
                setTimeout(() => {
                    $notice.fadeOut(500, function() { $(this).remove(); });
                }, 4000);
            }
            
            // Load existing template data
            const existingData = $('#template-data').val();
            if (existingData) {
                try {
                    const templateData = JSON.parse(existingData);
                    loadTemplateData(templateData);
                } catch (e) {
                    console.warn('Failed to parse template data:', e);
                }
            }
            
            function loadTemplateData(data) {
                if (!data.pages) return;
                
                $('#pdf-canvas').empty();
                
                data.pages.forEach((page, pageIndex) => {
                    const pageNum = pageIndex + 1;
                    const $page = $(`
                        <div class="pdf-page" data-page="${pageNum}">
                            <div class="page-number">Page ${pageNum}</div>
                            <div class="page-controls">
                                <button type="button" class="page-control-btn delete-page-btn" title="Delete Page">✕</button>
                            </div>
                        </div>
                    `);
                    
                    $('#pdf-canvas').append($page);
                    
                    // Make droppable
                    $page.droppable({
                        accept: '.element-item',
                        tolerance: 'pointer',
                        over: function() { $(this).addClass('drop-hover'); },
                        out: function() { $(this).removeClass('drop-hover'); },
                        drop: function(event, ui) {
                            $(this).removeClass('drop-hover');
                            const elementType = ui.draggable.data('type');
                            const pageOffset = $(this).offset();
                            const dropX = event.pageX - pageOffset.left - 10;
                            const dropY = event.pageY - pageOffset.top - 10;
                            addElementToCanvas(elementType, dropX, dropY, $(this));
                        }
                    });
                    
                    if (page.elements) {
                        page.elements.forEach(element => {
                            const props = getElementDefaults(element.type);
                            const $element = $(`
                                <div class="pdf-element" 
                                    data-id="${element.id}" 
                                    data-type="${element.type}"
                                    data-content="${element.content}"
                                    style="left: ${element.x}px; top: ${element.y}px; width: ${element.width}px; height: ${element.height}px;">
                                    <div class="element-content">${element.content || props.html}</div>
                                    <div class="element-controls">
                                        <button type="button" class="control-btn duplicate-btn" title="Duplicate">⧉</button>
                                        <button type="button" class="control-btn delete-btn" title="Delete">✕</button>
                                    </div>
                                </div>
                            `);
                            
                            $page.append($element);
                            makeElementInteractive($element);
                        });
                    }
                });
            }
            
            console.log('✅ Reverse2PDF Builder loaded successfully');
        });
        </script>
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
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'reverse2pdf_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        $template_name = sanitize_text_field($_POST['template_name'] ?? '');
        $template_data = wp_unslash($_POST['template_data'] ?? '');
        
        if (empty($template_name)) {
            wp_send_json_error('Template name is required');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;
        
        $data = array(
            'name' => $template_name,
            'template_data' => $template_data,
            'modified_date' => current_time('mysql')
        );
        
        if ($template_id) {
            // Update existing template
            $result = $wpdb->update($table, $data, array('id' => $template_id));
            if ($result !== false) {
                wp_send_json_success(array('template_id' => $template_id, 'message' => 'Template updated'));
            }
        } else {
            // Create new template
            $data['created_by'] = get_current_user_id();
            $data['created_date'] = current_time('mysql');
            $data['active'] = 1;
            
            $result = $wpdb->insert($table, $data);
            if ($result !== false) {
                wp_send_json_success(array('template_id' => $wpdb->insert_id, 'message' => 'Template created'));
            }
        }
        
        wp_send_json_error('Failed to save template');
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
