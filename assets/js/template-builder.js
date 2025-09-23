/* ==========================================================================
   Reverse2PDF Pro - Visual Template Builder
   ========================================================================== */

(function($) {
    'use strict';

    // Template Builder namespace
    window.Reverse2PDF = window.Reverse2PDF || {};
    const R2PDF = window.Reverse2PDF;

    R2PDF.Builder = {
        
        currentTemplate: null,
        selectedElement: null,
        canvas: null,
        zoom: 1,
        grid: 10,
        
        init: function() {
            this.canvas = $('#pdf-canvas');
            this.bindEvents();
            this.initDragDrop();
            this.initResizable();
            this.loadTemplate();
            
            console.log('🎨 Template Builder initialized');
        },

        bindEvents: function() {
            // Canvas events
            $(document).on('click', '.pdf-element', this.selectElement.bind(this));
            $(document).on('click', '.pdf-canvas', this.deselectAll.bind(this));
            
            // Property changes
            $(document).on('change', '.property-input', this.updateElementProperty.bind(this));
            
            // Toolbar actions
            $(document).on('click', '#add-page', this.addPage.bind(this));
            $(document).on('click', '#delete-element', this.deleteElement.bind(this));
            $(document).on('click', '#duplicate-element', this.duplicateElement.bind(this));
            
            // Zoom controls
            $(document).on('click', '#zoom-in', this.zoomIn.bind(this));
            $(document).on('click', '#zoom-out', this.zoomOut.bind(this));
            $(document).on('click', '#zoom-fit', this.zoomFit.bind(this));
            
            // Keyboard shortcuts
            $(document).keydown(this.handleKeyboard.bind(this));
        },

        initDragDrop: function() {
            // Make elements draggable
            $('.element-item').draggable({
                helper: 'clone',
                appendTo: 'body',
                zIndex: 1000,
                cursor: 'grabbing',
                start: function(event, ui) {
                    ui.helper.addClass('dragging');
                }
            });

            // Make canvas droppable
            $('.pdf-canvas .page').droppable({
                accept: '.element-item',
                tolerance: 'pointer',
                over: function(event, ui) {
                    $(this).addClass('drop-hover');
                },
                out: function(event, ui) {
                    $(this).removeClass('drop-hover');
                },
                drop: this.handleElementDrop.bind(this)
            });
        },

        initResizable: function() {
            // Make elements resizable
            $(document).on('mouseenter', '.pdf-element', function() {
                if (!$(this).hasClass('ui-resizable')) {
                    $(this).resizable({
                        containment: 'parent',
                        handles: 'n, e, s, w, ne, nw, se, sw',
                        grid: [R2PDF.Builder.grid, R2PDF.Builder.grid],
                        resize: function(event, ui) {
                            R2PDF.Builder.updateElementData($(this));
                        }
                    });
                }
            });
        },

        loadTemplate: function() {
            const templateData = $('#template-data').val();
            if (templateData) {
                try {
                    this.currentTemplate = JSON.parse(templateData);
                    this.renderTemplate();
                } catch (e) {
                    console.warn('Invalid template data:', e);
                    this.currentTemplate = this.getDefaultTemplate();
                }
            } else {
                this.currentTemplate = this.getDefaultTemplate();
            }
        },

        getDefaultTemplate: function() {
            return {
                pages: [{
                    id: 'page_1',
                    width: 595,
                    height: 842,
                    elements: []
                }],
                settings: {
                    paperSize: 'A4',
                    orientation: 'portrait',
                    margins: { top: 20, right: 15, bottom: 20, left: 15 }
                }
            };
        },

        renderTemplate: function() {
            if (!this.currentTemplate || !this.currentTemplate.pages) return;

            this.canvas.empty();
            
            this.currentTemplate.pages.forEach((page, index) => {
                const $page = this.createPageElement(page, index);
                this.canvas.append($page);
                
                // Render elements
                if (page.elements) {
                    page.elements.forEach(element => {
                        const $element = this.createElement(element);
                        $page.append($element);
                    });
                }
            });
        },

        createPageElement: function(page, index) {
            return $(`
                <div class="page" data-page="${index + 1}" style="width: ${page.width}px; height: ${page.height}px;">
                    <div class="page-overlay">
                        <div class="page-number">Page ${index + 1}</div>
                    </div>
                </div>
            `);
        },

        createElement: function(element) {
            const $element = $(`
                <div class="pdf-element" 
                     data-id="${element.id}" 
                     data-type="${element.type}"
                     style="position: absolute; 
                            left: ${element.x}px; 
                            top: ${element.y}px; 
                            width: ${element.width}px; 
                            height: ${element.height}px;
                            ${this.getElementStyles(element)}">
                    ${this.getElementContent(element)}
                    <div class="element-controls">
                        <button type="button" class="control-btn duplicate-btn" title="Duplicate">⧉</button>
                        <button type="button" class="control-btn delete-btn" title="Delete">✕</button>
                    </div>
                </div>
            `);
            
            this.makeElementDraggable($element);
            return $element;
        },

        getElementStyles: function(element) {
            let styles = '';
            
            if (element.fontSize) styles += `font-size: ${element.fontSize}px; `;
            if (element.fontWeight) styles += `font-weight: ${element.fontWeight}; `;
            if (element.color) styles += `color: ${element.color}; `;
            if (element.backgroundColor) styles += `background-color: ${element.backgroundColor}; `;
            if (element.textAlign) styles += `text-align: ${element.textAlign}; `;
            if (element.borderWidth) styles += `border: ${element.borderWidth}px solid ${element.borderColor || '#000'}; `;
            if (element.borderRadius) styles += `border-radius: ${element.borderRadius}px; `;
            
            return styles;
        },

        getElementContent: function(element) {
            switch (element.type) {
                case 'text':
                    return `<div class="element-text">${element.content || 'Text Element'}</div>`;
                case 'image':
                    return `<img src="${element.src || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3QgeD0iMyIgeT0iMyIgd2lkdGg9IjE4IiBoZWlnaHQ9IjE4IiByeD0iMiIgc3Ryb2tlPSIjOWNhM2FmIiBzdHJva2Utd2lkdGg9IjIiLz4KPGNpcmNsZSBjeD0iOC41IiBjeT0iOC41IiByPSIxLjUiIGZpbGw9IiM5Y2EzYWYiLz4KPHBhdGggZD0ibTkgMTIgMyAzIDYtNiIgc3Ryb2tlPSIjOWNhM2FmIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8L3N2Zz4K'}" style="width: 100%; height: 100%; object-fit: contain;" alt="Image">`;
                case 'line':
                    return '<div class="element-line" style="width: 100%; height: 1px; background: #000;"></div>';
                case 'rectangle':
                    return `<div class="element-rectangle" style="width: 100%; height: 100%; border: 1px solid #000;">${element.content || ''}</div>`;
                case 'qr-code':
                    return `<div class="element-qr" style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 12px;">QR: ${element.content || 'data'}</div>`;
                case 'barcode':
                    return `<div class="element-barcode" style="width: 100%; height: 100%; background: repeating-linear-gradient(90deg, #000 0px, #000 2px, #fff 2px, #fff 4px); display: flex; align-items: end; justify-content: center; font-size: 10px;">${element.content || '123456'}</div>`;
                default:
                    return `<div class="element-default">${element.content || element.type}</div>`;
            }
        },

        makeElementDraggable: function($element) {
            $element.draggable({
                containment: 'parent',
                grid: [this.grid, this.grid],
                drag: (event, ui) => {
                    this.updateElementData($element);
                },
                stop: (event, ui) => {
                    this.updateElementData($element);
                }
            });
        },

        handleElementDrop: function(event, ui) {
            const elementType = ui.draggable.data('type');
            const $page = $(event.target);
            const pageOffset = $page.offset();
            
            const dropPos = {
                x: Math.round((event.pageX - pageOffset.left) / this.grid) * this.grid,
                y: Math.round((event.pageY - pageOffset.top) / this.grid) * this.grid
            };
            
            this.addElement(elementType, dropPos, $page);
            $page.removeClass('drop-hover');
        },

        addElement: function(type, position, $page) {
            const elementId = 'element_' + Date.now();
            const element = {
                id: elementId,
                type: type,
                x: position.x,
                y: position.y,
                width: this.getDefaultWidth(type),
                height: this.getDefaultHeight(type),
                content: this.getDefaultContent(type)
            };
            
            // Add to template data
            const pageIndex = parseInt($page.data('page')) - 1;
            if (!this.currentTemplate.pages[pageIndex].elements) {
                this.currentTemplate.pages[pageIndex].elements = [];
            }
            this.currentTemplate.pages[pageIndex].elements.push(element);
            
            // Create and add element to DOM
            const $element = this.createElement(element);
            $page.append($element);
            
            // Select the new element
            this.selectElement.call($element[0]);
            
            // Save template
            this.saveTemplate();
            
            R2PDF.Admin.showNotification(`Added ${type} element`, 'success', 2000);
        },

        getDefaultWidth: function(type) {
            const widths = {
                text: 200,
                image: 150,
                line: 200,
                rectangle: 150,
                'qr-code': 100,
                barcode: 200
            };
            return widths[type] || 150;
        },

        getDefaultHeight: function(type) {
            const heights = {
                text: 30,
                image: 100,
                line: 5,
                rectangle: 100,
                'qr-code': 100,
                barcode: 50
            };
            return heights[type] || 30;
        },

        getDefaultContent: function(type) {
            const contents = {
                text: 'Text Element',
                image: '',
                line: '',
                rectangle: '',
                'qr-code': 'https://example.com',
                barcode: '123456789'
            };
            return contents[type] || '';
        },

        selectElement: function() {
            // Remove previous selection
            $('.pdf-element').removeClass('selected');
            
            // Select current element
            const $element = $(this);
            $element.addClass('selected');
            R2PDF.Builder.selectedElement = $element;
            
            // Update properties panel
            R2PDF.Builder.updatePropertiesPanel($element);
        },

        deselectAll: function(e) {
            if ($(e.target).hasClass('pdf-canvas') || $(e.target).hasClass('page')) {
                $('.pdf-element').removeClass('selected');
                R2PDF.Builder.selectedElement = null;
                R2PDF.Builder.clearPropertiesPanel();
            }
        },

        updatePropertiesPanel: function($element) {
            const elementData = this.getElementData($element);
            const propertiesHtml = this.generatePropertiesHtml(elementData);
            $('#element-properties').html(propertiesHtml);
        },

        clearPropertiesPanel: function() {
            $('#element-properties').html('<p>Select an element to edit its properties.</p>');
        },

        getElementData: function($element) {
            return {
                id: $element.data('id'),
                type: $element.data('type'),
                x: parseInt($element.css('left')),
                y: parseInt($element.css('top')),
                width: $element.width(),
                height: $element.height(),
                content: $element.find('.element-text').text() || $element.data('content') || '',
                fontSize: parseInt($element.css('font-size')) || 12,
                fontWeight: $element.css('font-weight') || 'normal',
                color: $element.css('color') || '#000000',
                backgroundColor: $element.css('background-color') || 'transparent',
                textAlign: $element.css('text-align') || 'left'
            };
        },

        generatePropertiesHtml: function(data) {
            let html = `<h5>Element Properties</h5>`;
            
            // Basic properties
            html += `
                <div class="property-group">
                    <h6>Position & Size</h6>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label>X Position</label>
                            <input type="number" class="property-input" data-property="x" value="${data.x}">
                        </div>
                        <div>
                            <label>Y Position</label>
                            <input type="number" class="property-input" data-property="y" value="${data.y}">
                        </div>
                        <div>
                            <label>Width</label>
                            <input type="number" class="property-input" data-property="width" value="${data.width}">
                        </div>
                        <div>
                            <label>Height</label>
                            <input type="number" class="property-input" data-property="height" value="${data.height}">
                        </div>
                    </div>
                </div>
            `;
            
            // Content property
            if (data.type === 'text' || data.type === 'qr-code' || data.type === 'barcode') {
                html += `
                    <div class="property-group">
                        <h6>Content</h6>
                        <textarea class="property-input" data-property="content" rows="3">${data.content}</textarea>
                    </div>
                `;
            }
            
            // Typography properties
            if (data.type === 'text') {
                html += `
                    <div class="property-group">
                        <h6>Typography</h6>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label>Font Size</label>
                                <input type="number" class="property-input" data-property="fontSize" value="${data.fontSize}">
                            </div>
                            <div>
                                <label>Font Weight</label>
                                <select class="property-input" data-property="fontWeight">
                                    <option value="normal" ${data.fontWeight === 'normal' ? 'selected' : ''}>Normal</option>
                                    <option value="bold" ${data.fontWeight === 'bold' ? 'selected' : ''}>Bold</option>
                                    <option value="600" ${data.fontWeight === '600' ? 'selected' : ''}>Semi Bold</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top: 10px;">
                            <label>Text Alignment</label>
                            <select class="property-input" data-property="textAlign">
                                <option value="left" ${data.textAlign === 'left' ? 'selected' : ''}>Left</option>
                                <option value="center" ${data.textAlign === 'center' ? 'selected' : ''}>Center</option>
                                <option value="right" ${data.textAlign === 'right' ? 'selected' : ''}>Right</option>
                                <option value="justify" ${data.textAlign === 'justify' ? 'selected' : ''}>Justify</option>
                            </select>
                        </div>
                    </div>
                `;
            }
            
            // Color properties
            html += `
                <div class="property-group">
                    <h6>Colors</h6>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label>Text Color</label>
                            <input type="color" class="property-input" data-property="color" value="${this.rgbToHex(data.color)}">
                        </div>
                        <div>
                            <label>Background</label>
                            <input type="color" class="property-input" data-property="backgroundColor" value="${this.rgbToHex(data.backgroundColor)}">
                        </div>
                    </div>
                </div>
            `;
            
            return html;
        },

        updateElementProperty: function() {
            const $input = $(this);
            const property = $input.data('property');
            const value = $input.val();
            
            if (!R2PDF.Builder.selectedElement) return;
            
            const $element = R2PDF.Builder.selectedElement;
            
            // Update element style/position
            switch (property) {
                case 'x':
                    $element.css('left', value + 'px');
                    break;
                case 'y':
                    $element.css('top', value + 'px');
                    break;
                case 'width':
                    $element.width(value);
                    break;
                case 'height':
                    $element.height(value);
                    break;
                case 'content':
                    $element.find('.element-text').text(value);
                    $element.data('content', value);
                    break;
                case 'fontSize':
                    $element.css('font-size', value + 'px');
                    break;
                case 'fontWeight':
                    $element.css('font-weight', value);
                    break;
                case 'color':
                    $element.css('color', value);
                    break;
                case 'backgroundColor':
                    $element.css('background-color', value);
                    break;
                case 'textAlign':
                    $element.css('text-align', value);
                    break;
            }
            
            // Update template data
            R2PDF.Builder.updateElementData($element);
            R2PDF.Builder.saveTemplate();
        },

        updateElementData: function($element) {
            const elementId = $element.data('id');
            const pageIndex = $element.closest('.page').data('page') - 1;
            
            if (!this.currentTemplate.pages[pageIndex].elements) return;
            
            const elementIndex = this.currentTemplate.pages[pageIndex].elements.findIndex(el => el.id === elementId);
            if (elementIndex === -1) return;
            
            // Update element data
            const updatedData = {
                id: elementId,
                type: $element.data('type'),
                x: parseInt($element.css('left')),
                y: parseInt($element.css('top')),
                width: $element.width(),
                height: $element.height(),
                content: $element.find('.element-text').text() || $element.data('content') || '',
                fontSize: parseInt($element.css('font-size')) || 12,
                fontWeight: $element.css('font-weight') || 'normal',
                color: $element.css('color') || '#000000',
                backgroundColor: $element.css('background-color') || 'transparent',
                textAlign: $element.css('text-align') || 'left'
            };
            
            this.currentTemplate.pages[pageIndex].elements[elementIndex] = updatedData;
        },

        saveTemplate: function() {
            $('#template-data').val(JSON.stringify(this.currentTemplate));
        },

        // Utility functions
        rgbToHex: function(rgb) {
            if (!rgb || rgb === 'transparent') return '#000000';
            
            const result = rgb.match(/\d+/g);
            if (result && result.length >= 3) {
                return "#" + ((1 << 24) + (parseInt(result[0]) << 16) + (parseInt(result[1]) << 8) + parseInt(result[2])).toString(16).slice(1);
            }
            return rgb;
        },

        // Zoom functions
        zoomIn: function() {
            this.zoom = Math.min(this.zoom * 1.2, 3);
            this.applyZoom();
        },

        zoomOut: function() {
            this.zoom = Math.max(this.zoom / 1.2, 0.3);
            this.applyZoom();
        },

        zoomFit: function() {
            const containerWidth = $('.builder-canvas').width();
            const pageWidth = 595;
            this.zoom = Math.min(containerWidth / pageWidth * 0.9, 1);
            this.applyZoom();
        },

        applyZoom: function() {
            this.canvas.css('transform', `scale(${this.zoom})`);
            $('.zoom-level').text(Math.round(this.zoom * 100) + '%');
        },

        // Keyboard shortcuts
        handleKeyboard: function(e) {
            if (!this.selectedElement) return;
            
            const step = e.shiftKey ? 10 : 1;
            
            switch (e.keyCode) {
                case 37: // Left arrow
                    e.preventDefault();
                    this.moveElement(this.selectedElement, -step, 0);
                    break;
                case 38: // Up arrow
                    e.preventDefault();
                    this.moveElement(this.selectedElement, 0, -step);
                    break;
                case 39: // Right arrow
                    e.preventDefault();
                    this.moveElement(this.selectedElement, step, 0);
                    break;
                case 40: // Down arrow
                    e.preventDefault();
                    this.moveElement(this.selectedElement, 0, step);
                    break;
                case 46: // Delete
                    e.preventDefault();
                    this.deleteElement();
                    break;
            }
        },

        moveElement: function($element, deltaX, deltaY) {
            const currentX = parseInt($element.css('left'));
            const currentY = parseInt($element.css('top'));
            
            $element.css({
                left: Math.max(0, currentX + deltaX) + 'px',
                top: Math.max(0, currentY + deltaY) + 'px'
            });
            
            this.updateElementData($element);
            this.updatePropertiesPanel($element);
            this.saveTemplate();
        },

        deleteElement: function() {
            if (!this.selectedElement) return;
            
            if (confirm('Are you sure you want to delete this element?')) {
                const elementId = this.selectedElement.data('id');
                const pageIndex = this.selectedElement.closest('.page').data('page') - 1;
                
                // Remove from template data
                this.currentTemplate.pages[pageIndex].elements = 
                    this.currentTemplate.pages[pageIndex].elements.filter(el => el.id !== elementId);
                
                // Remove from DOM
                this.selectedElement.remove();
                this.selectedElement = null;
                this.clearPropertiesPanel();
                this.saveTemplate();
                
                R2PDF.Admin.showNotification('Element deleted', 'success', 2000);
            }
        },

        duplicateElement: function() {
            if (!this.selectedElement) return;
            
            const elementData = this.getElementData(this.selectedElement);
            elementData.id = 'element_' + Date.now();
            elementData.x += 20;
            elementData.y += 20;
            
            const pageIndex = this.selectedElement.closest('.page').data('page') - 1;
            this.currentTemplate.pages[pageIndex].elements.push(elementData);
            
            const $newElement = this.createElement(elementData);
            this.selectedElement.closest('.page').append($newElement);
            
            this.selectElement.call($newElement[0]);
            this.saveTemplate();
            
            R2PDF.Admin.showNotification('Element duplicated', 'success', 2000);
        },

        addPage: function() {
            const newPageId = 'page_' + Date.now();
            const newPage = {
                id: newPageId,
                width: 595,
                height: 842,
                elements: []
            };
            
            this.currentTemplate.pages.push(newPage);
            
            const $newPage = this.createPageElement(newPage, this.currentTemplate.pages.length - 1);
            this.canvas.append($newPage);
            
            this.saveTemplate();
            R2PDF.Admin.showNotification('New page added', 'success', 2000);
        }
    };

    // Initialize when document ready
    $(document).ready(function() {
        if ($('.reverse2pdf-builder').length > 0) {
            R2PDF.Builder.init();
        }
    });

    // Expose to global scope
    window.Reverse2PDF = R2PDF;

})(jQuery);

// Additional CSS for template builder
const builderCSS = `
<style>
.pdf-element {
    border: 2px dashed transparent;
    cursor: move;
    min-width: 10px;
    min-height: 10px;
}

.pdf-element:hover {
    border-color: rgba(99, 102, 241, 0.5);
}

.pdf-element.selected {
    border-color: #6366f1;
    box-shadow: 0 0 0 1px #6366f1;
}

.pdf-element .element-controls {
    position: absolute;
    top: -30px;
    right: 0;
    display: none;
    gap: 4px;
}

.pdf-element.selected .element-controls {
    display: flex;
}

.control-btn {
    width: 24px;
    height: 24px;
    border: none;
    background: #6366f1;
    color: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.control-btn:hover {
    background: #4f46e5;
}

.page {
    position: relative;
    background: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    border: 1px solid #e5e7eb;
}

.page.drop-hover {
    border-color: #6366f1;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

.page-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.page-number {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.element-item.ui-draggable-dragging {
    transform: scale(1.05);
    opacity: 0.8;
    z-index: 1000;
}

.property-group {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.property-group:last-child {
    border-bottom: none;
}

.property-group h6 {
    margin: 0 0 10px 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.property-group label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    margin-bottom: 4px;
}

.zoom-level {
    font-weight: 600;
    color: #374151;
}

@media (max-width: 1024px) {
    .builder-workspace {
        grid-template-columns: 1fr;
        grid-template-rows: auto 1fr;
    }
    
    .builder-sidebar {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .pdf-element .element-controls {
        top: -35px;
    }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', builderCSS);
