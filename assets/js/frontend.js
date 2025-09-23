/* ==========================================================================
   Reverse2PDF Pro - Frontend JavaScript
   ========================================================================== */

(function($) {
    'use strict';

    // Global namespace
    window.Reverse2PDF = window.Reverse2PDF || {};
    const R2PDF = window.Reverse2PDF;

    // Frontend Object
    R2PDF.Frontend = {
        
        init: function() {
            this.bindEvents();
            this.initComponents();
            
            console.log('🚀 Reverse2PDF Frontend initialized');
        },

        bindEvents: function() {
            // PDF Generation buttons
            $(document).on('click', '.reverse2pdf-generate', this.handlePDFGeneration);
            
            // Form submissions
            $(document).on('submit', '.reverse2pdf-form', this.handleFormSubmission);
            
            // Download buttons
            $(document).on('click', '.reverse2pdf-download', this.handleDownload);
            
            // Print buttons
            $(document).on('click', '.reverse2pdf-print', this.handlePrint);
            
            // View buttons
            $(document).on('click', '.reverse2pdf-view', this.handleView);
        },

        initComponents: function() {
            this.initProgressBars();
            this.initTooltips();
            this.setupFormValidation();
        },

        handlePDFGeneration: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const templateId = $button.data('template-id');
            const formData = R2PDF.Frontend.collectFormData($button);
            const originalText = $button.html();
            
            if (!templateId) {
                R2PDF.Frontend.showNotification('Template ID is required', 'error');
                return;
            }

            // Show loading state
            $button.prop('disabled', true)
                   .html('<span class="reverse2pdf-spin">⟳</span> Generating PDF...')
                   .addClass('reverse2pdf-loading');

            // Show progress bar if configured
            const $progress = R2PDF.Frontend.showProgress($button);

            $.ajax({
                url: reverse2pdf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_generate_pdf',
                    template_id: templateId,
                    form_data: formData,
                    nonce: reverse2pdf_ajax.nonce
                },
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    // Upload progress
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable && $progress) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            R2PDF.Frontend.updateProgress($progress, percentComplete);
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    if (response.success) {
                        R2PDF.Frontend.showNotification('PDF generated successfully! 🎉', 'success');
                        
                        // Show download options
                        R2PDF.Frontend.showDownloadOptions($button, response.data);
                        
                        // Trigger custom event
                        $(document).trigger('reverse2pdf:generated', [response.data, templateId]);
                        
                    } else {
                        R2PDF.Frontend.showNotification('Generation failed: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('PDF Generation Error:', error);
                    R2PDF.Frontend.showNotification('Request failed. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false)
                           .html(originalText)
                           .removeClass('reverse2pdf-loading');
                    
                    if ($progress) {
                        setTimeout(() => {
                            $progress.fadeOut(500, function() {
                                $(this).remove();
                            });
                        }, 1000);
                    }
                }
            });
        },

        handleFormSubmission: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const templateId = $form.data('template-id');
            const $submitBtn = $form.find('button[type="submit"], input[type="submit"]');
            
            if (!templateId) {
                R2PDF.Frontend.showNotification('Form is not configured for PDF generation', 'warning');
                return;
            }

            // Validate form
            if (!R2PDF.Frontend.validateForm($form)) {
                return;
            }

            const formData = R2PDF.Frontend.serializeForm($form);
            const originalText = $submitBtn.val() || $submitBtn.text();
            
            // Show loading
            $submitBtn.prop('disabled', true);
            if ($submitBtn.is('button')) {
                $submitBtn.html('<span class="reverse2pdf-spin">⟳</span> Processing...');
            } else {
                $submitBtn.val('Processing...');
            }

            $.ajax({
                url: reverse2pdf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_generate_pdf',
                    template_id: templateId,
                    form_data: formData,
                    nonce: reverse2pdf_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        R2PDF.Frontend.showNotification('Form submitted and PDF generated! 📄', 'success');
                        
                        // Show download link
                        R2PDF.Frontend.showDownloadOptions($form, response.data);
                        
                        // Reset form if configured
                        if ($form.data('reset-after-submit')) {
                            $form[0].reset();
                        }
                        
                        // Trigger custom event
                        $(document).trigger('reverse2pdf:form-submitted', [response.data, formData]);
                        
                    } else {
                        R2PDF.Frontend.showNotification('Submission failed: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    R2PDF.Frontend.showNotification('Submission failed. Please try again.', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                    if ($submitBtn.is('button')) {
                        $submitBtn.html(originalText);
                    } else {
                        $submitBtn.val(originalText);
                    }
                }
            });
        },

        handleDownload: function(e) {
            e.preventDefault();
            
            const $link = $(this);
            const pdfUrl = $link.attr('href') || $link.data('pdf-url');
            
            if (!pdfUrl) {
                R2PDF.Frontend.showNotification('Download URL not found', 'error');
                return;
            }

            // Create temporary download link
            const downloadLink = document.createElement('a');
            downloadLink.href = pdfUrl;
            downloadLink.download = $link.data('filename') || 'document.pdf';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            
            R2PDF.Frontend.showNotification('Download started 📥', 'info', 2000);
        },

        handlePrint: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const pdfUrl = $button.data('pdf-url');
            
            if (!pdfUrl) {
                R2PDF.Frontend.showNotification('PDF URL not found', 'error');
                return;
            }

            // Open in new window for printing
            const printWindow = window.open(pdfUrl, '_blank');
            
            if (printWindow) {
                printWindow.onload = function() {
                    printWindow.print();
                };
            } else {
                R2PDF.Frontend.showNotification('Pop-up blocked. Please allow pop-ups and try again.', 'warning');
            }
        },

        handleView: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const pdfUrl = $button.data('pdf-url');
            const inline = $button.data('inline');
            
            if (!pdfUrl) {
                R2PDF.Frontend.showNotification('PDF URL not found', 'error');
                return;
            }

            if (inline) {
                // Show inline viewer
                R2PDF.Frontend.showInlineViewer(pdfUrl, $button);
            } else {
                // Open in new tab
                window.open(pdfUrl, '_blank');
            }
        },

        // Utility Functions
        collectFormData: function($context) {
            const formData = {};
            
            // Find the closest form or use the context
            const $form = $context.closest('form').length ? $context.closest('form') : $context.closest('.reverse2pdf-form');
            
            if ($form.length) {
                $form.find('input, select, textarea').each(function() {
                    const $field = $(this);
                    const name = $field.attr('name');
                    const type = $field.attr('type');
                    
                    if (name && type !== 'submit' && type !== 'button') {
                        if (type === 'checkbox' || type === 'radio') {
                            if ($field.is(':checked')) {
                                formData[name] = $field.val();
                            }
                        } else {
                            formData[name] = $field.val();
                        }
                    }
                });
            }
            
            // Add current page info
            formData.page_url = window.location.href;
            formData.page_title = document.title;
            formData.user_agent = navigator.userAgent;
            formData.timestamp = new Date().toISOString();
            
            return formData;
        },

        serializeForm: function($form) {
            const formData = {};
            const serialized = $form.serializeArray();
            
            $.each(serialized, function(index, field) {
                if (formData[field.name]) {
                    // Handle multiple values (like checkboxes)
                    if (!$.isArray(formData[field.name])) {
                        formData[field.name] = [formData[field.name]];
                    }
                    formData[field.name].push(field.value);
                } else {
                    formData[field.name] = field.value;
                }
            });
            
            return formData;
        },

        validateForm: function($form) {
            let isValid = true;
            
            // Remove previous error states
            $form.find('.field-error').removeClass('field-error');
            $form.find('.error-message').remove();
            
            // Check required fields
            $form.find('[required]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    isValid = false;
                    $field.addClass('field-error');
                    $field.after('<div class="error-message" style="color: #ef4444; font-size: 0.875rem; margin-top: 4px;">This field is required</div>');
                }
            });
            
            // Check email fields
            $form.find('input[type="email"]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (value && !R2PDF.Frontend.isValidEmail(value)) {
                    isValid = false;
                    $field.addClass('field-error');
                    $field.after('<div class="error-message" style="color: #ef4444; font-size: 0.875rem; margin-top: 4px;">Please enter a valid email address</div>');
                }
            });
            
            if (!isValid) {
                R2PDF.Frontend.showNotification('Please correct the errors in the form', 'warning');
                
                // Scroll to first error
                const $firstError = $form.find('.field-error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 300);
                    $firstError.focus();
                }
            }
            
            return isValid;
        },

        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        showProgress: function($context) {
            const $progress = $(`
                <div class="reverse2pdf-progress-container" style="margin-top: 12px;">
                    <div class="reverse2pdf-progress">
                        <div class="reverse2pdf-progress-bar" style="width: 0%"></div>
                    </div>
                    <div class="progress-text" style="text-align: center; font-size: 0.875rem; color: #6b7280; margin-top: 8px;">
                        Preparing PDF generation...
                    </div>
                </div>
            `);
            
            $context.after($progress);
            return $progress;
        },

        updateProgress: function($progress, percentage) {
            if ($progress && $progress.length) {
                $progress.find('.reverse2pdf-progress-bar').css('width', percentage + '%');
                
                let text = 'Preparing PDF generation...';
                if (percentage > 20) text = 'Processing template...';
                if (percentage > 50) text = 'Generating PDF...';
                if (percentage > 80) text = 'Finalizing document...';
                if (percentage >= 100) text = 'Complete!';
                
                $progress.find('.progress-text').text(text);
            }
        },

        showDownloadOptions: function($context, data) {
            const downloadHtml = `
                <div class="reverse2pdf-download-options" style="margin-top: 16px; padding: 16px; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 1px solid #a7f3d0; border-radius: 8px; text-align: center; animation: slideInDown 0.3s ease;">
                    <div style="margin-bottom: 12px; color: #065f46; font-weight: 600;">
                        ✅ PDF Generated Successfully!
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        <a href="${data.pdf_url}" target="_blank" class="reverse2pdf-btn reverse2pdf-btn-primary reverse2pdf-btn-sm">
                            <span style="margin-right: 6px;">👁️</span> View PDF
                        </a>
                        <a href="${data.pdf_url}" download class="reverse2pdf-btn reverse2pdf-btn-secondary reverse2pdf-btn-sm reverse2pdf-download" data-pdf-url="${data.pdf_url}">
                            <span style="margin-right: 6px;">📥</span> Download
                        </a>
                        <button type="button" class="reverse2pdf-btn reverse2pdf-btn-secondary reverse2pdf-btn-sm reverse2pdf-print" data-pdf-url="${data.pdf_url}">
                            <span style="margin-right: 6px;">🖨️</span> Print
                        </button>
                    </div>
                    <button type="button" onclick="$(this).parent().fadeOut()" style="position: absolute; top: 8px; right: 8px; background: none; border: none; color: #065f46; opacity: 0.7; cursor: pointer; font-size: 16px;">×</button>
                </div>
            `;
            
            // Remove existing download options
            $('.reverse2pdf-download-options').remove();
            
            $context.after(downloadHtml);
            
            // Auto-remove after 30 seconds
            setTimeout(() => {
                $('.reverse2pdf-download-options').fadeOut(500, function() {
                    $(this).remove();
                });
            }, 30000);
        },

        showInlineViewer: function(pdfUrl, $context) {
            const viewerHtml = `
                <div class="reverse2pdf-viewer" style="margin-top: 20px;">
                    <div style="background: #f3f4f6; padding: 12px; display: flex; justify-content: space-between; align-items: center; border-radius: 8px 8px 0 0;">
                        <span style="font-weight: 600; color: #374151;">PDF Viewer</span>
                        <button type="button" onclick="$(this).closest('.reverse2pdf-viewer').fadeOut()" style="background: none; border: none; color: #6b7280; cursor: pointer;">✕</button>
                    </div>
                    <iframe src="${pdfUrl}" style="width: 100%; height: 600px; border: none; border-radius: 0 0 8px 8px;"></iframe>
                </div>
            `;
            
            // Remove existing viewers
            $('.reverse2pdf-viewer').remove();
            
            $context.after(viewerHtml);
        },

        showNotification: function(message, type = 'info', duration = 5000) {
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };

            const $notification = $(`
                <div class="reverse2pdf-notification ${type}" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 350px; animation: slideInRight 0.3s ease;">
                    <span style="margin-right: 8px;">${icons[type]}</span>
                    <span>${message}</span>
                    <button type="button" onclick="$(this).parent().fadeOut()" style="margin-left: auto; background: none; border: none; opacity: 0.7; cursor: pointer;">×</button>
                </div>
            `);

            $('body').append($notification);

            // Auto remove
            setTimeout(() => {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, duration);

            return $notification;
        },

        initProgressBars: function() {
            // Animate any existing progress bars
            $('.reverse2pdf-progress-bar').each(function() {
                const $bar = $(this);
                const width = $bar.data('width') || '0%';
                $bar.animate({ width: width }, 1000);
            });
        },

        initTooltips: function() {
            // Simple tooltip implementation
            $('[data-tooltip]').hover(
                function() {
                    const title = $(this).data('tooltip');
                    if (title) {
                        const $tooltip = $('<div class="reverse2pdf-tooltip">')
                            .text(title)
                            .css({
                position: 'absolute',
                background: '#1f2937',
                color: 'white',
                padding: '6px 10px',
                borderRadius: '4px',
                fontSize: '12px',
                zIndex: 9999,
                whiteSpace: 'nowrap',
                pointerEvents: 'none'
            });
                        
                        const pos = $(this).offset();
                        $tooltip.appendTo('body').css({
                            top: pos.top - $tooltip.outerHeight() - 8,
                            left: pos.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                        });
                    }
                },
                function() {
                    $('.reverse2pdf-tooltip').remove();
                }
            );
        },

        setupFormValidation: function() {
            // Real-time form validation
            $('.reverse2pdf-form input, .reverse2pdf-form select, .reverse2pdf-form textarea').on('blur', function() {
                R2PDF.Frontend.validateField($(this));
            });
            
            // Clear errors on focus
            $('.reverse2pdf-form input, .reverse2pdf-form select, .reverse2pdf-form textarea').on('focus', function() {
                $(this).removeClass('field-error').next('.error-message').remove();
            });
        },

        validateField: function($field) {
            let isValid = true;
            const value = $field.val().trim();
            
            // Clear previous errors
            $field.removeClass('field-error').next('.error-message').remove();
            
            // Required field validation
            if ($field.attr('required') && !value) {
                isValid = false;
                $field.addClass('field-error')
                      .after('<div class="error-message" style="color: #ef4444; font-size: 0.875rem; margin-top: 4px;">This field is required</div>');
            }
            
            // Email validation
            if ($field.attr('type') === 'email' && value && !R2PDF.Frontend.isValidEmail(value)) {
                isValid = false;
                $field.addClass('field-error')
                      .after('<div class="error-message" style="color: #ef4444; font-size: 0.875rem; margin-top: 4px;">Please enter a valid email address</div>');
            }
            
            // URL validation
            if ($field.attr('type') === 'url' && value && !R2PDF.Frontend.isValidUrl(value)) {
                isValid = false;
                $field.addClass('field-error')
                      .after('<div class="error-message" style="color: #ef4444; font-size: 0.875rem; margin-top: 4px;">Please enter a valid URL</div>');
            }
            
            // Phone validation
            if ($field.attr('type') === 'tel' && value && !R2PDF.Frontend.isValidPhone(value)) {
                isValid = false;
                $field.addClass('field-error')
                      .after('<div class="error-message" style="color: #ef4444; font-size: 0.875rem; margin-top: 4px;">Please enter a valid phone number</div>');
            }
            
            return isValid;
        },

        isValidUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch {
                return false;
            }
        },

        isValidPhone: function(phone) {
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
        },

        // Shortcode functions
        processShortcodes: function() {
            // Process any reverse2pdf shortcodes on the page
            $('.reverse2pdf-shortcode').each(function() {
                const $shortcode = $(this);
                const type = $shortcode.data('type');
                const value = $shortcode.data('value');
                
                switch (type) {
                    case 'date':
                        $shortcode.text(R2PDF.Frontend.formatDate(value));
                        break;
                    case 'currency':
                        $shortcode.text(R2PDF.Frontend.formatCurrency(value));
                        break;
                    case 'number':
                        $shortcode.text(R2PDF.Frontend.formatNumber(value));
                        break;
                }
            });
        },

        formatDate: function(date, format = 'long') {
            const d = new Date(date);
            const options = {
                short: { year: 'numeric', month: 'short', day: 'numeric' },
                long: { year: 'numeric', month: 'long', day: 'numeric' },
                full: { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
            };
            
            return d.toLocaleDateString('en-US', options[format] || options.long);
        },

        formatCurrency: function(amount, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount);
        },

        formatNumber: function(number, decimals = 0) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(number);
        },

        // Event handlers for custom events
        onPDFGenerated: function(callback) {
            $(document).on('reverse2pdf:generated', callback);
        },

        onFormSubmitted: function(callback) {
            $(document).on('reverse2pdf:form-submitted', callback);
        },

        // Utility functions
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

        getCookie: function(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        },

        setCookie: function(name, value, days = 7) {
            const expires = new Date(Date.now() + days * 864e5).toUTCString();
            document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/`;
        },

        // Browser detection
        getBrowserInfo: function() {
            const ua = navigator.userAgent;
            return {
                isChrome: /Chrome/.test(ua) && /Google Inc/.test(navigator.vendor),
                isFirefox: /Firefox/.test(ua),
                isSafari: /Safari/.test(ua) && /Apple Computer/.test(navigator.vendor),
                isEdge: /Edg/.test(ua),
                isMobile: /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua)
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        R2PDF.Frontend.init();
        R2PDF.Frontend.processShortcodes();
        
        // Handle WordPress AJAX in frontend
        if (typeof reverse2pdf_ajax !== 'undefined') {
            console.log('Reverse2PDF AJAX configuration loaded');
        }
    });

    // Public API
    window.Reverse2PDF = R2PDF;

})(jQuery);

// Additional CSS for frontend
const frontendCSS = `
<style>
.reverse2pdf-tooltip {
    pointer-events: none;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.field-error {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

.reverse2pdf-progress-container {
    opacity: 0;
    animation: fadeInUp 0.3s ease forwards;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
    from {
        opacity: 0;
        transform: translateY(10px);
    }
}

@keyframes slideInDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.reverse2pdf-download-options {
    position: relative;
}

.reverse2pdf-notification {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
}

@media (max-width: 480px) {
    .reverse2pdf-download-options div:last-child {
        flex-direction: column;
    }
    
    .reverse2pdf-notification {
        right: 10px;
        left: 10px;
        max-width: none;
    }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', frontendCSS);
