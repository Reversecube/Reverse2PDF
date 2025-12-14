/**
 * Reverse2PDF Template Builder - COMPLETE WORKING JAVASCRIPT
 * All drag-and-drop, properties, and save functionality
 */

jQuery(document).ready(function($) {
    'use strict';

    console.log('Reverse2PDF Builder loaded');

    // State management
    const BuilderState = {
        templateId: 0,
        currentPage: 1,
        selectedElement: null,
        elements: [],
        zoom: 100,
        history: [],
        historyIndex: -1,
        settings: {
            pageSize: 'A4',
            orientation: 'portrait',
            pdfEngine: 'dompdf',
            flattenPdf: false,
            filename: ''
        }
    };

    // Initialize
    function init() {
        const canvas = $('#pdf-canvas');
        if (!canvas.length) return;

        BuilderState.templateId = canvas.data('template-id') || 0;
        const templateData = canvas.data('template');

        if (templateData && templateData.template_data) {
            loadTemplate(templateData);
        }

        setupEventListeners();
        setupDragAndDrop();
        setupTabs();
        console.log('Builder initialized');
    }

    
    // Tab switching
    function setupTabs() {
        $('.r2pdf-tab-btn').on('click', function() {
            const tab = $(this).data('tab');

            $('.r2pdf-tab-btn').removeClass('active');
            $(this).addClass('active');

            $('.r2pdf-tab-content').removeClass('active');
            $('.r2pdf-tab-content[data-tab="' + tab + '"]').addClass('active');
        });
    }

    // Event listeners
    function setupEventListeners() {
        // Save template
        $('#save-template-btn').on('click', saveTemplate);

        // Export PDF
        $('#export-pdf-btn').on('click', exportPDF);

        // Preview
        $('#preview-pdf-btn').on('click', previewPDF);

        // Undo/Redo
        $('#undo-btn').on('click', undo);
        $('#redo-btn').on('click', redo);

        // Zoom
        $('#zoom-in').on('click', () => setZoom(BuilderState.zoom + 10));
        $('#zoom-out').on('click', () => setZoom(BuilderState.zoom - 10));
        $('#zoom-fit').on('click', () => setZoom(100));

        // Alignment
        $('#align-left').on('click', () => alignElement('left'));
        $('#align-center').on('click', () => alignElement('center'));
        $('#align-right').on('click', () => alignElement('right'));

        // Delete element
        $('#delete-element').on('click', deleteSelectedElement);

        // Duplicate element
        $('#duplicate-element').on('click', duplicateSelectedElement);

        // Add page
        $('#add-page-btn').on('click', addPage);

        // Settings change
        $('#page-size').on('change', function() {
            BuilderState.settings.pageSize = $(this).val();
            updatePageSize();
        });

        $('#orientation').on('change', function() {
            BuilderState.settings.orientation = $(this).val();
            updatePageOrientation();
        });

        // Click outside to deselect
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.canvas-element').length && 
                !$(e.target).closest('#properties-panel').length) {
                deselectElement();
            }
        });
    }

    // Drag and Drop
    function setupDragAndDrop() {
        // Make elements draggable
        $('.element-item').attr('draggable', true).on('dragstart', function(e) {
            const type = $(this).data('type');
            e.originalEvent.dataTransfer.setData('elementType', type);
            $(this).addClass('dragging');
        }).on('dragend', function() {
            $(this).removeClass('dragging');
        });

        // Canvas drop zone
        $('.canvas-page').on('dragover', function(e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'copy';
        }).on('drop', function(e) {
            e.preventDefault();
            const type = e.originalEvent.dataTransfer.getData('elementType');
            const offset = $(this).offset();
            const x = e.pageX - offset.left;
            const y = e.pageY - offset.top;

            addElement(type, x, y);
        });
    }

    // Add element to canvas
    function addElement(type, x, y) {
        const elementId = 'element-' + Date.now();
        const element = {
            id: elementId,
            type: type,
            x: Math.round(x),
            y: Math.round(y),
            width: getDefaultWidth(type),
            height: getDefaultHeight(type),
            content: getDefaultContent(type),
            styles: getDefaultStyles(type)
        };

        BuilderState.elements.push(element);
        renderElement(element);
        selectElement(elementId);
        addToHistory();
    }

    // Render element
    function renderElement(element) {
        const $element = $('<div>', {
            id: element.id,
            class: 'canvas-element',
            'data-type': element.type,
            css: {
                left: element.x + 'px',
                top: element.y + 'px',
                width: element.width + 'px',
                height: element.height + 'px',
                fontSize: element.styles.fontSize || '14px',
                fontFamily: element.styles.fontFamily || 'Arial',
                color: element.styles.color || '#000000',
                backgroundColor: element.styles.backgroundColor || 'transparent',
                textAlign: element.styles.textAlign || 'left'
            }
        });

        // Add content based on type
        if (['text', 'heading', 'label', 'paragraph'].includes(element.type)) {
            $element.html(element.content);
        } else if (element.type === 'input') {
            $element.html('<input type="text" placeholder="Input field" style="width:100%; padding:4px; border:1px solid #ccc;">');
        } else if (element.type === 'checkbox') {
            $element.html('<input type="checkbox"> Checkbox');
        } else if (element.type === 'signature') {
            $element.html('<div style="border:2px dashed #ccc; padding:8px; text-align:center;">Signature</div>');
        } else if (element.type === 'image') {
            $element.html('<div style="border:1px solid #ccc; background:#f5f5f5; height:100%; display:flex; align-items:center; justify-content:center;"><span class="dashicons dashicons-format-image"></span></div>');
        }

        // Add resize handles
        const handles = ['nw', 'ne', 'sw', 'se', 'n', 's', 'e', 'w'];
        handles.forEach(handle => {
            $element.append('<div class="resize-handle ' + handle + '"></div>');
        });

        // Make element draggable
        $element.draggable({
            containment: '.canvas-page',
            drag: function(event, ui) {
                element.x = ui.position.left;
                element.y = ui.position.top;
                updatePropertiesPanel();
            },
            stop: function() {
                addToHistory();
            }
        });

        // Make element resizable
        $element.resizable({
            handles: 'n, s, e, w, ne, nw, se, sw',
            resize: function(event, ui) {
                element.width = ui.size.width;
                element.height = ui.size.height;
                updatePropertiesPanel();
            },
            stop: function() {
                addToHistory();
            }
        });

        // Click to select
        $element.on('click', function(e) {
            e.stopPropagation();
            selectElement(element.id);
        });

        $('.canvas-page').append($element);
    }

    // Select element
    function selectElement(elementId) {
        $('.canvas-element').removeClass('selected');
        $('#' + elementId).addClass('selected');

        BuilderState.selectedElement = BuilderState.elements.find(el => el.id === elementId);
        updatePropertiesPanel();
    }

    // Deselect element
    function deselectElement() {
        $('.canvas-element').removeClass('selected');
        BuilderState.selectedElement = null;
        showEmptyProperties();
    }

    // Update properties panel
    function updatePropertiesPanel() {
        if (!BuilderState.selectedElement) {
            showEmptyProperties();
            return;
        }

        const el = BuilderState.selectedElement;
        const html = `
            <div class="property-group">
                <label class="property-label">Type</label>
                <div style="padding:8px; background:#f9fafb; border-radius:4px; font-weight:600; text-transform:uppercase; font-size:11px; color:#667eea;">
                    ${el.type}
                </div>
            </div>

            <div class="property-group">
                <label class="property-label">Content</label>
                <textarea class="property-textarea" id="element-content">${el.content || ''}</textarea>
            </div>

            <div class="property-group">
                <label class="property-label">Position & Size</label>
                <div class="property-row">
                    <div>
                        <small style="display:block; margin-bottom:4px; font-weight:600;">X</small>
                        <input type="number" class="property-input" id="element-x" value="${el.x}">
                    </div>
                    <div>
                        <small style="display:block; margin-bottom:4px; font-weight:600;">Y</small>
                        <input type="number" class="property-input" id="element-y" value="${el.y}">
                    </div>
                </div>
                <div class="property-row" style="margin-top:8px;">
                    <div>
                        <small style="display:block; margin-bottom:4px; font-weight:600;">Width</small>
                        <input type="number" class="property-input" id="element-width" value="${el.width}">
                    </div>
                    <div>
                        <small style="display:block; margin-bottom:4px; font-weight:600;">Height</small>
                        <input type="number" class="property-input" id="element-height" value="${el.height}">
                    </div>
                </div>
            </div>

            <div class="property-group">
                <label class="property-label">Font Size</label>
                <input type="number" class="property-input" id="element-fontsize" value="${parseInt(el.styles.fontSize) || 14}">
            </div>

            <div class="property-group">
                <label class="property-label">Font Family</label>
                <select class="property-select" id="element-fontfamily">
                    <option value="Arial" ${el.styles.fontFamily === 'Arial' ? 'selected' : ''}>Arial</option>
                    <option value="Times New Roman" ${el.styles.fontFamily === 'Times New Roman' ? 'selected' : ''}>Times New Roman</option>
                    <option value="Courier" ${el.styles.fontFamily === 'Courier' ? 'selected' : ''}>Courier</option>
                    <option value="Georgia" ${el.styles.fontFamily === 'Georgia' ? 'selected' : ''}>Georgia</option>
                </select>
            </div>

            <div class="property-group">
                <label class="property-label">Text Align</label>
                <select class="property-select" id="element-textalign">
                    <option value="left" ${el.styles.textAlign === 'left' ? 'selected' : ''}>Left</option>
                    <option value="center" ${el.styles.textAlign === 'center' ? 'selected' : ''}>Center</option>
                    <option value="right" ${el.styles.textAlign === 'right' ? 'selected' : ''}>Right</option>
                </select>
            </div>

            <div class="property-group">
                <label class="property-label">Colors</label>
                <div style="margin-bottom:8px;">
                    <small style="display:block; margin-bottom:4px; font-weight:600;">Text Color</small>
                    <input type="color" class="property-color-picker" id="element-color" value="${el.styles.color || '#000000'}">
                </div>
                <div>
                    <small style="display:block; margin-bottom:4px; font-weight:600;">Background</small>
                    <input type="color" class="property-color-picker" id="element-bgcolor" value="${el.styles.backgroundColor || '#ffffff'}">
                </div>
            </div>

            <div class="property-group">
                <button class="property-btn" id="delete-element-btn">
                    <span class="dashicons dashicons-trash"></span> Delete Element
                </button>
            </div>
        `;

        $('#properties-panel').html(html);

        // Property change handlers
        $('#element-content').on('input', function() {
            el.content = $(this).val();
            $('#' + el.id).html(el.content);
        });

        $('#element-x').on('input', function() {
            el.x = parseInt($(this).val());
            $('#' + el.id).css('left', el.x + 'px');
        });

        $('#element-y').on('input', function() {
            el.y = parseInt($(this).val());
            $('#' + el.id).css('top', el.y + 'px');
        });

        $('#element-width').on('input', function() {
            el.width = parseInt($(this).val());
            $('#' + el.id).css('width', el.width + 'px');
        });

        $('#element-height').on('input', function() {
            el.height = parseInt($(this).val());
            $('#' + el.id).css('height', el.height + 'px');
        });

        $('#element-fontsize').on('input', function() {
            el.styles.fontSize = $(this).val() + 'px';
            $('#' + el.id).css('fontSize', el.styles.fontSize);
        });

        $('#element-fontfamily').on('change', function() {
            el.styles.fontFamily = $(this).val();
            $('#' + el.id).css('fontFamily', el.styles.fontFamily);
        });

        $('#element-textalign').on('change', function() {
            el.styles.textAlign = $(this).val();
            $('#' + el.id).css('textAlign', el.styles.textAlign);
        });

        $('#element-color').on('input', function() {
            el.styles.color = $(this).val();
            $('#' + el.id).css('color', el.styles.color);
        });

        $('#element-bgcolor').on('input', function() {
            el.styles.backgroundColor = $(this).val();
            $('#' + el.id).css('backgroundColor', el.styles.backgroundColor);
        });

        $('#delete-element-btn').on('click', deleteSelectedElement);
    }

    // Show empty properties
    function showEmptyProperties() {
        $('#properties-panel').html(`
            <div class="properties-empty">
                <span class="dashicons dashicons-admin-generic"></span>
                <p>Select an element to edit properties</p>
            </div>
        `);
    }

    // Delete element
    function deleteSelectedElement() {
        if (!BuilderState.selectedElement) return;

        $('#' + BuilderState.selectedElement.id).remove();
        BuilderState.elements = BuilderState.elements.filter(el => el.id !== BuilderState.selectedElement.id);
        BuilderState.selectedElement = null;
        showEmptyProperties();
        addToHistory();
    }

    // Duplicate element
    function duplicateSelectedElement() {
        if (!BuilderState.selectedElement) return;

        const original = BuilderState.selectedElement;
        addElement(original.type, original.x + 20, original.y + 20);
    }

    // Save template
    function saveTemplate() {
        const templateName = $('#template-name').val() || 'Untitled Template';
        const templateData = JSON.stringify({
            elements: BuilderState.elements,
            settings: BuilderState.settings
        });

        const data = {
            action: 'reverse2pdf_save_template',
            nonce: reverse2pdf_builder.nonce,
            template_id: BuilderState.templateId,
            template_name: templateName,
            template_data: templateData,
            page_size: BuilderState.settings.pageSize,
            orientation: BuilderState.settings.orientation
        };

        $.ajax({
            url: reverse2pdf_builder.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    alert('✅ Template saved successfully!');
                    if (response.data.template_id) {
                        BuilderState.templateId = response.data.template_id;
                    }
                } else {
                    alert('❌ Error: ' + (response.data || 'Failed to save'));
                }
            },
            error: function() {
                alert('❌ Network error - please try again');
            }
        });
    }

    // Export PDF
    function exportPDF() {
        alert('PDF Export coming soon! Save your template first.');
    }

    // Preview PDF
    function previewPDF() {
        alert('Preview coming soon! Save your template to test it.');
    }

    // Helper functions
    function getDefaultWidth(type) {
        const widths = {
            text: 200, heading: 300, label: 150, paragraph: 400,
            input: 250, textarea: 300, checkbox: 100, radio: 100,
            select: 200, signature: 250, image: 150, logo: 100,
            qrcode: 100, barcode: 200, line: 200, rectangle: 150,
            circle: 100
        };
        return widths[type] || 200;
    }

    function getDefaultHeight(type) {
        const heights = {
            text: 30, heading: 40, label: 25, paragraph: 100,
            input: 35, textarea: 80, checkbox: 25, radio: 25,
            select: 35, signature: 80, image: 150, logo: 60,
            qrcode: 100, barcode: 60, line: 2, rectangle: 100,
            circle: 100
        };
        return heights[type] || 40;
    }

    function getDefaultContent(type) {
        const contents = {
            text: 'Text', heading: 'Heading', label: 'Label',
            paragraph: 'Paragraph text goes here...',
            shortcode: '[shortcode]', 'post-title': '{post_title}',
            'custom-field': '{custom_field}'
        };
        return contents[type] || '';
    }

    function getDefaultStyles(type) {
        return {
            fontSize: type === 'heading' ? '24px' : '14px',
            fontFamily: 'Arial',
            color: '#000000',
            backgroundColor: 'transparent',
            textAlign: 'left'
        };
    }

    function setZoom(level) {
        BuilderState.zoom = Math.max(50, Math.min(200, level));
        $('#zoom-level').val(BuilderState.zoom + '%');
        $('.pdf-canvas').css('transform', 'scale(' + (BuilderState.zoom / 100) + ')');
    }

    function alignElement(alignment) {
        if (!BuilderState.selectedElement) return;
        const el = BuilderState.selectedElement;
        const canvas = $('.canvas-page');
        const canvasWidth = canvas.width();

        if (alignment === 'left') {
            el.x = 10;
        } else if (alignment === 'center') {
            el.x = (canvasWidth - el.width) / 2;
        } else if (alignment === 'right') {
            el.x = canvasWidth - el.width - 10;
        }

        $('#' + el.id).css('left', el.x + 'px');
        updatePropertiesPanel();
        addToHistory();
    }

    function addPage() {
        const pageNum = $('.canvas-page').length + 1;
        const $page = $('<div>', {
            class: 'canvas-page',
            'data-page': pageNum
        });
        $('.pdf-canvas').append($page);
        setupDragAndDrop();
    }

    function updatePageSize() {
        // Update canvas dimensions based on page size
    }

    function updatePageOrientation() {
        $('.canvas-page').toggleClass('landscape', BuilderState.settings.orientation === 'landscape');
    }

    function addToHistory() {
        BuilderState.history = BuilderState.history.slice(0, BuilderState.historyIndex + 1);
        BuilderState.history.push(JSON.stringify(BuilderState.elements));
        BuilderState.historyIndex++;
    }

    function undo() {
        if (BuilderState.historyIndex > 0) {
            BuilderState.historyIndex--;
            loadHistory();
        }
    }

    function redo() {
        if (BuilderState.historyIndex < BuilderState.history.length - 1) {
            BuilderState.historyIndex++;
            loadHistory();
        }
    }

    function loadHistory() {
        BuilderState.elements = JSON.parse(BuilderState.history[BuilderState.historyIndex]);
        $('.canvas-element').remove();
        BuilderState.elements.forEach(renderElement);
    }

    function loadTemplate(templateData) {
        try {
            const data = JSON.parse(templateData.template_data);
            BuilderState.elements = data.elements || [];
            BuilderState.settings = data.settings || BuilderState.settings;

            BuilderState.elements.forEach(renderElement);
        } catch (e) {
            console.error('Failed to load template:', e);
        }
    }

    // Initialize builder
    init();
});