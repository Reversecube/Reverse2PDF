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
