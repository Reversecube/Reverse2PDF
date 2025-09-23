/**
 * Reverse2PDF Template Builder JavaScript
 */

(function($) {
    'use strict';
    
    // Main Builder Object
    window.Reverse2PDF_Builder = {
        
        // Properties
        canvas: null,
        selectedElement: null,
        templateData: { pages: [{ elements: [] }] },
        currentPage: 1,
        totalPages: 1,
        zoom: 1,
        gridSize: 10,
        snapToGrid: true,
        showGrid: true,
        history: [],
        historyIndex: -1,
        
        // Initialize builder
        init: function() {
            this.canvas = $('#builder-canvas');
            this.setupEventHandlers();
            this.setupDragDrop();
            this.setupCanvas();
            this.loadTemplateData();
            this.updateUI();
        },
        
        setupEventHandlers: function() {
            var self = this;
            
            // Toolbar buttons
            $('#save-template').on('click', function() { self.saveTemplate(); });
            $('#preview-btn').on('click', function() { self.previewTemplate(); });
            $('#undo-btn').on('click', function() { self.undo(); });
            $('#redo-btn').on('click', function() { self.redo(); });
            
            // Zoom controls
            $('#zoom-level').on('change', function() {
                self.setZoom(parseFloat($(this).val()));
            });
            $('#zoom-in').on('click', function() { self.zoomIn(); });
            $('#zoom-out').on('click', function() { self.zoomOut(); });
            $('#fit-to-page').on('click', function() { self.fitToPage(); });
            
            // Page controls
            $('#add-page').on('click', function() { self.addPage(); });
            $('#delete-page').on('click', function() { self.deletePage(); });
            $('#prev-page').on('click', function() { self.previousPage(); });
            $('#next-page').on('click', function() { self.nextPage(); });
            
            // Canvas controls
            $('#show-grid').on('change', function() { self.toggleGrid(); });
            $('#snap-to-grid').on('change', function() { self.toggleSnap(); });
            $('#show-rulers').on('change', function() { self.toggleRulers(); });
            
            // Paper settings
            $('#paper-size').on('change', function() { self.updatePaperSize(); });
            $('#orientation').on('change', function() { self.updateOrientation(); });
            
            // Element selection
            $(document).on('click', '.template-element', function(e) {
                e.stopPropagation();
                self.selectElement($(this));
            });
            
            // Canvas click to deselect
            this.canvas.on('click', function(e) {
                if (e.target === this) {
                    self.deselectElement();
                }
            });
            
            // Sidebar tabs
            $('.sidebar-tab').on('click', function() {
                var tab = $(this).data('tab');
                self.switchSidebarTab($(this), tab);
            });
            
            // Category toggles
            $(document).on('click', '.category-title', function() {
                $(this).closest('.element-category').toggleClass('collapsed');
            });
            
            // Property changes
            $(document).on('change', '.property-field input, .property-field select, .property-field textarea', function() {
                self.updateElementProperty($(this));
            });
            
            // Layer controls
            $('#layer-up').on('click', function() { self.moveLayerUp(); });
            $('#layer-down').on('click', function() { self.moveLayerDown(); });
            $('#layer-duplicate').on('click', function() { self.duplicateElement(); });
            $('#layer-delete').on('click', function() { self.deleteElement(); });
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                self.handleKeyboardShortcuts(e);
            });
            
            // Elements search
            $('#elements-search').on('input', function() {
                self.searchElements($(this).val());
            });
        },
        
        setupDragDrop: function() {
            var self = this;
            
            // Make element items draggable
            $('.element-item').draggable({
                helper: 'clone',
                appendTo: 'body',
                zIndex: 10000,
                start: function(event, ui) {
                    ui.helper.addClass('dragging');
                }
            });
            
            // Make canvas droppable
            $('.pdf-page').droppable({
                accept: '.element-item',
                drop: function(event, ui) {
                    var elementType = ui.helper.data('type');
                    var offset = $(this).offset();
                    var x = (event.pageX - offset.left) / self.zoom;
                    var y = (event.pageY - offset.top) / self.zoom;
                    
                    if (self.snapToGrid) {
                        x = Math.round(x / self.gridSize) * self.gridSize;
                        y = Math.round(y / self.gridSize) * self.gridSize;
                    }
                    
                    self.createElement(elementType, x, y);
                }
            });
        },
        
        setupCanvas: function() {
            this.updateCanvasSize();
            this.updateGrid();
            this.updateRulers();
        },
        
        createElement: function(type, x, y) {
            var elementId = this.generateElementId();
            var elementData = this.getElementDefaults(type);
            
            elementData.id = elementId;
            elementData.type = type;
            elementData.x = x;
            elementData.y = y;
            
            // Add to current page
            this.templateData.pages[this.currentPage - 1].elements.push(elementData);
            
            // Render element
            this.renderElement(elementData);
            
            // Select new element
            this.selectElement($('[data-element-id="' + elementId + '"]'));
            
            // Save state
            this.saveState();
            this.updateLayers();
        },
        
        getElementDefaults: function(type) {
            var defaults = {
                width: 100,
                height: 25,
                fontSize: 12,
                fontFamily: 'Arial',
                color: '#000000'
            };
            
            switch(type) {
                case 'text':
                    defaults.content = 'Sample Text';
                    defaults.width = 200;
                    break;
                case 'image':
                    defaults.src = '';
                    defaults.width = 100;
                    defaults.height = 100;
                    break;
                case 'line':
                    defaults.thickness = 1;
                    defaults.width = 100;
                    defaults.height = 1;
                    break;
                case 'rectangle':
                    defaults.fillColor = 'transparent';
                    defaults.borderColor = '#000000';
                    defaults.borderWidth = 1;
                    defaults.width = 100;
                    defaults.height = 50;
                    break;
            }
            
            return defaults;
        },
        
        renderElement: function(elementData) {
            var $element = $('<div>')
                .addClass('template-element')
                .attr('data-element-id', elementData.id)
                .attr('data-element-type', elementData.type)
                .css({
                    position: 'absolute',
                    left: elementData.x + 'px',
                    top: elementData.y + 'px',
                    width: elementData.width + 'px',
                    height: elementData.height + 'px'
                });
            
            // Add content based on type
            this.updateElementContent($element, elementData);
            
            // Add to page
            this.getCurrentPage().find('.page-content').append($element);
            
            // Make interactive
            this.makeElementInteractive($element);
        },
        
        makeElementInteractive: function($element) {
            var self = this;
            
            $element.draggable({
                containment: $element.closest('.pdf-page'),
                grid: self.snapToGrid ? [self.gridSize, self.gridSize] : null,
                start: function() {
                    self.selectElement($element);
                },
                drag: function(event, ui) {
                    self.updateElementData($element, {
                        x: ui.position.left,
                        y: ui.position.top
                    });
                },
                stop: function() {
                    self.saveState();
                }
            });
            
            $element.resizable({
                handles: 'n,s,e,w,ne,nw,se,sw',
                grid: self.snapToGrid ? [self.gridSize, self.gridSize] : null,
                start: function() {
                    self.selectElement($element);
                },
                resize: function(event, ui) {
                    self.updateElementData($element, {
                        width: ui.size.width,
                        height: ui.size.height
                    });
                },
                stop: function() {
                    self.saveState();
                }
            });
        },
        
        updateElementContent: function($element, elementData) {
            var content = '';
            
            switch(elementData.type) {
                case 'text':
                    content = '<div class="text-content">' + (elementData.content || 'Text') + '</div>';
                    break;
                case 'image':
                    if (elementData.src) {
                        content = '<img src="' + elementData.src + '" style="max-width: 100%; max-height: 100%;">';
                    } else {
                        content = '<div class="image-placeholder">📷 Image</div>';
                    }
                    break;
                case 'line':
                    content = '<div style="width: 100%; height: ' + (elementData.thickness || 1) + 'px; background: ' + (elementData.color || '#000') + ';"></div>';
                    break;
                case 'rectangle':
                    var style = 'width: 100%; height: 100%;';
                    if (elementData.fillColor && elementData.fillColor !== 'transparent') {
                        style += 'background: ' + elementData.fillColor + ';';
                    }
                    if (elementData.borderWidth > 0) {
                        style += 'border: ' + elementData.borderWidth + 'px solid ' + (elementData.borderColor || '#000') + ';';
                    }
                    content = '<div style="' + style + '"></div>';
                    break;
            }
            
            $element.html(content);
            
            // Apply styling
            if (elementData.fontSize) $element.css('fontSize', elementData.fontSize + 'px');
            if (elementData.fontFamily) $element.css('fontFamily', elementData.fontFamily);
            if (elementData.color) $element.css('color', elementData.color);
            if (elementData.textAlign) $element.css('textAlign', elementData.textAlign);
        },
        
        selectElement: function($element) {
            // Deselect previous
            $('.template-element').removeClass('selected');
            
            // Select new
            $element.addClass('selected');
            this.selectedElement = $element;
            
            // Update properties panel
            this.updatePropertiesPanel();
            
            // Update layers
            this.updateLayerSelection();
        },
        
        deselectElement: function() {
            $('.template-element').removeClass('selected');
            this.selectedElement = null;
            this.updatePropertiesPanel();
            this.updateLayerSelection();
        },
        
        updatePropertiesPanel: function() {
            var $content = $('.properties-content');
            
            if (!this.selectedElement) {
                $content.html('<div class="no-selection"><span class="dashicons dashicons-admin-settings"></span><p>Select an element to edit its properties</p></div>');
                return;
            }
            
            var elementId = this.selectedElement.attr('data-element-id');
            var elementData = this.getElementData(elementId);
            
            if (!elementData) return;
            
            var html = this.generatePropertiesHTML(elementData);
            $content.html(html);
            
            // Initialize color pickers
            if (typeof $.fn.wpColorPicker !== 'undefined') {
                $content.find('.color-picker').wpColorPicker();
            }
        },
        
        generatePropertiesHTML: function(elementData) {
            var html = '<div class="property-groups">';
            
            // Position and Size
            html += '<div class="property-group">';
            html += '<h4 class="property-group-title">Position & Size</h4>';
            html += '<div class="property-fields">';
            
            html += '<div class="property-field-group">';
            html += '<div class="property-field">';
            html += '<label>X</label>';
            html += '<input type="number" class="property-field" data-property="x" value="' + (elementData.x || 0) + '">';
            html += '</div>';
            html += '<div class="property-field">';
            html += '<label>Y</label>';
            html += '<input type="number" class="property-field" data-property="y" value="' + (elementData.y || 0) + '">';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="property-field-group">';
            html += '<div class="property-field">';
            html += '<label>Width</label>';
            html += '<input type="number" class="property-field" data-property="width" value="' + (elementData.width || 100) + '">';
            html += '</div>';
            html += '<div class="property-field">';
            html += '<label>Height</label>';
            html += '<input type="number" class="property-field" data-property="height" value="' + (elementData.height || 25) + '">';
            html += '</div>';
            html += '</div>';
            
            html += '</div>';
            html += '</div>';
            
            // Type-specific properties
            html += this.getTypeSpecificPropertiesHTML(elementData);
            
            html += '</div>';
            
            return html;
        },
        
        getTypeSpecificPropertiesHTML: function(elementData) {
            var html = '<div class="property-group">';
            html += '<h4 class="property-group-title">' + elementData.type.charAt(0).toUpperCase() + elementData.type.slice(1) + ' Properties</h4>';
            html += '<div class="property-fields">';
            
            switch(elementData.type) {
                case 'text':
                    html += '<div class="property-field">';
                    html += '<label>Content</label>';
                    html += '<textarea class="property-field" data-property="content">' + (elementData.content || '') + '</textarea>';
                    html += '</div>';
                    
                    html += '<div class="property-field">';
                    html += '<label>Font Size</label>';
                    html += '<input type="number" class="property-field" data-property="fontSize" value="' + (elementData.fontSize || 12) + '">';
                    html += '</div>';
                    
                    html += '<div class="property-field">';
                    html += '<label>Font Family</label>';
                    html += '<select class="property-field" data-property="fontFamily">';
                    var fonts = ['Arial', 'Times New Roman', 'Helvetica', 'Georgia', 'Verdana'];
                    fonts.forEach(function(font) {
                        var selected = elementData.fontFamily === font ? 'selected' : '';
                        html += '<option value="' + font + '" ' + selected + '>' + font + '</option>';
                    });
                    html += '</select>';
                    html += '</div>';
                    
                    html += '<div class="property-field">';
                    html += '<label>Text Color</label>';
                    html += '<input type="text" class="property-field color-picker" data-property="color" value="' + (elementData.color || '#000000') + '">';
                    html += '</div>';
                    break;
                    
                case 'image':
                    html += '<div class="property-field">';
                    html += '<label>Image URL</label>';
                    html += '<div class="media-field">';
                    html += '<input type="text" class="property-field" data-property="src" value="' + (elementData.src || '') + '">';
                    html += '<button type="button" class="media-upload-btn">Browse</button>';
                    html += '</div>';
                    html += '</div>';
                    break;
            }
            
            html += '</div>';
            html += '</div>';
            
            return html;
        },
        
        updateElementProperty: function($input) {
            if (!this.selectedElement) return;
            
            var property = $input.data('property');
            var value = $input.val();
            var elementId = this.selectedElement.attr('data-element-id');
            
            // Update data
            this.updateElementData(this.selectedElement, {[property]: value});
            
            // Update visual
            this.updateElementVisual(elementId);
        },
        
        updateElementData: function($element, data) {
            var elementId = $element.attr('data-element-id');
            var elementData = this.getElementData(elementId);
            
            if (elementData) {
                Object.assign(elementData, data);
            }
        },
        
        updateElementVisual: function(elementId) {
            var $element = $('[data-element-id="' + elementId + '"]');
            var elementData = this.getElementData(elementId);
            
            if (!$element.length || !elementData) return;
            
            // Update position and size
            $element.css({
                left: elementData.x + 'px',
                top: elementData.y + 'px',
                width: elementData.width + 'px',
                height: elementData.height + 'px'
            });
            
            // Update content
            this.updateElementContent($element, elementData);
        },
        
        getElementData: function(elementId) {
            var currentPageData = this.templateData.pages[this.currentPage - 1];
            return currentPageData.elements.find(function(el) {
                return el.id === elementId;
            });
        },
        
        getCurrentPage: function() {
            return $('.pdf-page[data-page="' + this.currentPage + '"]');
        },
        
        generateElementId: function() {
            return 'element_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },
        
        // Save and load functionality
        saveTemplate: function() {
            var templateName = $('#template-name').val();
            var templateDescription = $('#template-description').val();
            var templateId = $('#template-id').val();
            
            if (!templateName) {
                alert('Please enter a template name');
                return;
            }
            
            var $btn = $('#save-template');
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('Saving...');
            
            $.post(reverse2pdf_builder.ajax_url, {
                action: 'reverse2pdf_save_template_data',
                template_id: templateId,
                template_name: templateName,
                template_description: templateDescription,
                template_data: JSON.stringify(this.templateData),
                nonce: reverse2pdf_builder.nonce
            })
            .done(function(response) {
                if (response.success) {
                    if (!templateId) {
                        $('#template-id').val(response.data.template_id);
                    }
                    Reverse2PDF_Builder.showNotification('Template saved successfully!', 'success');
                } else {
                    Reverse2PDF_Builder.showNotification('Error: ' + response.data, 'error');
                }
            })
            .fail(function() {
                Reverse2PDF_Builder.showNotification('Save failed. Please try again.', 'error');
            })
            .always(function() {
                $btn.prop('disabled', false).text(originalText);
            });
        },
        
        loadTemplateData: function() {
            var templateData = $('#template-data').val();
            if (templateData) {
                try {
                    this.templateData = JSON.parse(templateData);
                    this.renderTemplate();
                } catch(e) {
                    console.error('Failed to parse template data');
                }
            }
        },
        
        renderTemplate: function() {
            var self = this;
            
            // Clear canvas
            $('.pdf-page').remove();
            
            // Render pages
            this.templateData.pages.forEach(function(pageData, index) {
                self.addPageToCanvas(index + 1);
                
                pageData.elements.forEach(function(elementData) {
                    self.renderElement(elementData);
                });
            });
            
            this.totalPages = this.templateData.pages.length;
            this.updateUI();
        },
        
        addPageToCanvas: function(pageNumber) {
            var $page = $('<div class="pdf-page" data-page="' + pageNumber + '">' +
                '<div class="page-background"></div>' +
                '<div class="page-content"></div>' +
                '<div class="page-overlay"></div>' +
                '</div>');
            
            this.canvas.append($page);
            
            $page.droppable({
                accept: '.element-item',
                drop: function(event, ui) {
                    // Handle drop
                }
            });
        },
        
        // Additional utility methods
        showNotification: function(message, type) {
            var $notification = $('<div class="builder-notification ' + type + '">' + message + '</div>');
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        updateUI: function() {
            $('#current-page').text(this.currentPage);
            $('#total-pages').text(this.totalPages);
            $('#prev-page').prop('disabled', this.currentPage <= 1);
            $('#next-page').prop('disabled', this.currentPage >= this.totalPages);
            
            this.updateLayers();
        },
        
        updateLayers: function() {
            var $layersList = $('.layers-list');
            $layersList.empty();
            
            var currentPageData = this.templateData.pages[this.currentPage - 1];
            if (currentPageData && currentPageData.elements) {
                currentPageData.elements.forEach(function(element) {
                    var $layer = $('<div class="layer-item" data-element-id="' + element.id + '">' +
                        '<span class="dashicons dashicons-admin-generic"></span>' +
                        '<span class="layer-name">' + (element.id || 'Element') + '</span>' +
                        '<span class="layer-type">' + element.type + '</span>' +
                        '</div>');
                    
                    $layersList.append($layer);
                });
            }
        },
        
        // State management
        saveState: function() {
            this.history = this.history.slice(0, this.historyIndex + 1);
            this.history.push(JSON.stringify(this.templateData));
            
            if (this.history.length > 50) {
                this.history.shift();
            } else {
                this.historyIndex++;
            }
        },
        
        undo: function() {
            if (this.historyIndex > 0) {
                this.historyIndex--;
                this.templateData = JSON.parse(this.history[this.historyIndex]);
                this.renderTemplate();
            }
        },
        
        redo: function() {
            if (this.historyIndex < this.history.length - 1) {
                this.historyIndex++;
                this.templateData = JSON.parse(this.history[this.historyIndex]);
                this.renderTemplate();
            }
        },
        
        handleKeyboardShortcuts: function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 's':
                        e.preventDefault();
                        this.saveTemplate();
                        break;
                    case 'z':
                        e.preventDefault();
                        if (e.shiftKey) {
                            this.redo();
                        } else {
                            this.undo();
                        }
                        break;
                }
            }
            
            if (e.key === 'Delete' && this.selectedElement) {
                this.deleteElement();
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#reverse2pdf-builder-container').length) {
            Reverse2PDF_Builder.init();
        }
    });
    
})(jQuery);
