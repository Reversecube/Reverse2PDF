/**
 * Enhanced Reverse2PDF Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Global admin object
    window.Reverse2PDF_Admin = {
        init: function() {
            this.bindEvents();
            this.initComponents();
            this.loadInitialData();
        },
        
        bindEvents: function() {
            // Template management
            $(document).on('click', '.reverse2pdf-preview', this.previewTemplate);
            $(document).on('click', '.reverse2pdf-duplicate', this.duplicateTemplate);
            $(document).on('click', '.reverse2pdf-delete', this.deleteTemplate);
            $(document).on('click', '.reverse2pdf-export', this.exportTemplate);
            $(document).on('click', '.reverse2pdf-import', this.importTemplate);
            
            // Integration management
            $(document).on('change', '#form-type-select', this.loadFormsByType);
            $(document).on('change', '#form-select', this.loadFormFields);
            $(document).on('click', '.setup-integration', this.setupIntegration);
            $(document).on('click', '.test-integration', this.testIntegration);
            
            // PDF generation
            $(document).on('click', '.reverse2pdf-generate', this.generatePDF);
            $(document).on('click', '.reverse2pdf-download', this.downloadPDF);
            
            // Settings
            $(document).on('submit', '#reverse2pdf-settings-form', this.saveSettings);
            $(document).on('change', '.settings-field', this.validateSettings);
            
            // Bulk actions
            $(document).on('change', '#cb-select-all-1', this.toggleSelectAll);
            $(document).on('submit', '.bulk-actions-form', this.processBulkActions);
            
            // Modal controls
            $(document).on('click', '.modal-close, .modal-overlay', this.closeModal);
            $(document).on('click', '.modal-content', function(e) { e.stopPropagation(); });
            
            // Auto-save functionality
            $(document).on('input', '.auto-save', this.debounce(this.autoSave, 2000));
            
            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts);
        },
        
        initComponents: function() {
            // Initialize tooltips
            this.initTooltips();
            
            // Initialize color pickers
            this.initColorPickers();
            
            // Initialize media uploaders
            this.initMediaUploaders();
            
            // Initialize sortable lists
            this.initSortables();
            
            // Initialize tabs
            this.initTabs();
            
            // Initialize accordions
            this.initAccordions();
        },
        
        loadInitialData: function() {
            // Load dashboard stats
            if ($('#reverse2pdf-dashboard').length) {
                this.loadDashboardStats();
            }
            
            // Load recent logs
            if ($('#recent-logs-container').length) {
                this.loadRecentLogs();
            }
        },
        
        // Template Management
        previewTemplate: function(e) {
            e.preventDefault();
            
            var templateId = $(this).data('template-id');
            var datasetId = $(this).data('dataset-id') || 0;
            
            var previewUrl = reverse2pdf_admin.preview_url + 
                '&template_id=' + templateId + 
                '&dataset_id=' + datasetId + 
                '&preview=1';
            
            // Open in modal or new window
            if (reverse2pdf_admin.preview_mode === 'modal') {
                Reverse2PDF_Admin.openPreviewModal(previewUrl);
            } else {
                window.open(previewUrl, 'reverse2pdf_preview', 
                    'width=1000,height=800,scrollbars=yes,resizable=yes,menubar=no,toolbar=no');
            }
        },
        
        duplicateTemplate: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var templateId = $btn.data('template-id');
            var originalText = $btn.html();
            
            if (!confirm(reverse2pdf_admin.strings.confirm_duplicate)) {
                return;
            }
            
            $btn.prop('disabled', true).html('<span class="spinner is-active"></span> ' + reverse2pdf_admin.strings.duplicating);
            
            $.post(reverse2pdf_admin.ajax_url, {
                action: 'reverse2pdf_duplicate_template',
                template_id: templateId,
                nonce: reverse2pdf_admin.nonce
            })
            .done(function(response) {
                if (response.success) {
                    Reverse2PDF_Admin.showNotification(response.data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    Reverse2PDF_Admin.showNotification(response.data, 'error');
                }
            })
            .fail(function() {
                Reverse2PDF_Admin.showNotification(reverse2pdf_admin.strings.ajax_error, 'error');
            })
            .always(function() {
                $btn.prop('disabled', false).html(originalText);
            });
        },
        
        deleteTemplate: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var templateId = $btn.data('template-id');
            
            if (!confirm(reverse2pdf_admin.strings.confirm_delete)) {
                return;
            }
            
            var originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner is-active"></span>');
            
            $.post(reverse2pdf_admin.ajax_url, {
                action: 'reverse2pdf_delete_template',
                template_id: templateId,
                nonce: reverse2pdf_admin.nonce
            })
            .done(function(response) {
                if (response.success) {
                    $btn.closest('tr').fadeOut(300, function() {
                        $(this).remove();
                    });
                    Reverse2PDF_Admin.showNotification(response.data, 'success');
                } else {
                    Reverse2PDF_Admin.showNotification(response.data, 'error');
                    $btn.prop('disabled', false).html(originalText);
                }
            })
            .fail(function() {
                Reverse2PDF_Admin.showNotification(reverse2pdf_admin.strings.ajax_error, 'error');
                $btn.prop('disabled', false).html(originalText);
            });
        },
        
        // Integration Management
        loadFormsByType: function(e) {
            var formType = $(this).val();
            var $formSelect = $('#form-select');
            var $fieldsContainer = $('#form-fields-container');
            
            if (!formType) {
                $formSelect.hide().empty();
                $fieldsContainer.hide().empty();
                return;
            }
            
            $formSelect.html('<option value="">' + reverse2pdf_admin.strings.loading + '...</option>').show();
            
            $.post(reverse2pdf_admin.ajax_url, {
                action: 'reverse2pdf_get_forms',
                form_type: formType,
                nonce: reverse2pdf_admin.nonce
            })
            .done(function(response) {
                if (response.success) {
                    var options = '<option value="">' + reverse2pdf_admin.strings.select_form + '</option>';
                    
                    $.each(response.data, function(index, form) {
                        var statusClass = form.active ? 'active' : 'inactive';
                        options += '<option value="' + form.id + '" class="' + statusClass + '">' + 
                                  form.title + (form.active ? '' : ' (Inactive)') + '</option>';
                    });
                    
                    $formSelect.html(options);
                } else {
                    $formSelect.html('<option value="">' + reverse2pdf_admin.strings.no_forms + '</option>');
                }
            })
            .fail(function() {
                $formSelect.html('<option value="">' + reverse2pdf_admin.strings.load_error + '</option>');
            });
        },
        
        loadFormFields: function(e) {
            var formId = $(this).val();
            var formType = $('#form-type-select').val();
            var $fieldsContainer = $('#form-fields-container');
            
            if (!formId || !formType) {
                $fieldsContainer.hide().empty();
                return;
            }
            
            $fieldsContainer.html('<div class="loading-fields"><span class="spinner is-active"></span> Loading fields...</div>').show();
            
            $.post(reverse2pdf_admin.ajax_url, {
                action: 'reverse2pdf_get_form_fields',
                form_type: formType,
                form_id: formId,
                nonce: reverse2pdf_admin.nonce
            })
            .done(function(response) {
                if (response.success && response.data.length > 0) {
                    var fieldsHTML = '<div class="form-fields-list">';
                    fieldsHTML += '<h4>' + reverse2pdf_admin.strings.available_fields + '</h4>';
                    fieldsHTML += '<div class="fields-grid">';
                    
                    $.each(response.data, function(index, field) {
                        fieldsHTML += '<div class="field-item" data-field="' + field.name + '">';
                        fieldsHTML += '<div class="field-info">';
                        fieldsHTML += '<strong>' + field.label + '</strong>';
                        fieldsHTML += '<small>' + field.name + ' (' + field.type + ')</small>';
                        fieldsHTML += '</div>';
                        fieldsHTML += '<div class="field-actions">';
                        fieldsHTML += '<button type="button" class="button button-small copy-field-name" data-field="' + field.name + '">';
                        fieldsHTML += '<span class="dashicons dashicons-admin-page"></span>';
                        fieldsHTML += '</button>';
                        fieldsHTML += '</div>';
                        fieldsHTML += '</div>';
                    });
                    
                    fieldsHTML += '</div></div>';
                    $fieldsContainer.html(fieldsHTML);
                } else {
                    $fieldsContainer.html('<div class="no-fields">No fields found for this form.</div>');
                }
            })
            .fail(function() {
                $fieldsContainer.html('<div class="load-error">Failed to load form fields.</div>');
            });
        },
        
        setupIntegration: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $form = $btn.closest('form');
            var formData = $form.serialize();
            var originalText = $btn.html();
            
            // Validate required fields
            var formType = $form.find('#form-type-select').val();
            var formId = $form.find('#form-select').val();
            var templateId = $form.find('#template-select').val();
            
            if (!formType || !formId || !templateId) {
                Reverse2PDF_Admin.showNotification(reverse2pdf_admin.strings.required_fields_missing, 'error');
                return;
            }
            
            $btn.prop('disabled', true).html('<span class="spinner is-active"></span> ' + reverse2pdf_admin.strings.setting_up);
            
            $.post(reverse2pdf_admin.ajax_url, formData + '&action=reverse2pdf_setup_integration&nonce=' + reverse2pdf_admin.nonce)
            .done(function(response) {
                if (response.success) {
                    Reverse2PDF_Admin.showNotification(response.data.message, 'success');
                    $btn.removeClass('button-primary').addClass('button-secondary');
                    $btn.html('<span class="dashicons dashicons-yes"></span> ' + reverse2pdf_admin.strings.configured);
                    
                    // Enable test button
                    $form.find('.test-integration').prop('disabled', false);
                    
                    setTimeout(function() {
                        $btn.html(originalText).removeClass('button-secondary').addClass('button-primary');
                    }, 3000);
                } else {
                    Reverse2PDF_Admin.showNotification(response.data, 'error');
                }
            })
            .fail(function() {
                Reverse2PDF_Admin.showNotification(reverse2pdf_admin.strings.ajax_error, 'error');
            })
            .always(function() {
                $btn.prop('disabled', false);
            });
        },
        
        // PDF Generation
        generatePDF: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var templateId = $btn.data('template-id');
            var datasetId = $btn.data('dataset-id') || 0;
            var originalText = $btn.html();
            
            if (!templateId) {
                Reverse2PDF_Admin.showNotification(reverse2pdf_admin.strings.template_required, 'error');
                return;
            }
            
            $btn.prop('disabled', true).html('<span class="spinner is-active"></span> ' + reverse2pdf_admin.strings.generating);
            
            $.post(reverse2pdf_admin.ajax_url, {
                action: 'reverse2pdf_generate',
                template_id: templateId,
                dataset_id: datasetId,
                nonce: reverse2pdf_admin.nonce
            })
            .done(function(response) {
                if (response.success) {
                    // Show success and provide download link
                    var downloadHTML = '<a href="' + response.data.pdf_url + '" target="_blank" class="button button-small">' +
                                      '<span class="dashicons dashicons-download"></span> Download PDF</a>';
                    
                    Reverse2PDF_Admin.showNotification(
                        reverse2pdf_admin.strings.pdf_generated + ' ' + downloadHTML,
                        'success',
                        5000
                    );
                    
                    // Auto-download if enabled
                    if (reverse2pdf_admin.auto_download) {
                        window.open(response.data.pdf_url, '_blank');
                    }
                } else {
                    Reverse2PDF_Admin.showNotification(response.data, 'error');
                }
            })
            .fail(function() {
                Reverse2PDF_Admin.showNotification(reverse2pdf_admin.strings.generation_error, 'error');
            })
            .always(function() {
                $btn.prop('disabled', false).html(originalText);
            });
        },
        
        // UI Components
        initTooltips: function() {
            $(document).on('mouseenter', '[data-tooltip]', function() {
                var $this = $(this);
                var tooltipText = $this.data('tooltip');
                
                if (!tooltipText) return;
                
                var $tooltip = $('<div class="reverse2pdf-tooltip">' + tooltipText + '</div>');
                $('body').append($tooltip);
                
                var offset = $this.offset();
                var tooltipWidth = $tooltip.outerWidth();
                var tooltipHeight = $tooltip.outerHeight();
                
                $tooltip.css({
                    position: 'absolute',
                    top: offset.top - tooltipHeight - 10,
                    left: offset.left - (tooltipWidth / 2) + ($this.outerWidth() / 2),
                    zIndex: 9999
                });
                
                $this.data('tooltip-element', $tooltip);
            });
            
            $(document).on('mouseleave', '[data-tooltip]', function() {
                var $tooltip = $(this).data('tooltip-element');
                if ($tooltip) {
                    $tooltip.remove();
                    $(this).removeData('tooltip-element');
                }
            });
        },
        
        initColorPickers: function() {
            if (typeof $.fn.wpColorPicker !== 'undefined') {
                $('.color-picker').wpColorPicker({
                    change: function(event, ui) {
                        $(this).trigger('color-change', ui.color.toString());
                    }
                });
            }
        },
        
        initMediaUploaders: function() {
            $(document).on('click', '.media-upload-btn', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var $input = $btn.siblings('input[type="text"]');
                var mediaType = $btn.data('media-type') || 'image';
                
                var frame = wp.media({
                    title: reverse2pdf_admin.strings.select_media,
                    button: {
                        text: reverse2pdf_admin.strings.use_media
                    },
                    multiple: false,
                    library: {
                        type: mediaType
                    }
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $input.val(attachment.url).trigger('change');
                    
                    // Show preview if it's an image
                    if (mediaType === 'image') {
                        var $preview = $btn.siblings('.media-preview');
                        if (!$preview.length) {
                            $preview = $('<div class="media-preview"></div>');
                            $btn.after($preview);
                        }
                        $preview.html('<img src="' + attachment.url + '" style="max-width: 100px; max-height: 100px;">');
                    }
                });
                
                frame.open();
            });
        },
        
        initTabs: function() {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var $this = $(this);
                var target = $this.attr('href');
                
                // Update tab navigation
                $this.siblings('.nav-tab').removeClass('nav-tab-active');
                $this.addClass('nav-tab-active');
                
                // Show/hide tab content
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
                
                // Store active tab
                localStorage.setItem('reverse2pdf_active_tab', target);
                
                // Trigger tab change event
                $(document).trigger('reverse2pdf:tab-changed', [target, $this]);
            });
            
            // Restore active tab
            var activeTab = localStorage.getItem('reverse2pdf_active_tab');
            if (activeTab && $(activeTab).length) {
                $('.nav-tab[href="' + activeTab + '"]').click();
            }
        },
        
        initAccordions: function() {
            $('.accordion-header').on('click', function() {
                var $header = $(this);
                var $content = $header.next('.accordion-content');
                var $accordion = $header.closest('.accordion');
                
                if ($accordion.hasClass('single-open')) {
                    // Close other accordion items
                    $accordion.find('.accordion-content').not($content).slideUp();
                    $accordion.find('.accordion-header').not($header).removeClass('active');
                }
                
                $header.toggleClass('active');
                $content.slideToggle();
            });
        },
        
        // Utility Functions
        showNotification: function(message, type, duration) {
            type = type || 'info';
            duration = duration || 4000;
            
            var $notification = $('<div class="reverse2pdf-notification ' + type + '">' + message + '</div>');
            
            $notification.css({
                position: 'fixed',
                top: '32px',
                right: '20px',
                padding: '15px 20px',
                borderRadius: '4px',
                color: '#fff',
                fontSize: '14px',
                fontWeight: '500',
                zIndex: '999999',
                maxWidth: '400px',
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                opacity: '0',
                transform: 'translateX(100%)',
                transition: 'all 0.3s ease'
            });
            
            // Set colors based on type
            var colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#007cba'
            };
            
            $notification.css('backgroundColor', colors[type] || colors.info);
            
            $('body').append($notification);
            
            // Animate in
            setTimeout(function() {
                $notification.css({
                    opacity: '1',
                    transform: 'translateX(0)'
                });
            }, 10);
            
            // Auto remove
            setTimeout(function() {
                $notification.css({
                    opacity: '0',
                    transform: 'translateX(100%)'
                });
                
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, duration);
            
            // Manual close
            $notification.on('click', function() {
                $(this).css({
                    opacity: '0',
                    transform: 'translateX(100%)'
                });
            });
        },
        
        openPreviewModal: function(url) {
            var modalHTML = '<div class="reverse2pdf-modal-overlay">' +
                '<div class="reverse2pdf-modal preview-modal">' +
                    '<div class="modal-header">' +
                        '<h3>PDF Preview</h3>' +
                        '<button class="modal-close">&times;</button>' +
                    '</div>' +
                    '<div class="modal-body">' +
                        '<iframe src="' + url + '" style="width: 100%; height: 80vh; border: none;"></iframe>' +
                    '</div>' +
                '</div>' +
            '</div>';
            
            $('body').append(modalHTML);
        },
        
        closeModal: function(e) {
            if (e.target === this || $(this).hasClass('modal-close')) {
                $('.reverse2pdf-modal-overlay').fadeOut(300, function() {
                    $(this).remove();
                });
            }
        },
        
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },
        
        handleKeyboardShortcuts: function(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                var $saveBtn = $('.reverse2pdf-save:visible').first();
                if ($saveBtn.length) {
                    $saveBtn.click();
                }
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                $('.reverse2pdf-modal-overlay').trigger('click');
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        Reverse2PDF_Admin.init();
    });
    
})(jQuery);
