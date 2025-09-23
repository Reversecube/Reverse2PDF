/* ==========================================================================
   Reverse2PDF Pro - Professional Admin JavaScript
   ========================================================================== */

(function($) {
    'use strict';

    // Global namespace
    window.Reverse2PDF = window.Reverse2PDF || {};
    const R2PDF = window.Reverse2PDF;

    // Main Admin Object
    R2PDF.Admin = {
        
        // Initialize
        init: function() {
            this.bindEvents();
            this.initComponents();
            this.loadDashboardData();
            
            console.log('🚀 Reverse2PDF Admin initialized');
        },

        // Bind all events
        bindEvents: function() {
            // PDF Generation
            $(document).on('click', '.reverse2pdf-generate', this.handlePDFGeneration);
            
            // Template Management
            $(document).on('click', '#save-template', this.handleTemplateSave);
            $(document).on('click', '#preview-template', this.handleTemplatePreview);
            $(document).on('click', '#test-template', this.handleTemplateTest);
            
            // Form Integration
            $(document).on('click', '.setup-integration', this.handleIntegrationSetup);
            $(document).on('change', '#form-type', this.handleFormTypeChange);
            $(document).on('submit', '#integration-form', this.handleIntegrationSubmit);
            
            // Modal Events
            $(document).on('click', '.reverse2pdf-modal-close, .reverse2pdf-modal-overlay', this.handleModalClose);
            $(document).on('keyup', this.handleKeyboardEvents);
            
            // Stats Refresh
            $(document).on('click', '#refresh-stats', this.handleStatsRefresh);
            
            // Template Actions
            $(document).on('click', '.template-duplicate', this.handleTemplateDuplicate);
            $(document).on('click', '.template-delete', this.handleTemplateDelete);
            
            // Builder Events
            $(document).on('click', '.element-item', this.handleElementSelect);
            $(document).on('change', '.property-input', this.handlePropertyChange);
        },

        // Initialize components
        initComponents: function() {
            this.initTooltips();
            this.initAnimations();
            this.initDragAndDrop();
            this.initColorPickers();
            this.initCodeEditors();
        },

        // Load dashboard data
        loadDashboardData: function() {
            this.loadRecentActivity();
            this.loadSystemStatus();
            this.updateStatistics();
        },

        // Handle PDF Generation
        handlePDFGeneration: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const templateId = $button.data('template-id') || $('#template-id').val();
            const originalText = $button.html();
            
            if (!templateId) {
                R2PDF.Admin.showNotification('Please select a template first', 'warning');
                return;
            }

            // Show loading state
            $button.prop('disabled', true)
                   .html('<span class="dashicons dashicons-update reverse2pdf-spin"></span> Generating PDF...')
                   .addClass('reverse2pdf-loading');

            // Collect form data if available
            const formData = R2PDF.Admin.collectFormData();

            // AJAX request
            $.ajax({
                url: reverse2pdf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_generate_pdf',
                    template_id: templateId,
                    form_data: formData,
                    nonce: reverse2pdf_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        R2PDF.Admin.showNotification('PDF generated successfully! 🎉', 'success');
                        
                        // Show download link with animation
                        const downloadHtml = `
                            <div class="reverse2pdf-download-link reverse2pdf-fade-in">
                                <a href="${response.data.pdf_url}" target="_blank" class="reverse2pdf-btn reverse2pdf-btn-success">
                                    <span class="dashicons dashicons-download"></span> 
                                    View PDF
                                </a>
                                <button type="button" class="reverse2pdf-btn reverse2pdf-btn-secondary reverse2pdf-btn-sm" onclick="$(this).parent().fadeOut()">
                                    <span class="dashicons dashicons-no-alt"></span>
                                    Close
                                </button>
                            </div>
                        `;
                        
                        $button.after(downloadHtml);
                        
                        // Auto-remove after 15 seconds
                        setTimeout(() => {
                            $('.reverse2pdf-download-link').fadeOut(500, function() {
                                $(this).remove();
                            });
                        }, 15000);
                        
                        // Update statistics
                        R2PDF.Admin.updateStatistics();
                        
                    } else {
                        R2PDF.Admin.showNotification('Error: ' + (response.data || 'Unknown error occurred'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('PDF Generation Error:', error);
                    R2PDF.Admin.showNotification('Request failed. Please check your internet connection and try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false)
                           .html(originalText)
                           .removeClass('reverse2pdf-loading');
                }
            });
        },

        // Handle Template Save
        handleTemplateSave: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const templateId = $('#template-id').val() || 0;
            const templateName = $('#template-name').val().trim();
            const templateData = R2PDF.Admin.getTemplateData();
            const originalText = $button.html();
            
            // Validation
            if (!templateName) {
                R2PDF.Admin.showNotification('Please enter a template name', 'warning');
                $('#template-name').focus().addClass('reverse2pdf-pulse');
                setTimeout(() => $('#template-name').removeClass('reverse2pdf-pulse'), 2000);
                return;
            }

            if (!templateData || templateData === '{}') {
                R2PDF.Admin.showNotification('Please add some content to your template', 'warning');
                return;
            }

            // Show saving state
            $button.prop('disabled', true)
                   .html('<span class="dashicons dashicons-update reverse2pdf-spin"></span> Saving Template...')
                   .addClass('reverse2pdf-loading');

            $.ajax({
                url: reverse2pdf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_save_template',
                    template_id: templateId,
                    template_name: templateName,
                    template_data: templateData,
                    template_description: $('#template-description').val() || '',
                    nonce: reverse2pdf_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        R2PDF.Admin.showNotification('Template saved successfully! ✨', 'success');
                        
                        // Update template ID if new template
                        if (!templateId && response.data.template_id) {
                            $('#template-id').val(response.data.template_id);
                            
                            // Update URL without refresh
                            const newUrl = new URL(window.location);
                            newUrl.searchParams.set('template_id', response.data.template_id);
                            history.replaceState({}, '', newUrl);
                        }
                        
                        // Update last saved indicator
                        R2PDF.Admin.updateLastSavedTime();
                        
                    } else {
                        R2PDF.Admin.showNotification('Save failed: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    R2PDF.Admin.showNotification('Save request failed. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false)
                           .html(originalText)
                           .removeClass('reverse2pdf-loading');
                }
            });
        },

        // Handle Template Preview
        handleTemplatePreview: function(e) {
            e.preventDefault();
            
            const templateData = R2PDF.Admin.getTemplateData();
            if (!templateData) {
                R2PDF.Admin.showNotification('No template content to preview', 'warning');
                return;
            }

            // Create preview modal
            const previewHtml = `
                <div class="reverse2pdf-preview-container">
                    <div class="reverse2pdf-preview-toolbar">
                        <button type="button" class="reverse2pdf-btn reverse2pdf-btn-secondary" id="zoom-out">
                            <span class="dashicons dashicons-minus"></span> Zoom Out
                        </button>
                        <span class="zoom-level">100%</span>
                        <button type="button" class="reverse2pdf-btn reverse2pdf-btn-secondary" id="zoom-in">
                            <span class="dashicons dashicons-plus"></span> Zoom In
                        </button>
                        <div class="preview-spacer"></div>
                        <button type="button" class="reverse2pdf-btn reverse2pdf-btn-primary" id="generate-preview-pdf">
                            <span class="dashicons dashicons-media-document"></span> Generate PDF
                        </button>
                    </div>
                    <div class="reverse2pdf-preview-content">
                        <div class="preview-page">
                            ${R2PDF.Admin.renderTemplatePreview(templateData)}
                        </div>
                    </div>
                </div>
            `;

            R2PDF.Admin.createModal('Template Preview', previewHtml, { width: '80vw', maxWidth: '1200px' });
        },

        // Handle Template Test
        handleTemplateTest: function(e) {
            e.preventDefault();
            
            const templateId = $('#template-id').val();
            if (!templateId) {
                R2PDF.Admin.showNotification('Please save the template first', 'warning');
                return;
            }

            // Use the same generation function with test data
            const testData = {
                name: 'John Doe',
                email: 'john@example.com',
                message: 'This is a test message for the PDF template.',
                date: new Date().toLocaleDateString(),
                company: 'Test Company Ltd.'
            };

            $(this).trigger('click'); // Trigger normal generation with test data
        },

        // Handle Integration Setup
        handleIntegrationSetup: function(e) {
            e.preventDefault();
            
            const formType = $(this).data('type');
            const formTitle = $(this).closest('.integration-card').find('h3').text();
            
            const setupHtml = `
                <form id="integration-setup-form" class="reverse2pdf-form">
                    <div class="reverse2pdf-form-group">
                        <label class="reverse2pdf-form-label">Form Plugin</label>
                        <input type="text" class="reverse2pdf-form-input" value="${formTitle}" readonly>
                        <input type="hidden" name="form_type" value="${formType}">
                    </div>
                    
                    <div class="reverse2pdf-form-group">
                        <label class="reverse2pdf-form-label">Select Form</label>
                        <select class="reverse2pdf-form-select" name="form_id" required>
                            <option value="">Loading forms...</option>
                        </select>
                    </div>
                    
                    <div class="reverse2pdf-form-group">
                        <label class="reverse2pdf-form-label">PDF Template</label>
                        <select class="reverse2pdf-form-select" name="template_id" required>
                            <option value="">Loading templates...</option>
                        </select>
                    </div>
                    
                    <div class="reverse2pdf-form-group">
                        <label class="reverse2pdf-form-label">Trigger Action</label>
                        <select class="reverse2pdf-form-select" name="trigger_action">
                            <option value="form_submit">On Form Submission</option>
                            <option value="form_success">On Successful Submission</option>
                            <option value="payment_complete">On Payment Complete</option>
                        </select>
                    </div>
                    
                    <div class="reverse2pdf-form-group">
                        <button type="submit" class="reverse2pdf-btn reverse2pdf-btn-primary reverse2pdf-btn-lg">
                            <span class="dashicons dashicons-yes"></span> Setup Integration
                        </button>
                    </div>
                </form>
            `;

            const $modal = R2PDF.Admin.createModal(`Setup ${formTitle} Integration`, setupHtml);
            
            // Load forms and templates
            R2PDF.Admin.loadFormsByType(formType, $modal.find('select[name="form_id"]'));
            R2PDF.Admin.loadTemplates($modal.find('select[name="template_id"]'));
        },

        // Handle Form Type Change
        handleFormTypeChange: function() {
            const formType = $(this).val();
            const $formSelect = $('#form-id');
            
            if (!formType) {
                $formSelect.html('<option value="">Select a form plugin first</option>').prop('disabled', true);
                return;
            }

            $formSelect.html('<option value="">Loading forms...</option>').prop('disabled', false);
            R2PDF.Admin.loadFormsByType(formType, $formSelect);
        },

        // Handle Integration Submit
        handleIntegrationSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.html();
            
            // Get form data
            const formData = new FormData($form[0]);
            formData.append('action', 'reverse2pdf_setup_integration');
            formData.append('nonce', reverse2pdf_admin.nonce);
            
            // Show loading
            $submitBtn.prop('disabled', true)
                      .html('<span class="dashicons dashicons-update reverse2pdf-spin"></span> Setting up...');

            $.ajax({
                url: reverse2pdf_admin.ajax_url,
                type: 'POST',
                data: Object.fromEntries(formData),
                success: function(response) {
                    if (response.success) {
                        R2PDF.Admin.showNotification('Integration setup successfully! 🎉', 'success');
                        $('.reverse2pdf-modal').removeClass('active');
                        
                        // Refresh page after delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        R2PDF.Admin.showNotification('Setup failed: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    R2PDF.Admin.showNotification('Setup request failed. Please try again.', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        },

        // Handle Modal Close
        handleModalClose: function(e) {
            if (e.target === this || $(e.target).hasClass('reverse2pdf-modal-close')) {
                const $modal = $(this).closest('.reverse2pdf-modal');
                R2PDF.Admin.closeModal($modal);
            }
        },

        // Handle Keyboard Events
        handleKeyboardEvents: function(e) {
            if (e.keyCode === 27) { // ESC key
                $('.reverse2pdf-modal.active').each(function() {
                    R2PDF.Admin.closeModal($(this));
                });
            }
        },

        // Handle Stats Refresh
        handleStatsRefresh: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const originalText = $button.html();
            
            $button.prop('disabled', true)
                   .html('<span class="dashicons dashicons-update reverse2pdf-spin"></span> Refreshing...');

            R2PDF.Admin.updateStatistics().always(function() {
                $button.prop('disabled', false).html(originalText);
            });
        },

        // Utility Functions
        collectFormData: function() {
            const formData = {};
            
            // Collect from current form if any
            $('.reverse2pdf-form').find('input, select, textarea').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                if (name) {
                    formData[name] = $field.val();
                }
            });
            
            // Add default test data if empty
            if (Object.keys(formData).length === 0) {
                formData.name = 'Test User';
                formData.email = 'test@example.com';
                formData.message = 'This is a test message';
                formData.date = new Date().toLocaleDateString();
            }
            
            return formData;
        },

        getTemplateData: function() {
            // This would collect data from the visual builder
            // For now, return a basic structure
            const templateData = $('#template-data').val();
            if (templateData) {
                try {
                    return JSON.parse(templateData);
                } catch (e) {
                    console.warn('Invalid template data:', e);
                }
            }
            
            return {
                pages: [{
                    width: 595,
                    height: 842,
                    elements: []
                }]
            };
        },

        renderTemplatePreview: function(templateData) {
            // Basic template preview rendering
            return '<div class="template-preview">Template preview would be rendered here based on template data</div>';
        },

        loadFormsByType: function(formType, $select) {
            $.ajax({
                url: reverse2pdf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_get_forms',
                    form_type: formType,
                    nonce: reverse2pdf_admin.nonce
                },
                success: function(response) {
                    $select.empty().append('<option value="">Select a form...</option>');
                    
                    if (response.success && response.data && response.data.length > 0) {
                        response.data.forEach(function(form) {
                            $select.append(`<option value="${form.id}">${form.title}</option>`);
                        });
                    } else {
                        $select.append('<option value="">No forms found</option>');
                    }
                },
                error: function() {
                    $select.html('<option value="">Error loading forms</option>');
                }
            });
        },

        loadTemplates: function($select) {
            $.ajax({
                url: reverse2pdf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_get_templates',
                    nonce: reverse2pdf_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        response.data.forEach(function(template) {
                            $select.append(`<option value="${template.id}">${template.name}</option>`);
                        });
                    }
                },
                error: function() {
                    console.warn('Failed to load templates');
                }
            });
        },

        loadRecentActivity: function() {
            $.ajax({
                url: reverse2pdf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_get_recent_activity',
                    nonce: reverse2pdf_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        R2PDF.Admin.updateActivityDisplay(response.data);
                    }
                }
            });
        },

        loadSystemStatus: function() {
            $.ajax({
                url: reverse2pdf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_system_status',
                    nonce: reverse2pdf_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        R2PDF.Admin.updateSystemStatusDisplay(response.data);
                    }
                }
            });
        },

        updateStatistics: function() {
            return $.ajax({
                url: reverse2pdf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_get_stats',
                    nonce: reverse2pdf_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        R2PDF.Admin.updateStatsDisplay(response.data);
                    }
                }
            });
        },

        updateActivityDisplay: function(activities) {
            const $container = $('.activity-timeline, .recent-activity-list');
            if ($container.length && activities.length > 0) {
                $container.empty();
                activities.forEach(function(activity) {
                    const statusClass = activity.status === 'success' ? 'success' : 
                                      activity.status === 'error' ? 'error' : 'warning';
                    
                    $container.append(`
                        <div class="activity-item ${statusClass} reverse2pdf-fade-in">
                            <div class="activity-title">${activity.title || activity.action}</div>
                            <div class="activity-time">${activity.time || activity.created_date}</div>
                        </div>
                    `);
                });
            }
        },

        updateSystemStatusDisplay: function(status) {
            $('.system-status').each(function() {
                const $item = $(this);
                const component = $item.data('component');
                
                if (status[component]) {
                    $item.removeClass('status-error status-warning')
                         .addClass('status-' + status[component].status)
                         .find('.status-text')
                         .text(status[component].message);
                }
            });
        },

        updateStatsDisplay: function(stats) {
            Object.keys(stats).forEach(function(key) {
                $(`.stat-${key} .reverse2pdf-stat-number, .reverse2pdf-hero-stat-number`).each(function() {
                    const $el = $(this);
                    const currentVal = parseInt($el.text().replace(/,/g, '')) || 0;
                    const newVal = stats[key] || 0;
                    
                    if (currentVal !== newVal) {
                        R2PDF.Admin.animateNumber($el, currentVal, newVal);
                    }
                });
            });
        },

        updateLastSavedTime: function() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            
            let $indicator = $('.last-saved-indicator');
            if ($indicator.length === 0) {
                $indicator = $('<div class="last-saved-indicator"></div>');
                $('#save-template').after($indicator);
            }
            
            $indicator.html(`<small style="color: #10b981; margin-left: 10px;">✓ Saved at ${timeString}</small>`)
                     .addClass('reverse2pdf-fade-in');
            
            // Remove after 5 seconds
            setTimeout(() => {
                $indicator.fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        animateNumber: function($element, from, to, duration = 1000) {
            $({ value: from }).animate({ value: to }, {
                duration: duration,
                easing: 'swing',
                step: function() {
                    $element.text(Math.floor(this.value).toLocaleString());
                },
                complete: function() {
                    $element.text(to.toLocaleString());
                }
            });
        },

        showNotification: function(message, type = 'info', duration = 5000) {
            const icons = {
                success: 'yes-alt',
                error: 'dismiss',
                warning: 'warning',
                info: 'info'
            };

            const $notification = $(`
                <div class="reverse2pdf-notification ${type}">
                    <span class="dashicons dashicons-${icons[type]}"></span>
                    <span class="notification-message">${message}</span>
                    <button type="button" class="notification-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            `);

            // Create container if it doesn't exist
            if ($('.reverse2pdf-notifications').length === 0) {
                $('body').append('<div class="reverse2pdf-notifications"></div>');
            }
            
            $('.reverse2pdf-notifications').append($notification);

            // Auto remove
            const timeout = setTimeout(() => {
                $notification.addClass('removing').fadeOut(300, function() {
                    $(this).remove();
                });
            }, duration);

            // Manual close
            $notification.find('.notification-close').on('click', function() {
                clearTimeout(timeout);
                $notification.addClass('removing').fadeOut(300, function() {
                    $(this).remove();
                });
            });

            // Return notification element for chaining
            return $notification;
        },

        createModal: function(title, content, options = {}) {
            const defaults = {
                width: 'auto',
                maxWidth: '600px',
                closeOnOverlay: true
            };
            
            const settings = $.extend({}, defaults, options);
            
            const $modal = $(`
                <div class="reverse2pdf-modal">
                    <div class="reverse2pdf-modal-overlay"></div>
                    <div class="reverse2pdf-modal-content" style="max-width: ${settings.maxWidth}; width: ${settings.width}">
                        <div class="reverse2pdf-modal-header">
                            <h3 class="reverse2pdf-modal-title">${title}</h3>
                            <button type="button" class="reverse2pdf-modal-close">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        <div class="reverse2pdf-modal-body">
                            ${content}
                        </div>
                    </div>
                </div>
            `);
            
            // Add to page
            $('body').append($modal);
            
            // Show modal with delay for animation
            setTimeout(() => {
                $modal.addClass('active');
            }, 50);
            
            return $modal;
        },

        closeModal: function($modal) {
            $modal.removeClass('active');
            
            setTimeout(() => {
                $modal.remove();
            }, 300);
        },

        initTooltips: function() {
            // Initialize tooltips for elements with data-tooltip
            $('[data-tooltip]').hover(
                function() {
                    const $this = $(this);
                    const title = $this.data('tooltip');
                    
                    if (title) {
                        const $tooltip = $('<div class="reverse2pdf-tooltip">')
                            .text(title)
                            .appendTo('body');
                        
                        const pos = $this.offset();
                        const tooltipWidth = $tooltip.outerWidth();
                        const tooltipHeight = $tooltip.outerHeight();
                        
                        $tooltip.css({
                            position: 'absolute',
                            top: pos.top - tooltipHeight - 8,
                            left: pos.left + ($this.outerWidth() / 2) - (tooltipWidth / 2),
                            background: '#1f2937',
                            color: 'white',
                            padding: '8px 12px',
                            borderRadius: '6px',
                            fontSize: '12px',
                            fontWeight: '500',
                            zIndex: 999999,
                            whiteSpace: 'nowrap',
                            opacity: 0
                        }).animate({ opacity: 1 }, 150);
                    }
                },
                function() {
                    $('.reverse2pdf-tooltip').remove();
                }
            );
        },

        initAnimations: function() {
            // Smooth scrolling for anchor links
            $('a[href^="#"]').on('click', function(e) {
                const target = $($(this).attr('href'));
                if (target.length) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 500);
                }
            });

            // Add fade-in animation to cards on load
            $('.reverse2pdf-card, .reverse2pdf-action-card').each(function(index) {
                $(this).css({
                    opacity: 0,
                    transform: 'translateY(20px)'
                }).delay(index * 100).animate({
                    opacity: 1
                }, 500, function() {
                    $(this).css('transform', 'translateY(0)');
                });
            });
        },

        initDragAndDrop: function() {
            // Initialize drag and drop for template builder
            if ($('.element-item').length > 0) {
                $('.element-item').draggable({
                    helper: 'clone',
                    appendTo: 'body',
                    zIndex: 1000,
                    cursor: 'grabbing'
                });
            }

            if ($('.pdf-canvas').length > 0) {
                $('.pdf-canvas .page').droppable({
                    accept: '.element-item',
                    drop: function(event, ui) {
                        R2PDF.Admin.handleElementDrop(event, ui, $(this));
                    }
                });
            }
        },

        initColorPickers: function() {
            // Initialize color pickers if available
            if (typeof $.fn.wpColorPicker !== 'undefined') {
                $('.color-picker').wpColorPicker();
            }
        },

        initCodeEditors: function() {
            // Initialize code editors if CodeMirror is available
            if (typeof CodeMirror !== 'undefined') {
                $('textarea.code-editor').each(function() {
                    CodeMirror.fromTextArea(this, {
                        lineNumbers: true,
                        mode: 'htmlmixed',
                        theme: 'default'
                    });
                });
            }
        },

        handleElementDrop: function(event, ui, $dropZone) {
            const elementType = ui.draggable.data('type');
            const dropPos = {
                x: event.pageX - $dropZone.offset().left,
                y: event.pageY - $dropZone.offset().top
            };
            
            console.log(`Dropped ${elementType} element at`, dropPos);
            
            // This would add the element to the template
            R2PDF.Admin.addElementToTemplate(elementType, dropPos);
        },

        addElementToTemplate: function(elementType, position) {
            // This would add an element to the template builder
            console.log('Adding element to template:', elementType, position);
            
            // For now, just show a notification
            R2PDF.Admin.showNotification(`Added ${elementType} element to template`, 'success', 2000);
        },

        // Utility helper functions
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        formatBytes: function(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        },

        formatDate: function(date) {
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Add body class for styling
        $('body').addClass('reverse2pdf-admin-page');
        
        // Initialize admin
        R2PDF.Admin.init();
        
        // Add global keyboard shortcuts
        $(document).keydown(function(e) {
            // Ctrl/Cmd + S for save
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
                e.preventDefault();
                $('#save-template').trigger('click');
            }
            
            // Ctrl/Cmd + Enter for generate PDF
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 13) {
                e.preventDefault();
                $('.reverse2pdf-generate').first().trigger('click');
            }
        });
    });

    // Expose to global scope
    window.Reverse2PDF = R2PDF;

})(jQuery);

// Additional CSS for enhanced functionality
const additionalCSS = `
<style>
.reverse2pdf-tooltip {
    pointer-events: none;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.reverse2pdf-admin-page .ui-draggable-dragging {
    transform: scale(1.05) !important;
    opacity: 0.9 !important;
    z-index: 9999 !important;
}

.reverse2pdf-admin-page .ui-droppable-hover {
    background: rgba(99, 102, 241, 0.05) !important;
    border: 2px dashed rgba(99, 102, 241, 0.3) !important;
}

.reverse2pdf-notifications {
    position: fixed;
    top: 32px;
    right: 20px;
    z-index: 999999;
    max-width: 400px;
}

.reverse2pdf-notification.removing {
    transform: translateX(100%);
    opacity: 0;
}

@keyframes reverse2pdf-slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.reverse2pdf-notification {
    animation: reverse2pdf-slideInRight 0.3s ease-out;
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', additionalCSS);
