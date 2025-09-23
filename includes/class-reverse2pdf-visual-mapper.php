<?php
if (!defined('ABSPATH')) {
    exit;
}

class Reverse2PDF_Visual_Mapper {
    
    private $element_types;
    private $canvas_settings;
    
    public function __construct() {
        $this->init_element_types();
        $this->init_canvas_settings();
        add_action('admin_enqueue_scripts', array($this, 'enqueue_builder_assets'));
        add_action('wp_ajax_reverse2pdf_save_template_data', array($this, 'save_template_data'));
        add_action('wp_ajax_reverse2pdf_load_template_data', array($this, 'load_template_data'));
        add_action('wp_ajax_reverse2pdf_auto_map_fields', array($this, 'auto_map_fields'));
    }
    
    /**
     * Initialize element types for the visual builder
     */
    private function init_element_types() {
        $this->element_types = array(
            'basic' => array(
                'text' => array(
                    'name' => __('Text', 'reverse2pdf'),
                    'icon' => 'dashicons-editor-textcolor',
                    'category' => 'basic',
                    'properties' => array(
                        'content' => array('type' => 'textarea', 'label' => 'Content', 'default' => 'Sample Text'),
                        'fontSize' => array('type' => 'number', 'label' => 'Font Size', 'default' => 12, 'min' => 6, 'max' => 72),
                        'fontFamily' => array('type' => 'select', 'label' => 'Font Family', 'default' => 'Arial', 'options' => array('Arial', 'Times', 'Helvetica', 'Georgia')),
                        'fontWeight' => array('type' => 'select', 'label' => 'Font Weight', 'default' => 'normal', 'options' => array('normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900')),
                        'textAlign' => array('type' => 'select', 'label' => 'Text Align', 'default' => 'left', 'options' => array('left', 'center', 'right', 'justify')),
                        'color' => array('type' => 'color', 'label' => 'Text Color', 'default' => '#000000'),
                        'lineHeight' => array('type' => 'number', 'label' => 'Line Height', 'default' => 1.4, 'step' => 0.1, 'min' => 0.5, 'max' => 3)
                    )
                ),
                'image' => array(
                    'name' => __('Image', 'reverse2pdf'),
                    'icon' => 'dashicons-format-image',
                    'category' => 'basic',
                    'properties' => array(
                        'src' => array('type' => 'media', 'label' => 'Image URL', 'default' => ''),
                        'alt' => array('type' => 'text', 'label' => 'Alt Text', 'default' => ''),
                        'objectFit' => array('type' => 'select', 'label' => 'Object Fit', 'default' => 'contain', 'options' => array('contain', 'cover', 'fill', 'scale-down', 'none')),
                        'borderRadius' => array('type' => 'number', 'label' => 'Border Radius', 'default' => 0, 'min' => 0, 'max' => 100),
                        'opacity' => array('type' => 'number', 'label' => 'Opacity', 'default' => 1, 'min' => 0, 'max' => 1, 'step' => 0.1)
                    )
                ),
                'line' => array(
                    'name' => __('Line', 'reverse2pdf'),
                    'icon' => 'dashicons-minus',
                    'category' => 'basic',
                    'properties' => array(
                        'thickness' => array('type' => 'number', 'label' => 'Thickness', 'default' => 1, 'min' => 1, 'max' => 10),
                        'color' => array('type' => 'color', 'label' => 'Color', 'default' => '#000000'),
                        'style' => array('type' => 'select', 'label' => 'Style', 'default' => 'solid', 'options' => array('solid', 'dashed', 'dotted')),
                        'opacity' => array('type' => 'number', 'label' => 'Opacity', 'default' => 1, 'min' => 0, 'max' => 1, 'step' => 0.1)
                    )
                ),
                'rectangle' => array(
                    'name' => __('Rectangle', 'reverse2pdf'),
                    'icon' => 'dashicons-admin-page',
                    'category' => 'basic',
                    'properties' => array(
                        'fillColor' => array('type' => 'color', 'label' => 'Fill Color', 'default' => 'transparent'),
                        'borderColor' => array('type' => 'color', 'label' => 'Border Color', 'default' => '#000000'),
                        'borderWidth' => array('type' => 'number', 'label' => 'Border Width', 'default' => 1, 'min' => 0, 'max' => 10),
                        'borderRadius' => array('type' => 'number', 'label' => 'Border Radius', 'default' => 0, 'min' => 0, 'max' => 50),
                        'opacity' => array('type' => 'number', 'label' => 'Opacity', 'default' => 1, 'min' => 0, 'max' => 1, 'step' => 0.1)
                    )
                )
            ),
            'form' => array(
                'input' => array(
                    'name' => __('Input Field', 'reverse2pdf'),
                    'icon' => 'dashicons-edit',
                    'category' => 'form',
                    'properties' => array(
                        'fieldName' => array('type' => 'text', 'label' => 'Field Name', 'default' => 'field_name'),
                        'placeholder' => array('type' => 'text', 'label' => 'Placeholder', 'default' => 'Enter text...'),
                        'fontSize' => array('type' => 'number', 'label' => 'Font Size', 'default' => 12, 'min' => 6, 'max' => 24),
                        'fontFamily' => array('type' => 'select', 'label' => 'Font Family', 'default' => 'Arial', 'options' => array('Arial', 'Times', 'Helvetica')),
                        'borderWidth' => array('type' => 'number', 'label' => 'Border Width', 'default' => 1, 'min' => 0, 'max' => 5),
                        'borderColor' => array('type' => 'color', 'label' => 'Border Color', 'default' => '#cccccc'),
                        'backgroundColor' => array('type' => 'color', 'label' => 'Background', 'default' => '#ffffff')
                    )
                ),
                'textarea' => array(
                    'name' => __('Textarea', 'reverse2pdf'),
                    'icon' => 'dashicons-editor-alignleft',
                    'category' => 'form',
                    'properties' => array(
                        'fieldName' => array('type' => 'text', 'label' => 'Field Name', 'default' => 'field_name'),
                        'placeholder' => array('type' => 'text', 'label' => 'Placeholder', 'default' => 'Enter text...'),
                        'rows' => array('type' => 'number', 'label' => 'Rows', 'default' => 4, 'min' => 1, 'max' => 20),
                        'fontSize' => array('type' => 'number', 'label' => 'Font Size', 'default' => 12, 'min' => 6, 'max' => 24),
                        'fontFamily' => array('type' => 'select', 'label' => 'Font Family', 'default' => 'Arial', 'options' => array('Arial', 'Times', 'Helvetica')),
                        'borderWidth' => array('type' => 'number', 'label' => 'Border Width', 'default' => 1, 'min' => 0, 'max' => 5),
                        'borderColor' => array('type' => 'color', 'label' => 'Border Color', 'default' => '#cccccc'),
                        'backgroundColor' => array('type' => 'color', 'label' => 'Background', 'default' => '#ffffff')
                    )
                ),
                'checkbox' => array(
                    'name' => __('Checkbox', 'reverse2pdf'),
                    'icon' => 'dashicons-yes-alt',
                    'category' => 'form',
                    'properties' => array(
                        'fieldName' => array('type' => 'text', 'label' => 'Field Name', 'default' => 'checkbox_field'),
                        'label' => array('type' => 'text', 'label' => 'Label', 'default' => 'Checkbox Label'),
                        'checked' => array('type' => 'checkbox', 'label' => 'Checked by Default', 'default' => false),
                        'fontSize' => array('type' => 'number', 'label' => 'Font Size', 'default' => 12, 'min' => 6, 'max' => 24),
                        'color' => array('type' => 'color', 'label' => 'Text Color', 'default' => '#000000')
                    )
                )
            ),
            'advanced' => array(
                'table' => array(
                    'name' => __('Table', 'reverse2pdf'),
                    'icon' => 'dashicons-grid-view',
                    'category' => 'advanced',
                    'properties' => array(
                        'rows' => array('type' => 'number', 'label' => 'Rows', 'default' => 3, 'min' => 1, 'max' => 20),
                        'columns' => array('type' => 'number', 'label' => 'Columns', 'default' => 3, 'min' => 1, 'max' => 10),
                        'hasHeader' => array('type' => 'checkbox', 'label' => 'Has Header', 'default' => true),
                        'borderWidth' => array('type' => 'number', 'label' => 'Border Width', 'default' => 1, 'min' => 0, 'max' => 5),
                        'borderColor' => array('type' => 'color', 'label' => 'Border Color', 'default' => '#cccccc'),
                        'headerBg' => array('type' => 'color', 'label' => 'Header Background', 'default' => '#f0f0f0'),
                        'fontSize' => array('type' => 'number', 'label' => 'Font Size', 'default' => 11, 'min' => 6, 'max' => 18)
                    )
                ),
                'qr' => array(
                    'name' => __('QR Code', 'reverse2pdf'),
                    'icon' => 'dashicons-screenoptions',
                    'category' => 'advanced',
                    'properties' => array(
                        'value' => array('type' => 'text', 'label' => 'QR Value', 'default' => 'https://example.com'),
                        'size' => array('type' => 'number', 'label' => 'Size', 'default' => 100, 'min' => 50, 'max' => 300),
                        'errorCorrection' => array('type' => 'select', 'label' => 'Error Correction', 'default' => 'M', 'options' => array('L', 'M', 'Q', 'H')),
                        'margin' => array('type' => 'number', 'label' => 'Margin', 'default' => 0, 'min' => 0, 'max' => 10)
                    )
                ),
                'barcode' => array(
                    'name' => __('Barcode', 'reverse2pdf'),
                    'icon' => 'dashicons-admin-links',
                    'category' => 'advanced',
                    'properties' => array(
                        'value' => array('type' => 'text', 'label' => 'Barcode Value', 'default' => '123456789'),
                        'barcodeType' => array('type' => 'select', 'label' => 'Barcode Type', 'default' => 'code128', 'options' => array('code128', 'code39', 'ean13', 'upca')),
                        'includeText' => array('type' => 'checkbox', 'label' => 'Include Text', 'default' => true),
                        'textSize' => array('type' => 'number', 'label' => 'Text Size', 'default' => 10, 'min' => 6, 'max' => 16)
                    )
                ),
                'signature' => array(
                    'name' => __('Signature Field', 'reverse2pdf'),
                    'icon' => 'dashicons-edit-large',
                    'category' => 'advanced',
                    'properties' => array(
                        'fieldName' => array('type' => 'text', 'label' => 'Field Name', 'default' => 'signature'),
                        'label' => array('type' => 'text', 'label' => 'Label', 'default' => 'Signature'),
                        'showDate' => array('type' => 'checkbox', 'label' => 'Show Date', 'default' => true),
                        'borderStyle' => array('type' => 'select', 'label' => 'Border Style', 'default' => 'bottom', 'options' => array('none', 'bottom', 'box')),
                        'fontSize' => array('type' => 'number', 'label' => 'Font Size', 'default' => 12, 'min' => 6, 'max' => 18)
                    )
                )
            ),
            'wordpress' => array(
                'post_title' => array(
                    'name' => __('Post Title', 'reverse2pdf'),
                    'icon' => 'dashicons-format-aside',
                    'category' => 'wordpress',
                    'properties' => array(
                        'postId' => array('type' => 'number', 'label' => 'Post ID (0 = current)', 'default' => 0),
                        'fontSize' => array('type' => 'number', 'label' => 'Font Size', 'default' => 18, 'min' => 6, 'max' => 48),
                        'fontFamily' => array('type' => 'select', 'label' => 'Font Family', 'default' => 'Arial', 'options' => array('Arial', 'Times', 'Helvetica')),
                        'fontWeight' => array('type' => 'select', 'label' => 'Font Weight', 'default' => 'bold', 'options' => array('normal', 'bold')),
                        'color' => array('type' => 'color', 'label' => 'Color', 'default' => '#000000')
                    )
                ),
                'post_content' => array(
                    'name' => __('Post Content', 'reverse2pdf'),
                    'icon' => 'dashicons-admin-post',
                    'category' => 'wordpress',
                    'properties' => array(
                        'postId' => array('type' => 'number', 'label' => 'Post ID (0 = current)', 'default' => 0),
                        'stripTags' => array('type' => 'checkbox', 'label' => 'Strip HTML Tags', 'default' => false),
                        'wordLimit' => array('type' => 'number', 'label' => 'Word Limit (0 = no limit)', 'default' => 0, 'min' => 0),
                        'fontSize' => array('type' => 'number', 'label' => 'Font Size', 'default' => 12, 'min' => 6, 'max' => 24),
                        'lineHeight' => array('type' => 'number', 'label' => 'Line Height', 'default' => 1.4, 'step' => 0.1)
                    )
                ),
                'featured_image' => array(
                    'name' => __('Featured Image', 'reverse2pdf'),
                    'icon' => 'dashicons-format-gallery',
                    'category' => 'wordpress',
                    'properties' => array(
                        'postId' => array('type' => 'number', 'label' => 'Post ID (0 = current)', 'default' => 0),
                        'size' => array('type' => 'select', 'label' => 'Image Size', 'default' => 'medium', 'options' => array('thumbnail', 'medium', 'large', 'full')),
                        'fallback' => array('type' => 'media', 'label' => 'Fallback Image', 'default' => ''),
                        'objectFit' => array('type' => 'select', 'label' => 'Object Fit', 'default' => 'contain', 'options' => array('contain', 'cover', 'fill'))
                    )
                )
            )
        );
        
        $this->element_types = apply_filters('reverse2pdf_element_types', $this->element_types);
    }
    
    /**
     * Initialize canvas settings
     */
    private function init_canvas_settings() {
        $this->canvas_settings = array(
            'paper_sizes' => array(
                'A4' => array('width' => 595, 'height' => 842),
                'A3' => array('width' => 842, 'height' => 1191),
                'Letter' => array('width' => 612, 'height' => 792),
                'Legal' => array('width' => 612, 'height' => 1008),
                'Custom' => array('width' => 595, 'height' => 842)
            ),
            'orientations' => array('portrait', 'landscape'),
            'units' => array('px', 'mm', 'in'),
            'zoom_levels' => array(0.25, 0.5, 0.75, 1, 1.25, 1.5, 2),
            'grid_sizes' => array(5, 10, 15, 20, 25, 50),
            'snap_threshold' => 5
        );
    }
    
    /**
     * Enqueue builder assets
     */
    public function enqueue_builder_assets($hook) {
        if (strpos($hook, 'reverse2pdf-builder') === false && strpos($hook, 'reverse2pdf-add-template') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'reverse2pdf-builder',
            REVERSE2PDF_PLUGIN_URL . 'assets/css/template-builder.css',
            array('wp-color-picker'),
            REVERSE2PDF_VERSION
        );
        
        // JavaScript
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_script('jquery-ui-resizable');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_media();
        
        wp_enqueue_script(
            'reverse2pdf-builder',
            REVERSE2PDF_PLUGIN_URL . 'assets/js/template-builder.js',
            array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-resizable', 'jquery-ui-sortable', 'wp-color-picker'),
            REVERSE2PDF_VERSION,
            true
        );
        
        wp_localize_script('reverse2pdf-builder', 'reverse2pdf_builder', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reverse2pdf_nonce'),
            'element_types' => $this->element_types,
            'canvas_settings' => $this->canvas_settings,
            'strings' => array(
                'save' => __('Save Template', 'reverse2pdf'),
                'saving' => __('Saving...', 'reverse2pdf'),
                'saved' => __('Template Saved', 'reverse2pdf'),
                'error' => __('Error occurred', 'reverse2pdf'),
                'delete_element' => __('Delete Element', 'reverse2pdf'),
                'duplicate_element' => __('Duplicate Element', 'reverse2pdf'),
                'copy_element' => __('Copy Element', 'reverse2pdf'),
                'paste_element' => __('Paste Element', 'reverse2pdf'),
                'undo' => __('Undo', 'reverse2pdf'),
                'redo' => __('Redo', 'reverse2pdf'),
                'zoom_in' => __('Zoom In', 'reverse2pdf'),
                'zoom_out' => __('Zoom Out', 'reverse2pdf'),
                'fit_to_page' => __('Fit to Page', 'reverse2pdf'),
                'add_page' => __('Add Page', 'reverse2pdf'),
                'delete_page' => __('Delete Page', 'reverse2pdf'),
                'confirm_delete_page' => __('Are you sure you want to delete this page?', 'reverse2pdf'),
                'confirm_delete_element' => __('Are you sure you want to delete this element?', 'reverse2pdf'),
                'select_image' => __('Select Image', 'reverse2pdf'),
                'use_image' => __('Use Image', 'reverse2pdf'),
                'no_elements' => __('No elements on this page', 'reverse2pdf'),
                'loading' => __('Loading...', 'reverse2pdf'),
                'preview' => __('Preview', 'reverse2pdf'),
                'generate_pdf' => __('Generate PDF', 'reverse2pdf')
            )
        ));
    }
    
    /**
     * Render the visual builder interface
     */
    public function render_builder_interface($template_id = 0) {
        $template_data = null;
        $template_name = '';
        $template_description = '';
        
        if ($template_id) {
            global $wpdb;
            $template = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " WHERE id = %d",
                $template_id
            ));
            
            if ($template) {
                $template_data = json_decode($template->template_data, true);
                $template_name = $template->name;
                $template_description = $template->description;
            }
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php echo $template_id ? __('Edit Template', 'reverse2pdf') : __('Create Template', 'reverse2pdf'); ?>
            </h1>
            
            <div id="reverse2pdf-builder-container">
                <!-- Toolbar -->
                <div class="builder-toolbar">
                    <div class="toolbar-section toolbar-left">
                        <div class="template-info">
                            <input type="text" id="template-name" placeholder="<?php _e('Template Name', 'reverse2pdf'); ?>" 
                                   value="<?php echo esc_attr($template_name); ?>" class="template-name-input">
                            <input type="text" id="template-description" placeholder="<?php _e('Description (optional)', 'reverse2pdf'); ?>" 
                                   value="<?php echo esc_attr($template_description); ?>" class="template-desc-input">
                        </div>
                    </div>
                    
                    <div class="toolbar-section toolbar-center">
                        <div class="page-controls">
                            <button type="button" class="button" id="add-page" title="<?php _e('Add Page', 'reverse2pdf'); ?>">
                                <span class="dashicons dashicons-plus-alt"></span>
                            </button>
                            <div class="page-navigation">
                                <button type="button" class="button" id="prev-page" title="<?php _e('Previous Page', 'reverse2pdf'); ?>">
                                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                                </button>
                                <span class="page-info">
                                    <span id="current-page">1</span> / <span id="total-pages">1</span>
                                </span>
                                <button type="button" class="button" id="next-page" title="<?php _e('Next Page', 'reverse2pdf'); ?>">
                                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                                </button>
                            </div>
                            <button type="button" class="button" id="delete-page" title="<?php _e('Delete Page', 'reverse2pdf'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                        
                        <div class="zoom-controls">
                            <button type="button" class="button" id="zoom-out" title="<?php _e('Zoom Out', 'reverse2pdf'); ?>">
                                <span class="dashicons dashicons-minus"></span>
                            </button>
                            <select id="zoom-level">
                                <option value="0.25">25%</option>
                                <option value="0.5">50%</option>
                                <option value="0.75">75%</option>
                                <option value="1" selected>100%</option>
                                <option value="1.25">125%</option>
                                <option value="1.5">150%</option>
                                <option value="2">200%</option>
                            </select>
                            <button type="button" class="button" id="zoom-in" title="<?php _e('Zoom In', 'reverse2pdf'); ?>">
                                <span class="dashicons dashicons-plus-alt"></span>
                            </button>
                            <button type="button" class="button" id="fit-to-page" title="<?php _e('Fit to Page', 'reverse2pdf'); ?>">
                                <span class="dashicons dashicons-fullscreen-alt"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="toolbar-section toolbar-right">
                        <div class="action-buttons">
                            <button type="button" class="button" id="undo-btn" title="<?php _e('Undo', 'reverse2pdf'); ?>">
                                <span class="dashicons dashicons-undo"></span>
                            </button>
                            <button type="button" class="button" id="redo-btn" title="<?php _e('Redo', 'reverse2pdf'); ?>">
                                <span class="dashicons dashicons-redo"></span>
                            </button>
                            <button type="button" class="button" id="preview-btn">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php _e('Preview', 'reverse2pdf'); ?>
                            </button>
                            <button type="button" class="button button-primary" id="save-template">
                                <span class="dashicons dashicons-saved"></span>
                                <?php _e('Save Template', 'reverse2pdf'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Main builder area -->
                <div class="builder-main">
                    <!-- Elements panel -->
                    <div class="builder-sidebar left-sidebar">
                        <div class="sidebar-tabs">
                            <button class="sidebar-tab active" data-tab="elements">
                                <span class="dashicons dashicons-admin-page"></span>
                                <?php _e('Elements', 'reverse2pdf'); ?>
                            </button>
                            <button class="sidebar-tab" data-tab="pages">
                                <span class="dashicons dashicons-admin-multisite"></span>
                                <?php _e('Pages', 'reverse2pdf'); ?>
                            </button>
                        </div>
                        
                        <div class="sidebar-content">
                            <!-- Elements tab -->
                            <div class="tab-panel active" id="elements-panel">
                                <div class="elements-search">
                                    <input type="text" placeholder="<?php _e('Search elements...', 'reverse2pdf'); ?>" id="elements-search">
                                </div>
                                
                                <div class="elements-categories">
                                    <?php foreach ($this->element_types as $category => $elements): ?>
                                        <div class="element-category" data-category="<?php echo esc_attr($category); ?>">
                                            <h4 class="category-title">
                                                <?php echo esc_html(ucfirst($category)); ?>
                                                <span class="category-toggle"></span>
                                            </h4>
                                            <div class="category-elements">
                                                <?php foreach ($elements as $element_type => $element_config): ?>
                                                    <div class="element-item" data-type="<?php echo esc_attr($element_type); ?>"
                                                         data-category="<?php echo esc_attr($category); ?>" draggable="true">
                                                        <span class="dashicons <?php echo esc_attr($element_config['icon']); ?>"></span>
                                                        <span class="element-name"><?php echo esc_html($element_config['name']); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Pages tab -->
                            <div class="tab-panel" id="pages-panel">
                                <div class="pages-list">
                                    <!-- Pages will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Canvas area -->
                    <div class="builder-canvas-container">
                        <div class="canvas-header">
                            <div class="canvas-settings">
                                <select id="paper-size">
                                    <option value="A4">A4 (595 x 842)</option>
                                    <option value="A3">A3 (842 x 1191)</option>
                                    <option value="Letter">Letter (612 x 792)</option>
                                    <option value="Legal">Legal (612 x 1008)</option>
                                    <option value="Custom">Custom</option>
                                </select>
                                
                                <select id="orientation">
                                    <option value="portrait"><?php _e('Portrait', 'reverse2pdf'); ?></option>
                                    <option value="landscape"><?php _e('Landscape', 'reverse2pdf'); ?></option>
                                </select>
                                
                                <div class="canvas-tools">
                                    <label class="tool-checkbox">
                                        <input type="checkbox" id="show-grid" checked>
                                        <?php _e('Grid', 'reverse2pdf'); ?>
                                    </label>
                                    <label class="tool-checkbox">
                                        <input type="checkbox" id="snap-to-grid" checked>
                                        <?php _e('Snap', 'reverse2pdf'); ?>
                                    </label>
                                    <label class="tool-checkbox">
                                        <input type="checkbox" id="show-rulers" checked>
                                        <?php _e('Rulers', 'reverse2pdf'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="canvas-wrapper">
                            <div class="ruler ruler-horizontal" id="ruler-horizontal"></div>
                            <div class="ruler ruler-vertical" id="ruler-vertical"></div>
                            
                            <div class="canvas-scroll-area">
                                <div class="builder-canvas" id="builder-canvas">
                                    <div class="pdf-page" id="page-1" data-page="1">
                                        <div class="page-background"></div>
                                        <div class="page-content"></div>
                                        <div class="page-overlay"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Properties panel -->
                    <div class="builder-sidebar right-sidebar">
                        <div class="sidebar-tabs">
                            <button class="sidebar-tab active" data-tab="properties">
                                <span class="dashicons dashicons-admin-settings"></span>
                                <?php _e('Properties', 'reverse2pdf'); ?>
                            </button>
                            <button class="sidebar-tab" data-tab="layers">
                                <span class="dashicons dashicons-sort"></span>
                                <?php _e('Layers', 'reverse2pdf'); ?>
                            </button>
                        </div>
                        
                        <div class="sidebar-content">
                            <!-- Properties tab -->
                            <div class="tab-panel active" id="properties-panel">
                                <div class="properties-content">
                                    <div class="no-selection">
                                        <span class="dashicons dashicons-admin-settings"></span>
                                        <p><?php _e('Select an element to edit its properties', 'reverse2pdf'); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Layers tab -->
                            <div class="tab-panel" id="layers-panel">
                                <div class="layers-controls">
                                    <button type="button" class="button button-small" id="layer-up" title="<?php _e('Move Up', 'reverse2pdf'); ?>">
                                        <span class="dashicons dashicons-arrow-up-alt"></span>
                                    </button>
                                    <button type="button" class="button button-small" id="layer-down" title="<?php _e('Move Down', 'reverse2pdf'); ?>">
                                        <span class="dashicons dashicons-arrow-down-alt"></span>
                                    </button>
                                    <button type="button" class="button button-small" id="layer-duplicate" title="<?php _e('Duplicate', 'reverse2pdf'); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                    <button type="button" class="button button-small" id="layer-delete" title="<?php _e('Delete', 'reverse2pdf'); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                                
                                <div class="layers-list">
                                    <!-- Layers will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden data -->
                <input type="hidden" id="template-id" value="<?php echo esc_attr($template_id); ?>">
                <input type="hidden" id="template-data" value="<?php echo esc_attr(json_encode($template_data)); ?>">
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Save template data
     */
    public function save_template_data() {
        check_ajax_referer('reverse2pdf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        $template_name = sanitize_text_field($_POST['template_name'] ?? '');
        $template_description = sanitize_text_field($_POST['template_description'] ?? '');
        $template_data = wp_unslash($_POST['template_data'] ?? '');
        
        if (empty($template_name)) {
            wp_send_json_error('Template name is required');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . REVERSE2PDF_TABLE_TEMPLATES;
        
        $data = array(
            'name' => $template_name,
            'description' => $template_description,
            'template_data' => $template_data,
            'modified_date' => current_time('mysql')
        );
        
        if ($template_id) {
            $result = $wpdb->update($table, $data, array('id' => $template_id));
            if ($result !== false) {
                wp_send_json_success(array(
                    'template_id' => $template_id,
                    'message' => __('Template updated successfully', 'reverse2pdf')
                ));
            } else {
                wp_send_json_error('Failed to update template');
            }
        } else {
            $data['created_by'] = get_current_user_id();
            $data['created_date'] = current_time('mysql');
            $data['active'] = 1;
            
            $result = $wpdb->insert($table, $data);
            if ($result !== false) {
                wp_send_json_success(array(
                    'template_id' => $wpdb->insert_id,
                    'message' => __('Template created successfully', 'reverse2pdf')
                ));
            } else {
                wp_send_json_error('Failed to create template');
            }
        }
    }
    
    /**
     * AJAX: Load template data
     */
    public function load_template_data() {
        check_ajax_referer('reverse2pdf_nonce', 'nonce');
        
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if (!$template_id) {
            wp_send_json_error('Template ID required');
        }
        
        global $wpdb;
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}" . REVERSE2PDF_TABLE_TEMPLATES . " WHERE id = %d",
            $template_id
        ));
        
        if ($template) {
            wp_send_json_success(array(
                'name' => $template->name,
                'description' => $template->description,
                'template_data' => json_decode($template->template_data, true),
                'settings' => json_decode($template->settings ?: '{}', true)
            ));
        } else {
            wp_send_json_error('Template not found');
        }
    }
    
    /**
     * Get element types
     */
    public function get_element_types() {
        return $this->element_types;
    }
    
    /**
     * Get canvas settings
     */
    public function get_canvas_settings() {
        return $this->canvas_settings;
    }
}
?>
