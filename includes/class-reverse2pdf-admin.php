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
    global $wpdb;
    
    // Get statistics
    $stats = array(
        'templates' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES),
        'pdfs_today' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_LOGS . " WHERE DATE(created_date) = CURDATE() AND action = 'pdf_generated'"),
        'integrations' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_INTEGRATIONS . " WHERE active = 1"),
        'total_pdfs' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_LOGS . " WHERE action = 'pdf_generated'")
    );
    ?>
    <div class="wrap">
        <style>
            .reverse2pdf-dashboard {
                background: #f8f9fa;
                margin: -10px -20px;
                padding: 0;
                min-height: 100vh;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }
            
            .reverse2pdf-hero {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 60px 0 80px;
                position: relative;
                overflow: hidden;
            }
            
            .reverse2pdf-hero::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="%23ffffff" opacity="0.1"><polygon points="0,0 0,100 1000,100"/></svg>') no-repeat center bottom;
                background-size: 100% 100px;
            }
            
            .reverse2pdf-hero-content {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 30px;
                text-align: center;
                position: relative;
                z-index: 2;
            }
            
            .reverse2pdf-hero h1 {
                font-size: 3.5rem;
                font-weight: 800;
                margin: 0 0 20px 0;
                text-shadow: 0 4px 8px rgba(0,0,0,0.2);
                background: linear-gradient(45deg, #fff, #f0f8ff);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            
            .reverse2pdf-hero p {
                font-size: 1.25rem;
                margin: 0 0 40px 0;
                opacity: 0.95;
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .reverse2pdf-stats-row {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 30px;
                margin-top: 40px;
            }
            
            .reverse2pdf-hero-stat {
                text-align: center;
                padding: 20px;
                background: rgba(255,255,255,0.15);
                border-radius: 16px;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255,255,255,0.2);
            }
            
            .reverse2pdf-hero-stat-number {
                display: block;
                font-size: 2.5rem;
                font-weight: 800;
                line-height: 1;
                margin-bottom: 8px;
            }
            
            .reverse2pdf-hero-stat-label {
                font-size: 0.875rem;
                opacity: 0.9;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-weight: 500;
            }
            
            .reverse2pdf-container {
                max-width: 1200px;
                margin: -40px auto 0;
                padding: 0 30px 60px;
                position: relative;
                z-index: 3;
            }
            
            .reverse2pdf-actions-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 24px;
                margin-bottom: 50px;
            }
            
            .reverse2pdf-action-card {
                background: white;
                border-radius: 20px;
                padding: 40px 30px;
                text-align: center;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                border: 1px solid #e2e8f0;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                text-decoration: none;
                color: inherit;
                position: relative;
                overflow: hidden;
                min-height: 220px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            
            .reverse2pdf-action-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                opacity: 0;
                transition: all 0.3s ease;
                z-index: 1;
            }
            
            .reverse2pdf-action-card:hover {
                transform: translateY(-8px) scale(1.02);
                box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
                text-decoration: none;
                color: white;
            }
            
            .reverse2pdf-action-card:hover::before {
                opacity: 1;
            }
            
            .reverse2pdf-action-card > * {
                position: relative;
                z-index: 2;
            }
            
            .reverse2pdf-action-icon {
                font-size: 4rem;
                margin-bottom: 20px;
                display: block;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                transition: all 0.3s ease;
            }
            
            .reverse2pdf-action-card:hover .reverse2pdf-action-icon {
                -webkit-text-fill-color: white;
                transform: scale(1.1);
            }
            
            .reverse2pdf-action-title {
                font-size: 1.5rem;
                font-weight: 700;
                margin: 0 0 12px 0;
                color: #1a202c;
            }
            
            .reverse2pdf-action-card:hover .reverse2pdf-action-title {
                color: white;
            }
            
            .reverse2pdf-action-description {
                font-size: 1rem;
                color: #64748b;
                margin: 0;
                line-height: 1.6;
            }
            
            .reverse2pdf-action-card:hover .reverse2pdf-action-description {
                color: rgba(255,255,255,0.9);
            }
            
            .reverse2pdf-content-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 30px;
                margin-bottom: 40px;
            }
            
            .reverse2pdf-card {
                background: white;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                border: 1px solid #e2e8f0;
                overflow: hidden;
                transition: all 0.3s ease;
            }
            
            .reverse2pdf-card:hover {
                box-shadow: 0 8px 30px rgba(0,0,0,0.12);
                transform: translateY(-2px);
            }
            
            .reverse2pdf-card-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 24px 30px;
                border-bottom: none;
            }
            
            .reverse2pdf-card-title {
                font-size: 1.375rem;
                font-weight: 700;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .reverse2pdf-card-body {
                padding: 30px;
            }
            
            .reverse2pdf-empty-state {
                text-align: center;
                padding: 40px 20px;
                color: #64748b;
            }
            
            .reverse2pdf-empty-icon {
                font-size: 4rem;
                margin-bottom: 16px;
                opacity: 0.5;
            }
            
            .reverse2pdf-empty-title {
                font-size: 1.25rem;
                font-weight: 600;
                color: #374151;
                margin: 0 0 8px 0;
            }
            
            .reverse2pdf-empty-text {
                font-size: 1rem;
                margin: 0 0 24px 0;
            }
            
            .reverse2pdf-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 24px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 0.875rem;
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
            }
            
            .reverse2pdf-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
                color: white;
                text-decoration: none;
            }
            
            .reverse2pdf-system-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 16px 0;
                border-bottom: 1px solid #f1f5f9;
            }
            
            .reverse2pdf-system-item:last-child {
                border-bottom: none;
            }
            
            .reverse2pdf-system-label {
                font-weight: 500;
                color: #374151;
            }
            
            .reverse2pdf-system-value {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 0.875rem;
            }
            
            .reverse2pdf-status-good {
                color: #10b981;
                font-weight: 600;
            }
            
            .reverse2pdf-status-warning {
                color: #f59e0b;
                font-weight: 600;
            }
            
            @media (max-width: 768px) {
                .reverse2pdf-hero h1 {
                    font-size: 2.5rem;
                }
                
                .reverse2pdf-hero-content {
                    padding: 0 20px;
                }
                
                .reverse2pdf-container {
                    padding: 0 20px 40px;
                }
                
                .reverse2pdf-content-grid {
                    grid-template-columns: 1fr;
                }
                
                .reverse2pdf-actions-grid {
                    grid-template-columns: 1fr;
                }
                
                .reverse2pdf-action-card {
                    min-height: 180px;
                    padding: 30px 24px;
                }
                
                .reverse2pdf-action-icon {
                    font-size: 3rem;
                }
            }
        </style>
        
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
    ?>
    <div class="wrap reverse2pdf-builder-wrap">
        <style>
            /* Enhanced Builder Styles */
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
                grid-template-columns: 320px 1fr;
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
            
            .element-item:active {
                cursor: grabbing;
                transform: translateX(8px) scale(0.98);
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
                overflow: hidden;
            }
            
            .pdf-canvas:hover {
                box-shadow: 
                    0 32px 64px -12px rgba(0, 0, 0, 0.35),
                    0 0 0 1px rgba(102, 126, 234, 0.1);
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
            }
            
            .pdf-page:last-child {
                margin-bottom: 0;
            }
            
            .pdf-page.drop-zone {
                border: 2px dashed #667eea;
                background: rgba(102, 126, 234, 0.02);
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
                position: absolute;
                border: 2px dashed transparent;
                cursor: move;
                min-width: 20px;
                min-height: 20px;
                transition: all 0.3s ease;
                z-index: 5;
            }
            
            .pdf-element:hover {
                border-color: rgba(102, 126, 234, 0.5);
                box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
            }
            
            .pdf-element.selected {
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            }
            
            .element-controls {
                position: absolute;
                top: -35px;
                right: 0;
                display: none;
                gap: 4px;
                z-index: 10;
            }
            
            .pdf-element.selected .element-controls {
                display: flex;
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
            
            /* Loading Animation */
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            .reverse2pdf-spin {
                animation: spin 1s linear infinite;
            }
            
            /* Responsive Design */
            @media (max-width: 1024px) {
                .builder-workspace {
                    grid-template-columns: 280px 1fr;
                }
            }
            
            @media (max-width: 768px) {
                .builder-workspace {
                    grid-template-columns: 1fr;
                    grid-template-rows: auto 1fr;
                    height: auto;
                }
                
                .builder-sidebar {
                    border-right: none;
                    border-bottom: 1px solid #e5e7eb;
                    max-height: 300px;
                }
                
                .builder-controls {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 10px;
                }
                
                .builder-input {
                    min-width: auto;
                }
                
                .pdf-page {
                    width: 100%;
                    max-width: 595px;
                }
            }
        </style>
        
        <!-- Builder Header -->
        <div class="builder-header">
            <h1><?php echo $template ? 'Edit Template: ' . esc_html($template->name) : '🎨 Create New Template'; ?></h1>
            <div class="builder-controls">
                <input type="text" id="template-name" class="builder-input" 
                       placeholder="Enter template name..." 
                       value="<?php echo $template ? esc_attr($template->name) : ''; ?>" />
                
                <button type="button" id="save-template" class="builder-btn primary">
                    <span class="dashicons dashicons-yes"></span> 
                    Save Template
                </button>
                
                <button type="button" id="preview-template" class="builder-btn">
                    <span class="dashicons dashicons-visibility"></span> 
                    Preview
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
                    Elements & Properties
                </div>
                
                <div class="sidebar-content">
                    <!-- Element Library -->
                    <div class="element-library">
                        <div class="element-group">
                            <div class="element-group-title">📝 Basic Elements</div>
                            
                            <div class="element-item" data-type="text" draggable="true">
                                <span class="element-icon dashicons dashicons-editor-textcolor"></span>
                                Text Element
                            </div>
                            
                            <div class="element-item" data-type="image" draggable="true">
                                <span class="element-icon dashicons dashicons-format-image"></span>
                                Image
                            </div>
                            
                            <div class="element-item" data-type="line" draggable="true">
                                <span class="element-icon dashicons dashicons-minus"></span>
                                Horizontal Line
                            </div>
                            
                            <div class="element-item" data-type="rectangle" draggable="true">
                                <span class="element-icon dashicons dashicons-admin-page"></span>
                                Rectangle
                            </div>
                        </div>
                        
                        <div class="element-group">
                            <div class="element-group-title">📋 Form Elements</div>
                            
                            <div class="element-item" data-type="form-field" draggable="true">
                                <span class="element-icon dashicons dashicons-edit"></span>
                                Form Field
                            </div>
                            
                            <div class="element-item" data-type="checkbox" draggable="true">
                                <span class="element-icon dashicons dashicons-yes-alt"></span>
                                Checkbox
                            </div>
                            
                            <div class="element-item" data-type="signature" draggable="true">
                                <span class="element-icon dashicons dashicons-edit-large"></span>
                                Signature Field
                            </div>
                        </div>
                        
                        <div class="element-group">
                            <div class="element-group-title">🔧 Advanced</div>
                            
                            <div class="element-item" data-type="table" draggable="true">
                                <span class="element-icon dashicons dashicons-grid-view"></span>
                                Table
                            </div>
                            
                            <div class="element-item" data-type="qr-code" draggable="true">
                                <span class="element-icon dashicons dashicons-screenoptions"></span>
                                QR Code
                            </div>
                            
                            <div class="element-item" data-type="barcode" draggable="true">
                                <span class="element-icon dashicons dashicons-admin-links"></span>
                                Barcode
                            </div>
                            
                            <div class="element-item" data-type="chart" draggable="true">
                                <span class="element-icon dashicons dashicons-chart-line"></span>
                                Chart
                            </div>
                        </div>
                    </div>
                    
                    <!-- Properties Panel -->
                    <div class="properties-panel">
                        <div class="properties-title">
                            <span class="dashicons dashicons-admin-settings"></span>
                            Element Properties
                        </div>
                        <div id="element-properties">
                            <div class="empty-state" style="padding: 30px 15px;">
                                <div style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;">⚙️</div>
                                <div style="font-size: 14px; color: #6b7280;">
                                    Select an element to edit its properties
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
                        <?php if ($template && !empty($template->template_data)): ?>
                            <!-- Load existing template -->
                            <div class="pdf-page" data-page="1">
                                <div class="page-number">Page 1</div>
                                <div class="page-controls">
                                    <button type="button" class="page-control-btn delete-page-btn" title="Delete Page" data-page="1">✕</button>
                                </div>
                                <!-- Template elements will be loaded here via JavaScript -->
                            </div>
                        <?php else: ?>
                            <!-- Empty template -->
                            <div class="pdf-page" data-page="1">
                                <div class="page-number">Page 1</div>
                                <div class="page-controls">
                                    <button type="button" class="page-control-btn delete-page-btn" title="Delete Page" data-page="1">✕</button>
                                </div>
                                <div class="empty-state">
                                    <div class="empty-icon">📄</div>
                                    <div class="empty-title">Start Building Your Template</div>
                                    <div class="empty-text">
                                        Drag elements from the sidebar to add them to your PDF template.
                                        Create professional documents with our visual builder.
                                    </div>
                                    <button type="button" class="builder-btn primary" onclick="addFirstElement()">
                                        <span class="dashicons dashicons-plus-alt2"></span>
                                        Add Your First Element
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
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
        let isDragging = false;
        let currentElementId = 0;
        
        // Initialize drag and drop
        $('.element-item').on('dragstart', function(e) {
            e.originalEvent.dataTransfer.setData('text/plain', $(this).data('type'));
            $(this).addClass('dragging');
            isDragging = true;
        });
        
        $('.element-item').on('dragend', function() {
            $(this).removeClass('dragging');
            isDragging = false;
        });
        
        // Canvas drop zone events
        $(document).on('dragover', '.pdf-page', function(e) {
            e.preventDefault();
            if (isDragging) {
                $(this).addClass('drop-zone');
            }
        });
        
        $(document).on('dragleave', '.pdf-page', function(e) {
            // Only remove drop-zone if we're leaving the page element entirely
            if (!$(e.relatedTarget).closest('.pdf-page').length) {
                $(this).removeClass('drop-zone');
            }
        });
        
        $(document).on('drop', '.pdf-page', function(e) {
            e.preventDefault();
            $(this).removeClass('drop-zone');
            
            if (!isDragging) return;
            
            const elementType = e.originalEvent.dataTransfer.getData('text/plain');
            const rect = this.getBoundingClientRect();
            const x = Math.max(10, e.originalEvent.clientX - rect.left - 10);
            const y = Math.max(10, e.originalEvent.clientY - rect.top - 10);
            
            addElementToCanvas(elementType, x, y, $(this));
        });
        
        // Add element to canvas function
        function addElementToCanvas(type, x, y, $page) {
            currentElementId++;
            const elementId = 'element_' + Date.now() + '_' + currentElementId;
            
            let elementHtml = '';
            let width = 150;
            let height = 30;
            let content = '';
            
            switch(type) {
                case 'text':
                    content = 'Sample Text';
                    elementHtml = `<div class="element-content" style="padding: 5px; word-wrap: break-word;">${content}</div>`;
                    break;
                case 'image':
                    content = 'Image';
                    elementHtml = '<div class="element-content" style="background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 12px; border: 2px dashed #d1d5db;">📷 Image Placeholder</div>';
                    height = 100;
                    break;
                case 'line':
                    content = '';
                    elementHtml = '<div class="element-content" style="border-top: 2px solid #000; width: 100%; height: 2px; margin-top: 50%;"></div>';
                    height = 5;
                    width = 200;
                    break;
                case 'rectangle':
                    content = 'Rectangle';
                    elementHtml = '<div class="element-content" style="border: 2px solid #000; width: 100%; height: 100%; box-sizing: border-box; display: flex; align-items: center; justify-content: center; font-size: 12px;">Rectangle</div>';
                    height = 100;
                    break;
                case 'form-field':
                    content = '{field_name}';
                    elementHtml = `<div class="element-content" style="padding: 5px; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 4px;">${content}</div>`;
                    break;
                case 'qr-code':
                    content = 'https://example.com';
                    elementHtml = '<div class="element-content" style="background: #000; color: white; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;">QR</div>';
                    width = 100;
                    height = 100;
                    break;
                case 'barcode':
                    content = '123456789';
                    elementHtml = '<div class="element-content" style="background: repeating-linear-gradient(90deg, #000 0px, #000 2px, #fff 2px, #fff 4px); height: 60%; margin-bottom: 5px;"></div><div style="text-align: center; font-size: 10px;">123456789</div>';
                    width = 200;
                    height = 60;
                    break;
                default:
                    content = type.charAt(0).toUpperCase() + type.slice(1);
                    elementHtml = '<div class="element-content" style="padding: 5px;">' + content + '</div>';
            }
            
            const $element = $(`
                <div class="pdf-element" data-id="${elementId}" data-type="${type}" data-content="${content}"
                     style="left: ${x}px; top: ${y}px; width: ${width}px; height: ${height}px;">
                    ${elementHtml}
                    <div class="element-controls">
                        <button type="button" class="control-btn duplicate-btn" title="Duplicate">⧉</button>
                        <button type="button" class="control-btn delete-btn" title="Delete">✕</button>
                    </div>
                </div>
            `);
            
            $page.append($element);
            
            // Make element interactive
            makeElementInteractive($element);
            
            // Auto-select new element
            selectElement($element);
            
            // Update template data
            updateTemplateData();
            
            // Show success message
            showNotification('Added ' + type + ' element successfully!', 'success');
        }
        
        // Make element interactive
        function makeElementInteractive($element) {
            // Click to select
            $element.off('click').on('click', function(e) {
                e.stopPropagation();
                selectElement($(this));
            });
            
            // Make draggable
            $element.draggable({
                containment: 'parent',
                grid: [10, 10],
                stop: function() {
                    updateTemplateData();
                    updatePropertiesPanel($element);
                }
            });
            
            // Make resizable
            $element.resizable({
                containment: 'parent',
                grid: [10, 10],
                handles: 'n, e, s, w, ne, nw, se, sw',
                minWidth: 20,
                minHeight: 10,
                stop: function() {
                    updateTemplateData();
                    updatePropertiesPanel($element);
                }
            });
        }
        
        // Select element function
        function selectElement($element) {
            $('.pdf-element').removeClass('selected');
            $element.addClass('selected');
            updatePropertiesPanel($element);
        }
        
        // Update properties panel
        function updatePropertiesPanel($element) {
            if (!$element || !$element.length) {
                $('#element-properties').html(`
                    <div class="empty-state" style="padding: 30px 15px;">
                        <div style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;">⚙️</div>
                        <div style="font-size: 14px; color: #6b7280;">
                            Select an element to edit its properties
                        </div>
                    </div>
                `);
                return;
            }
            
            const type = $element.data('type');
            const content = $element.data('content') || $element.find('.element-content').text();
            
            let propertiesHtml = `
                <div class="property-group" style="margin-bottom: 20px;">
                    <h6 style="margin-bottom: 12px; font-size: 13px; font-weight: 600; color: #374151;">Position & Size</h6>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 4px; display: block;">X Position</label>
                            <input type="number" class="property-input" data-property="x" value="${parseInt($element.css('left'))}" style="width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 4px; display: block;">Y Position</label>
                            <input type="number" class="property-input" data-property="y" value="${parseInt($element.css('top'))}" style="width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 4px; display: block;">Width</label>
                            <input type="number" class="property-input" data-property="width" value="${$element.width()}" style="width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 4px; display: block;">Height</label>
                            <input type="number" class="property-input" data-property="height" value="${$element.height()}" style="width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                        </div>
                    </div>
                </div>
            `;
            
            if (type === 'text' || type === 'form-field' || type === 'qr-code' || type === 'barcode') {
                propertiesHtml += `
                    <div class="property-group" style="margin-bottom: 20px;">
                        <h6 style="margin-bottom: 12px; font-size: 13px; font-weight: 600; color: #374151;">Content</h6>
                        <textarea class="property-input" data-property="content" rows="3" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px; resize: vertical;">${content}</textarea>
                    </div>
                `;
            }
            
            if (type === 'text') {
                propertiesHtml += `
                    <div class="property-group">
                        <h6 style="margin-bottom: 12px; font-size: 13px; font-weight: 600; color: #374151;">Typography</h6>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label style="font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 4px; display: block;">Font Size</label>
                                <input type="number" class="property-input" data-property="fontSize" value="14" style="width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 4px; display: block;">Font Weight</label>
                                <select class="property-input" data-property="fontWeight" style="width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                                    <option value="normal">Normal</option>
                                    <option value="bold">Bold</option>
                                    <option value="600">Semi Bold</option>
                                </select>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            $('#element-properties').html(propertiesHtml);
            
            // Bind property changes
            $('.property-input').off('input change').on('input change', function() {
                const property = $(this).data('property');
                const value = $(this).val();
                
                switch(property) {
                    case 'x':
                        $element.css('left', Math.max(0, value) + 'px');
                        break;
                    case 'y':
                        $element.css('top', Math.max(0, value) + 'px');
                        break;
                    case 'width':
                        $element.width(Math.max(20, value));
                        break;
                    case 'height':
                        $element.height(Math.max(10, value));
                        break;
                    case 'content':
                        $element.data('content', value);
                        if (type === 'text' || type === 'form-field') {
                            $element.find('.element-content').text(value);
                        }
                        break;
                    case 'fontSize':
                        $element.find('.element-content').css('font-size', value + 'px');
                        break;
                    case 'fontWeight':
                        $element.find('.element-content').css('font-weight', value);
                        break;
                }
                
                updateTemplateData();
            });
        }
        
        // Clear selection when clicking canvas
        $(document).on('click', '.pdf-page', function(e) {
            if (e.target === this || $(e.target).hasClass('empty-state') || $(e.target).parent().hasClass('empty-state')) {
                $('.pdf-element').removeClass('selected');
                $('#element-properties').html(`
                    <div class="empty-state" style="padding: 30px 15px;">
                        <div style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;">⚙️</div>
                        <div style="font-size: 14px; color: #6b7280;">
                            Select an element to edit its properties
                        </div>
                    </div>
                `);
            }
        });
        
        // Delete element
        $(document).on('click', '.delete-btn', function(e) {
            e.stopPropagation();
            if (confirm('Are you sure you want to delete this element?')) {
                $(this).closest('.pdf-element').remove();
                $('#element-properties').html(`
                    <div class="empty-state" style="padding: 30px 15px;">
                        <div style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;">⚙️</div>
                        <div style="font-size: 14px; color: #6b7280;">
                            Select an element to edit its properties
                        </div>
                    </div>
                `);
                updateTemplateData();
                showNotification('Element deleted', 'success');
            }
        });
        
        // Duplicate element
        $(document).on('click', '.duplicate-btn', function(e) {
            e.stopPropagation();
            const $original = $(this).closest('.pdf-element');
            const $clone = $original.clone();
            
            // Update position
            const newLeft = parseInt($original.css('left')) + 20;
            const newTop = parseInt($original.css('top')) + 20;
            $clone.css({ left: newLeft + 'px', top: newTop + 'px' });
            
            // Update ID
            currentElementId++;
            const newId = 'element_' + Date.now() + '_' + currentElementId;
            $clone.data('id', newId);
            $clone.attr('data-id', newId);
            
            $original.parent().append($clone);
            
            // Re-initialize interactions
            makeElementInteractive($clone);
            
            selectElement($clone);
            updateTemplateData();
            showNotification('Element duplicated', 'success');
        });
        
        // Delete page
        $(document).on('click', '.delete-page-btn', function(e) {
            e.stopPropagation();
            const $page = $(this).closest('.pdf-page');
            const pageCount = $('.pdf-page').length;
            
            if (pageCount <= 1) {
                showNotification('Cannot delete the last page', 'warning');
                return;
            }
            
            if (confirm('Are you sure you want to delete this page and all its elements?')) {
                $page.remove();
                renumberPages();
                updateTemplateData();
                showNotification('Page deleted successfully', 'success');
            }
        });
        
        // Save template
        $('#save-template').on('click', function() {
            const templateName = $('#template-name').val().trim();
            if (!templateName) {
                showNotification('Please enter a template name', 'warning');
                $('#template-name').focus();
                return;
            }
            
            const $btn = $(this);
            const originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update reverse2pdf-spin"></span> Saving...');
            
            const templateData = collectTemplateData();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_save_template',
                    template_id: $('#template-id').val(),
                    template_name: templateName,
                    template_data: JSON.stringify(templateData),
                    nonce: '<?php echo wp_create_nonce('reverse2pdf_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Template saved successfully! ✨', 'success');
                        if (!$('#template-id').val() && response.data.template_id) {
                            $('#template-id').val(response.data.template_id);
                            // Update URL
                            const newUrl = new URL(window.location);
                            newUrl.searchParams.set('template_id', response.data.template_id);
                            history.replaceState({}, '', newUrl);
                        }
                    } else {
                        showNotification('Save failed: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    showNotification('Save request failed', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });
        
        // Test PDF generation
        $('#test-template').on('click', function() {
            const templateId = $('#template-id').val();
            if (!templateId) {
                showNotification('Please save the template first', 'warning');
                return;
            }
            
            const $btn = $(this);
            const originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update reverse2pdf-spin"></span> Generating...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_generate_pdf',
                    template_id: templateId,
                    form_data: { 
                        test_name: 'John Doe', 
                        test_email: 'john@example.com',
                        test_message: 'This is a test message for PDF generation.',
                        field_name: 'Sample Field Value'
                    },
                    nonce: '<?php echo wp_create_nonce('reverse2pdf_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Test PDF generated successfully! 🎉', 'success');
                        window.open(response.data.pdf_url, '_blank');
                    } else {
                        showNotification('PDF generation failed: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    showNotification('Generation request failed', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });
        
        // Add page
        $('#add-page').on('click', function() {
            const pageNumber = $('.pdf-page').length + 1;
            const $newPage = $(`
                <div class="pdf-page" data-page="${pageNumber}">
                    <div class="page-number">Page ${pageNumber}</div>
                    <div class="page-controls">
                        <button type="button" class="page-control-btn delete-page-btn" title="Delete Page" data-page="${pageNumber}">✕</button>
                    </div>
                </div>
            `);
            
            $('#pdf-canvas').append($newPage);
            showNotification('New page added successfully', 'success');
            updateTemplateData();
            
            // Scroll to new page
            $newPage[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        
        // Preview template
        $('#preview-template').on('click', function() {
            const templateData = collectTemplateData();
            if (!templateData.pages || templateData.pages.length === 0) {
                showNotification('No template content to preview', 'warning');
                return;
            }
            
            // Create a simple preview window
            const previewWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
            const previewHtml = generatePreviewHtml(templateData);
            
            previewWindow.document.write(previewHtml);
            previewWindow.document.close();
            
            showNotification('Preview opened in new window', 'info');
        });
        
        // Utility functions
        function updateTemplateData() {
            const templateData = collectTemplateData();
            $('#template-data').val(JSON.stringify(templateData));
        }
        
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
                        content: $el.data('content') || $el.find('.element-content').text() || '',
                        fontSize: parseInt($el.find('.element-content').css('font-size')) || 14,
                        fontWeight: $el.find('.element-content').css('font-weight') || 'normal'
                    });
                });
                
                pages.push({
                    id: 'page_' + (index + 1),
                    width: 595,
                    height: 842,
                    elements: elements
                });
            });
            
            return { pages: pages };
        }
        
        function renumberPages() {
            $('.pdf-page').each(function(index) {
                const pageNum = index + 1;
                $(this).data('page', pageNum);
                $(this).find('.page-number').text('Page ' + pageNum);
                $(this).find('.delete-page-btn').data('page', pageNum);
            });
        }
        
        function generatePreviewHtml(templateData) {
            let html = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Template Preview</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
                        .preview-page { 
                            background: white; 
                            width: 595px; 
                            min-height: 842px; 
                            margin: 0 auto 20px; 
                            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
                            position: relative;
                            page-break-after: always;
                        }
                        .preview-element { position: absolute; }
                    </style>
                </head>
                <body>
            `;
            
            templateData.pages.forEach((page, pageIndex) => {
                html += `<div class="preview-page">`;
                
                if (page.elements) {
                    page.elements.forEach(element => {
                        let elementStyle = `left: ${element.x}px; top: ${element.y}px; width: ${element.width}px; height: ${element.height}px;`;
                        if (element.fontSize) elementStyle += ` font-size: ${element.fontSize}px;`;
                        if (element.fontWeight) elementStyle += ` font-weight: ${element.fontWeight};`;
                        
                        html += `<div class="preview-element" style="${elementStyle}">${element.content || element.type}</div>`;
                    });
                }
                
                html += `</div>`;
            });
            
            html += `</body></html>`;
            return html;
        }
        
        function showNotification(message, type) {
            const typeClass = type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success');
            const $notification = $(`
                <div class="notice notice-${typeClass} is-dismissible" style="position: fixed; top: 50px; right: 20px; z-index: 999999; max-width: 350px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <p><strong>${message}</strong></p>
                </div>
            `);
            
            $('body').append($notification);
            
            setTimeout(() => {
                $notification.fadeOut(500, function() {
                    $(this).remove();
                });
            }, 4000);
        }
        
        // Add first element helper
        window.addFirstElement = function() {
            addElementToCanvas('text', 50, 100, $('.pdf-page').first());
        };
        
        // Load existing template data if available
        const existingData = $('#template-data').val();
        if (existingData && existingData !== '') {
            try {
                const templateData = JSON.parse(existingData);
                loadTemplateData(templateData);
            } catch (e) {
                console.warn('Could not parse template data:', e);
            }
        }
        
        function loadTemplateData(data) {
            if (!data.pages || !data.pages.length) return;
            
            $('#pdf-canvas').empty();
            
            data.pages.forEach((page, pageIndex) => {
                const pageNum = pageIndex + 1;
                const $page = $(`
                    <div class="pdf-page" data-page="${pageNum}">
                        <div class="page-number">Page ${pageNum}</div>
                        <div class="page-controls">
                            <button type="button" class="page-control-btn delete-page-btn" title="Delete Page" data-page="${pageNum}">✕</button>
                        </div>
                    </div>
                `);
                
                $('#pdf-canvas').append($page);
                
                if (page.elements && page.elements.length) {
                    page.elements.forEach(element => {
                        let elementHtml = '';
                        
                        switch(element.type) {
                            case 'text':
                                elementHtml = `<div class="element-content" style="padding: 5px; font-size: ${element.fontSize || 14}px; font-weight: ${element.fontWeight || 'normal'};">${element.content || 'Text'}</div>`;
                                break;
                            case 'image':
                                elementHtml = '<div class="element-content" style="background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 12px; border: 2px dashed #d1d5db;">📷 Image</div>';
                                break;
                            case 'line':
                                elementHtml = '<div class="element-content" style="border-top: 2px solid #000; width: 100%; height: 2px; margin-top: 50%;"></div>';
                                break;
                            case 'rectangle':
                                elementHtml = '<div class="element-content" style="border: 2px solid #000; width: 100%; height: 100%; box-sizing: border-box; display: flex; align-items: center; justify-content: center; font-size: 12px;">Rectangle</div>';
                                break;
                            case 'form-field':
                                elementHtml = `<div class="element-content" style="padding: 5px; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 4px;">${element.content || '{field}'}</div>`;
                                break;
                            case 'qr-code':
                                elementHtml = '<div class="element-content" style="background: #000; color: white; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;">QR</div>';
                                break;
                            case 'barcode':
                                elementHtml = '<div class="element-content" style="background: repeating-linear-gradient(90deg, #000 0px, #000 2px, #fff 2px, #fff 4px); height: 60%; margin-bottom: 5px;"></div><div style="text-align: center; font-size: 10px;">123456789</div>';
                                break;
                            default:
                                elementHtml = `<div class="element-content" style="padding: 5px;">${element.content || element.type}</div>`;
                        }
                        
                        const $element = $(`
                            <div class="pdf-element" data-id="${element.id}" data-type="${element.type}" data-content="${element.content || ''}"
                                 style="left: ${element.x}px; top: ${element.y}px; width: ${element.width}px; height: ${element.height}px;">
                                ${elementHtml}
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
    });
    </script>
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
