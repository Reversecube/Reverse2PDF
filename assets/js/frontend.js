/**
 * Reverse2PDF Frontend JavaScript
 */

(function($) {
    'use strict';
    
    // Global frontend object
    window.Reverse2PDF_Frontend = {
        init: function() {
            this.bindEvents();
            this.initViewers();
            this.loadStoredPDFs();
        },
        
        bindEvents: function() {
            // AJAX download buttons
            $(document).on('click', '.reverse2pdf-ajax-download', this.handleAjaxDownload);
            
            // PDF viewers
            $(document).on('click', '.pdf-viewer-controls .viewer-btn', this.handleViewerControls);
            
            // Form submission handling
            $(document).on('submit', '.reverse2pdf-form', this.handleFormSubmission);
            
            // Copy field names
            $(document).on('click', '.copy-field-name', this.copyFieldName);
            
            // PDF generation progress
            $(document).on('reverse2pdf:generation-started', this.showProgress);
            $(document).on('reverse2pdf:generation-completed', this.hideProgress);
            $(document).on('reverse2pdf:generation-failed', this.showError);
        },
        
        initViewers: function() {
            $('.reverse2pdf-pdf-viewer').each(function() {
                var $viewer = $(this);
                var templateId = $viewer.data('template-id');
                var datasetId = $viewer.data('dataset-id') || 0;
                
                if (templateId) {
                    Reverse2PDF_Frontend.loadPDFViewer($viewer, templateId, datasetId);
                }
            });
        },
        
        loadStoredPDFs: function() {
            // Check for stored PDFs from form submissions
            var sessionId = this.getSessionId();
            if (sessionId) {
                this.checkForStoredPDF(sessionId);
            }
        },
        
        handleAjaxDownload: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var templateId = $btn.data('template-id');
            var datasetId = $btn.data('dataset-id') || 0;
            var filename = $btn.data('filename') || '';
            var format = $btn.data('format') || 'pdf';
            
            if (!templateId) {
                Reverse2PDF_Frontend.showNotification('Template ID required', 'error');
                return;
            }
            
            var originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner"></span> Generating...');
            
            // Trigger generation started event
            $(document).trigger('reverse2pdf:generation-started', [templateId, datasetId]);
            
            $.ajax({
                url: reverse2pdf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_generate',
                    template_id: templateId,
                    dataset_id: datasetId,
                    format: format,
                    filename: filename,
                    nonce: reverse2pdf_ajax.nonce
                },
                timeout: 60000, // 60 seconds timeout
                success: function(response) {
                    if (response.success) {
                        // Trigger generation completed event
                        $(document).trigger('reverse2pdf:generation-completed', [response.data]);
                        
                        // Start download
                        Reverse2PDF_Frontend.downloadFile(response.data.pdf_url, filename || 'document.pdf');
                        
                        Reverse2PDF_Frontend.showNotification('PDF generated successfully!', 'success');
                    } else {
                        $(document).trigger('reverse2pdf:generation-failed', [response.data]);
                        Reverse2PDF_Frontend.showNotification('Error: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $(document).trigger('reverse2pdf:generation-failed', [error]);
                    
                    if (status === 'timeout') {
                        Reverse2PDF_Frontend.showNotification('PDF generation timed out. Please try again.', 'error');
                    } else {
                        Reverse2PDF_Frontend.showNotification('An error occurred while generating PDF.', 'error');
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        },
        
        loadPDFViewer: function($viewer, templateId, datasetId) {
            var $loading = $viewer.find('.pdf-loading');
            
            $loading.html('<div class="loading-animation"><div class="spinner"></div><p>Loading PDF...</p></div>');
            
            $.ajax({
                url: reverse2pdf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_generate',
                    template_id: templateId,
                    dataset_id: datasetId,
                    preview: true,
                    nonce: reverse2pdf_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var pdfUrl = response.data.pdf_url;
                        
                        // Create iframe for PDF display
                        var iframe = '<iframe src="' + pdfUrl + '" style="width: 100%; height: 100%; border: none;" frameborder="0"></iframe>';
                        
                        $viewer.html(iframe);
                        
                        // Add viewer controls if enabled
                        var $container = $viewer.closest('.reverse2pdf-viewer-container');
                        var $controls = $container.find('.pdf-viewer-controls');
                        
                        if ($controls.length) {
                            Reverse2PDF_Frontend.initViewerControls($controls, pdfUrl);
                        }
                    } else {
                        $loading.html('<div class="pdf-error"><p>Failed to load PDF: ' + response.data + '</p></div>');
                    }
                },
                error: function() {
                    $loading.html('<div class="pdf-error"><p>Failed to load PDF viewer.</p></div>');
                }
            });
        },
        
        initViewerControls: function($controls, pdfUrl) {
            $controls.find('[data-action="download"]').off('click').on('click', function() {
                Reverse2PDF_Frontend.downloadFile(pdfUrl, 'document.pdf');
            });
            
            $controls.find('[data-action="print"]').off('click').on('click', function() {
                var printWindow = window.open(pdfUrl, '_blank');
                printWindow.addEventListener('load', function() {
                    printWindow.print();
                });
            });
            
            // Zoom controls
            var currentZoom = 100;
            $controls.find('[data-action="zoom-in"]').off('click').on('click', function() {
                currentZoom = Math.min(currentZoom + 25, 200);
                Reverse2PDF_Frontend.updateZoom($controls, currentZoom);
            });
            
            $controls.find('[data-action="zoom-out"]').off('click').on('click', function() {
                currentZoom = Math.max(currentZoom - 25, 25);
                Reverse2PDF_Frontend.updateZoom($controls, currentZoom);
            });
            
            // Page navigation (if supported)
            $controls.find('[data-action="previous-page"]').off('click').on('click', function() {
                // Implementation depends on PDF viewer
                console.log('Previous page');
            });
            
            $controls.find('[data-action="next-page"]').off('click').on('click', function() {
                // Implementation depends on PDF viewer
                console.log('Next page');
            });
        },
        
        updateZoom: function($controls, zoomLevel) {
            $controls.find('.zoom-level').text(zoomLevel + '%');
            
            // Update iframe zoom if possible
            var $iframe = $controls.closest('.reverse2pdf-viewer-container').find('iframe');
            $iframe.css('transform', 'scale(' + (zoomLevel / 100) + ')');
        },
        
        handleViewerControls: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var action = $btn.data('action');
            var $container = $btn.closest('.reverse2pdf-viewer-container');
            
            switch (action) {
                case 'fullscreen':
                    Reverse2PDF_Frontend.toggleFullscreen($container);
                    break;
                case 'refresh':
                    Reverse2PDF_Frontend.refreshViewer($container);
                    break;
                case 'share':
                    Reverse2PDF_Frontend.sharePDF($container);
                    break;
            }
        },
        
        handleFormSubmission: function(e) {
            var $form = $(this);
            var generatePDF = $form.data('generate-pdf');
            var templateId = $form.data('template-id');
            
            if (generatePDF && templateId) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'reverse2pdf_process_form');
                formData.append('template_id', templateId);
                formData.append('nonce', reverse2pdf_ajax.nonce);
                
                var $submitBtn = $form.find('input[type="submit"], button[type="submit"]');
                var originalText = $submitBtn.val() || $submitBtn.text();
                
                $submitBtn.prop('disabled', true);
                if ($submitBtn.is('input')) {
                    $submitBtn.val('Processing...');
                } else {
                    $submitBtn.text('Processing...');
                }
                
                $.ajax({
                    url: reverse2pdf_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Reverse2PDF_Frontend.showNotification('Form submitted and PDF generated!', 'success');
                            
                            // Provide download link
                            if (response.data.pdf_url) {
                                var downloadBtn = '<a href="' + response.data.pdf_url + '" target="_blank" class="pdf-download-link">Download Your PDF</a>';
                                $form.after('<div class="pdf-result">' + downloadBtn + '</div>');
                            }
                            
                            // Reset form if configured
                            if ($form.data('reset-after-submit')) {
                                $form[0].reset();
                            }
                        } else {
                            Reverse2PDF_Frontend.showNotification('Error: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        Reverse2PDF_Frontend.showNotification('Form submission failed. Please try again.', 'error');
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false);
                        if ($submitBtn.is('input')) {
                            $submitBtn.val(originalText);
                        } else {
                            $submitBtn.text(originalText);
                        }
                    }
                });
            }
        },
        
        copyFieldName: function(e) {
            e.preventDefault();
            
            var fieldName = $(this).data('field');
            var placeholder = '{' + fieldName + '}';
            
            // Create temporary input to copy text
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(placeholder).select();
            document.execCommand('copy');
            $temp.remove();
            
            // Show feedback
            var $btn = $(this);
            var originalHTML = $btn.html();
            $btn.html('<span class="dashicons dashicons-yes"></span>').addClass('copied');
            
            setTimeout(function() {
                $btn.html(originalHTML).removeClass('copied');
            }, 1500);
            
            Reverse2PDF_Frontend.showNotification('Field name copied: ' + placeholder, 'info', 2000);
        },
        
        downloadFile: function(url, filename) {
            // Create invisible download link
            var link = document.createElement('a');
            link.href = url;
            link.download = filename;
            link.style.display = 'none';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        showProgress: function(e, templateId, datasetId) {
            // Show global progress indicator
            if (!$('#reverse2pdf-progress').length) {
                var progressHTML = '<div id="reverse2pdf-progress" class="reverse2pdf-progress-overlay">' +
                    '<div class="progress-modal">' +
                    '<div class="progress-content">' +
                    '<div class="progress-spinner"></div>' +
                    '<h3>Generating PDF...</h3>' +
                    '<p>Please wait while your PDF is being created.</p>' +
                    '<div class="progress-bar"><div class="progress-fill"></div></div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                
                $('body').append(progressHTML);
            }
            
            $('#reverse2pdf-progress').fadeIn();
            
            // Simulate progress
            var progress = 0;
            var progressInterval = setInterval(function() {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                
                $('#reverse2pdf-progress .progress-fill').css('width', progress + '%');
            }, 500);
            
            // Store interval for cleanup
            $('#reverse2pdf-progress').data('interval', progressInterval);
        },
        
        hideProgress: function(e, data) {
            var progressInterval = $('#reverse2pdf-progress').data('interval');
            if (progressInterval) {
                clearInterval(progressInterval);
            }
            
            // Complete progress bar
            $('#reverse2pdf-progress .progress-fill').css('width', '100%');
            
            setTimeout(function() {
                $('#reverse2pdf-progress').fadeOut(function() {
                    $(this).remove();
                });
            }, 500);
        },
        
        showError: function(e, error) {
            var progressInterval = $('#reverse2pdf-progress').data('interval');
            if (progressInterval) {
                clearInterval(progressInterval);
            }
            
            $('#reverse2pdf-progress .progress-content').html(
                '<div class="error-icon">⚠</div>' +
                '<h3>PDF Generation Failed</h3>' +
                '<p>' + error + '</p>' +
                '<button class="button" onclick="$(\'#reverse2pdf-progress\').fadeOut();">Close</button>'
            );
        },
        
        showNotification: function(message, type, duration) {
            type = type || 'info';
            duration = duration || 4000;
            
            // Remove existing notifications
            $('.reverse2pdf-notification').remove();
            
            var $notification = $('<div class="reverse2pdf-notification ' + type + '">' + message + '</div>');
            
            // Style the notification
            $notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '15px 20px',
                borderRadius: '4px',
                color: '#fff',
                fontSize: '14px',
                fontWeight: '500',
                zIndex: '999999',
                maxWidth: '350px',
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
                info: '#17a2b8'
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
            
            // Click to dismiss
            $notification.on('click', function() {
                $(this).css({
                    opacity: '0',
                    transform: 'translateX(100%)'
                });
            });
        },
        
        getSessionId: function() {
            // Simple session ID generation
            var sessionId = sessionStorage.getItem('reverse2pdf_session');
            if (!sessionId) {
                sessionId = 'session_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
                sessionStorage.setItem('reverse2pdf_session', sessionId);
            }
            return sessionId;
        },
        
        checkForStoredPDF: function(sessionId) {
            $.ajax({
                url: reverse2pdf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'reverse2pdf_check_stored_pdf',
                    session_id: sessionId,
                    nonce: reverse2pdf_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.pdf_url) {
                        // Show download notification for stored PDF
                        var downloadHTML = '<div class="stored-pdf-notification">' +
                            '<h4>Your PDF is Ready!</h4>' +
                            '<p>A PDF was generated from your recent form submission.</p>' +
                            '<a href="' + response.data.pdf_url + '" class="button button-primary" target="_blank">Download PDF</a>' +
                            '<button class="button dismiss-notification" onclick="$(this).closest(\'.stored-pdf-notification\').fadeOut();">Dismiss</button>' +
                            '</div>';
                        
                        $('body').append(downloadHTML);
                        
                        // Position the notification
                        $('.stored-pdf-notification').css({
                            position: 'fixed',
                            top: '50%',
                            left: '50%',
                            transform: 'translate(-50%, -50%)',
                            background: '#fff',
                            padding: '20px',
                            borderRadius: '8px',
                            boxShadow: '0 8px 32px rgba(0,0,0,0.2)',
                            zIndex: '999999',
                            textAlign: 'center',
                            minWidth: '300px'
                        });
                    }
                }
            });
        },
        
        toggleFullscreen: function($container) {
            if (!document.fullscreenElement) {
                $container[0].requestFullscreen().catch(err => {
                    console.log('Error attempting to enable fullscreen:', err);
                });
            } else {
                document.exitFullscreen();
            }
        },
        
        refreshViewer: function($container) {
            var $viewer = $container.find('.reverse2pdf-pdf-viewer');
            var templateId = $viewer.data('template-id');
            var datasetId = $viewer.data('dataset-id') || 0;
            
            $viewer.html('<div class="pdf-loading">Refreshing...</div>');
            this.loadPDFViewer($viewer, templateId, datasetId);
        },
        
        sharePDF: function($container) {
            var $iframe = $container.find('iframe');
            if ($iframe.length) {
                var pdfUrl = $iframe.attr('src');
                
                if (navigator.share) {
                    navigator.share({
                        title: 'PDF Document',
                        url: pdfUrl
                    });
                } else {
                    // Fallback: copy to clipboard
                    var $temp = $('<input>');
                    $('body').append($temp);
                    $temp.val(pdfUrl).select();
                    document.execCommand('copy');
                    $temp.remove();
                    
                    this.showNotification('PDF link copied to clipboard!', 'info');
                }
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        Reverse2PDF_Frontend.init();
    });
    
})(jQuery);
