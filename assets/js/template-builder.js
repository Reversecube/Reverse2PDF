/**
 * Reverse2PDF Template Builder – stable implementation
 * Features:
 * - Multi-page (add / select / delete)
 * - Per-page elements
 * - Drag & drop, resize
 * - Properties panel
 * - Zoom, align, undo/redo
 * - Save / load skeleton
 */

jQuery(document).ready(function ($) {
    'use strict';

    console.log('Reverse2PDF Builder: init');

    // ---------------------------------------------------------------------
    // STATE
    // ---------------------------------------------------------------------
    const BuilderState = {
        templateId: 0,
        currentPage: 1,
        pages: [{ id: 1, name: 'Page 1', elements: [] }],
        elements: [],
        selectedElement: null,
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

    // ---------------------------------------------------------------------
    // INIT
    // ---------------------------------------------------------------------
    function init() {
        const $wrap = $('#pdf-canvas');
        if (!$wrap.length) {
            console.warn('Reverse2PDF: #pdf-canvas not found');
            return;
        }

        BuilderState.templateId = $wrap.data('template-id') || 0;
        const templateData = $wrap.data('template');

        buildPagesDOM();

        if (templateData && templateData.template_data) {
            loadTemplate(templateData);
        }

        setupTabs();
        setupEvents();
        setupDragAndDrop();

        renderPagesList();
        updateCanvasForPage(BuilderState.currentPage);
        showEmptyProperties();
        addToHistory();

        console.log('Reverse2PDF Builder: ready');
    }

    // Build .canvas-page DOM from BuilderState.pages
    function buildPagesDOM() {
        const $pdfCanvas = $('.pdf-canvas');
        if (!$pdfCanvas.length) {
            console.warn('Reverse2PDF: .pdf-canvas not found');
            return;
        }

        $pdfCanvas.empty();

        BuilderState.pages.forEach(function (p) {
            const $page = $('<div>', {
                class: 'canvas-page',
                'data-page': p.id
            });
            $pdfCanvas.append($page);
        });
    }

    // ---------------------------------------------------------------------
    // TABS
    // ---------------------------------------------------------------------
    function setupTabs() {
        $('.r2pdf-tab-btn').off('click.r2pdf').on('click.r2pdf', function () {
            const tab = $(this).data('tab');

            $('.r2pdf-tab-btn').removeClass('active');
            $(this).addClass('active');

            $('.r2pdf-tab-content').removeClass('active');
            $('.r2pdf-tab-content[data-tab="' + tab + '"]').addClass('active');
        });
    }

    // ---------------------------------------------------------------------
    // GLOBAL EVENTS
    // ---------------------------------------------------------------------
    function setupEvents() {
        // Save / Export / Preview
        $('#save-template-btn').off('click.r2pdf').on('click.r2pdf', saveTemplate);
        $('#export-pdf-btn').off('click.r2pdf').on('click.r2pdf', exportPDF);
        $('#preview-pdf-btn').off('click.r2pdf').on('click.r2pdf', previewPDF);

        // Undo / Redo
        $('#undo-btn').off('click.r2pdf').on('click.r2pdf', undo);
        $('#redo-btn').off('click.r2pdf').on('click.r2pdf', redo);

        // Zoom
        $('#zoom-in').off('click.r2pdf').on('click.r2pdf', function () {
            setZoom(BuilderState.zoom + 10);
        });
        $('#zoom-out').off('click.r2pdf').on('click.r2pdf', function () {
            setZoom(BuilderState.zoom - 10);
        });
        $('#zoom-fit').off('click.r2pdf').on('click.r2pdf', function () {
            setZoom(100);
        });

        // Align
        $('#align-left').off('click.r2pdf').on('click.r2pdf', function () {
            alignElement('left');
        });
        $('#align-center').off('click.r2pdf').on('click.r2pdf', function () {
            alignElement('center');
        });
        $('#align-right').off('click.r2pdf').on('click.r2pdf', function () {
            alignElement('right');
        });

        // Toolbar element operations
        $('#delete-element').off('click.r2pdf').on('click.r2pdf', deleteSelectedElement);
        $('#duplicate-element').off('click.r2pdf').on('click.r2pdf', duplicateSelectedElement);

        // Page button
        $('#add-page-btn').off('click.r2pdf').on('click.r2pdf', function (e) {
            e.preventDefault();
            addPage();
        });

        // Settings
        $('#page-size').off('change.r2pdf').on('change.r2pdf', function () {
            BuilderState.settings.pageSize = $(this).val();
            renderPagesList();
        });
        $('#orientation').off('change.r2pdf').on('change.r2pdf', function () {
            BuilderState.settings.orientation = $(this).val();
            updateOrientation();
            renderPagesList();
        });

        // Click outside to deselect
        $(document).off('click.r2pdf').on('click.r2pdf', function (e) {
            if (!$(e.target).closest('.canvas-element').length &&
                !$(e.target).closest('#properties-panel').length) {
                deselectElement();
            }
        });
    }

    // ---------------------------------------------------------------------
    // PAGES
    // ---------------------------------------------------------------------
    function renderPagesList() {
        const $list = $('.pages-list');
        if (!$list.length) return;

        $list.empty();
        const canDelete = BuilderState.pages.length > 1;

        BuilderState.pages.forEach(function (page, idx) {
            const active = page.id === BuilderState.currentPage ? 'active' : '';
            const disabledAttr = canDelete ? '' : 'disabled';
            const title = canDelete ? 'Delete page' : 'Cannot delete last page';

            const $item = $(`
                <div class="page-item ${active}" data-page-id="${page.id}">
                    <div class="page-item-info">
                        <div class="page-item-name">Page ${idx + 1}</div>
                        <div class="page-item-size">
                            ${BuilderState.settings.pageSize} - ${BuilderState.settings.orientation}
                        </div>
                    </div>
                    <button class="page-delete-btn" data-page-id="${page.id}" ${disabledAttr} title="${title}">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            `);

            // select
            $item.on('click', function (e) {
                if ($(e.target).closest('.page-delete-btn').length) return;
                selectPage(page.id);
            });

            // delete
            $item.find('.page-delete-btn').on('click', function (e) {
                e.stopPropagation();
                deletePage(page.id);
            });

            $list.append($item);
        });
    }

    function addPage() {
        const nextId = BuilderState.pages.length
            ? Math.max.apply(null, BuilderState.pages.map(p => p.id)) + 1
            : 1;

        const page = { id: nextId, name: 'Page ' + (BuilderState.pages.length + 1), elements: [] };
        BuilderState.pages.push(page);

        const $page = $('<div>', {
            class: 'canvas-page',
            'data-page': page.id
        });
        $('.pdf-canvas').append($page);

        setupDragAndDrop();

        BuilderState.currentPage = page.id;
        renderPagesList();
        updateCanvasForPage(page.id);
        addToHistory();

        console.log('Page added:', page.id);
    }

    function selectPage(pageId) {
        BuilderState.currentPage = pageId;

        $('.page-item').removeClass('active');
        $('.page-item[data-page-id="' + pageId + '"]').addClass('active');

        updateCanvasForPage(pageId);
        deselectElement();

        console.log('Page selected:', pageId);
    }

    function deletePage(pageId) {
        if (BuilderState.pages.length <= 1) {
            alert('Cannot delete the last page');
            return;
        }

        if (!confirm('Delete this page and all its elements?')) {
            return;
        }

        const page = BuilderState.pages.find(p => p.id === pageId);
        if (page && page.elements.length) {
            page.elements.forEach(function (id) {
                $('#' + id).remove();
            });
            BuilderState.elements = BuilderState.elements.filter(function (el) {
                return el.pageId !== pageId;
            });
        }

        $('.canvas-page[data-page="' + pageId + '"]').remove();

        const idx = BuilderState.pages.findIndex(p => p.id === pageId);
        BuilderState.pages.splice(idx, 1);

        const newPage = BuilderState.pages[Math.max(0, idx - 1)];
        BuilderState.currentPage = newPage.id;

        renderPagesList();
        updateCanvasForPage(newPage.id);
        addToHistory();

        console.log('Page deleted:', pageId);
    }

    function updateCanvasForPage(pageId) {
        $('.canvas-page').hide();
        $('.canvas-page[data-page="' + pageId + '"]').show();

        $('.canvas-element').each(function () {
            const elPageId = $(this).data('page-id');
            $(this).toggle(elPageId === pageId);
        });

        const page = BuilderState.pages.find(p => p.id === pageId);
        if (!page || !page.elements.length) {
            ensureEmptyState();
        } else {
            $('.canvas-empty-state').remove();
        }
    }

    function ensureEmptyState() {
        const $page = $('.canvas-page[data-page="' + BuilderState.currentPage + '"]');
        if (!$page.length) return;

        if (!$page.find('.canvas-empty-state').length) {
            $page.append(
                '<div class="canvas-empty-state">' +
                '<span class="dashicons dashicons-admin-page"></span>' +
                '<h3>Start Building Your PDF</h3>' +
                '<p>Drag elements from left sidebar</p>' +
                '</div>'
            );
        }
    }

    function updateOrientation() {
        $('.canvas-page').toggleClass('landscape', BuilderState.settings.orientation === 'landscape');
    }

    // ---------------------------------------------------------------------
    // DRAG & DROP
    // ---------------------------------------------------------------------
    function setupDragAndDrop() {
        // palette
        $('.element-item')
            .attr('draggable', true)
            .off('dragstart.r2pdf dragend.r2pdf')
            .on('dragstart.r2pdf', function (e) {
                const type = $(this).data('type');
                e.originalEvent.dataTransfer.setData('elementType', type);
                $(this).addClass('dragging');
            })
            .on('dragend.r2pdf', function () {
                $(this).removeClass('dragging');
            });

        // canvas
        $('.canvas-page')
            .off('dragover.r2pdf drop.r2pdf')
            .on('dragover.r2pdf', function (e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'copy';
            })
            .on('drop.r2pdf', function (e) {
                e.preventDefault();
                const type = e.originalEvent.dataTransfer.getData('elementType');
                if (!type) return;

                const offset = $(this).offset();
                const x = e.pageX - offset.left;
                const y = e.pageY - offset.top;

                addElement(type, x, y);
            });
    }

    function addElement(type, x, y) {
        const elementId = 'element-' + Date.now();
        const element = {
            id: elementId,
            type: type,
            pageId: BuilderState.currentPage,
            x: Math.round(x),
            y: Math.round(y),
            width: getDefaultWidth(type),
            height: getDefaultHeight(type),
            content: getDefaultContent(type),
            styles: getDefaultStyles(type)
        };

        BuilderState.elements.push(element);

        const page = BuilderState.pages.find(p => p.id === BuilderState.currentPage);
        if (page) {
            page.elements.push(elementId);
        }

        renderElement(element);
        selectElement(elementId);
        addToHistory();
        $('.canvas-empty-state').remove();
    }

    function renderElement(element) {
        const $page = $('.canvas-page[data-page="' + element.pageId + '"]');
        if (!$page.length) return;

        const $el = $('<div>', {
            id: element.id,
            class: 'canvas-element',
            'data-type': element.type,
            'data-page-id': element.pageId,
            css: {
                position: 'absolute',
                left: element.x + 'px',
                top: element.y + 'px',
                width: element.width + 'px',
                height: element.height + 'px',
                fontSize: element.styles.fontSize || '14px',
                fontFamily: element.styles.fontFamily || 'Arial',
                color: element.styles.color || '#000000',
                backgroundColor: element.styles.backgroundColor || 'transparent',
                textAlign: element.styles.textAlign || 'left',
                fontWeight: element.styles.fontWeight || 'normal'
            }
        });

        // Content based on type
        if (['text', 'heading', 'label', 'paragraph'].includes(element.type)) {
            $el.html(element.content || element.type);
        } else if (element.type === 'input') {
            $el.html('<input type="text" placeholder="' + (element.placeholder || 'Input field') + '" style="width:100%;padding:4px;border:1px solid #ccc;box-sizing:border-box;">');
        } else if (element.type === 'checkbox') {
            $el.html('<input type="checkbox"> ' + (element.label || 'Checkbox'));
        } else if (element.type === 'radio') {
            $el.html('<input type="radio"> ' + (element.label || 'Radio'));
        } else if (element.type === 'textarea') {
            $el.html('<textarea placeholder="' + (element.placeholder || 'Textarea') + '" style="width:100%;height:100%;padding:4px;border:1px solid #ccc;box-sizing:border-box;resize:none;"></textarea>');
        } else if (element.type === 'select') {
            $el.html('<select style="width:100%;padding:4px;"><option>Select...</option></select>');
        } else if (element.type === 'signature') {
            $el.html('<div style="border:2px dashed #ccc;padding:8px;text-align:center;height:100%;display:flex;align-items:center;justify-content:center;">Signature</div>');
        } else if (element.type === 'image') {
            if (element.imageSrc) {
                $el.html('<img src="' + element.imageSrc + '" style="width:100%;height:100%;object-fit:' + (element.styles.objectFit || 'contain') + ';">');
            } else {
                $el.html('<div style="border:1px solid #ccc;background:#f5f5f5;height:100%;display:flex;align-items:center;justify-content:center;"><span class="dashicons dashicons-format-image"></span></div>');
            }
        } else if (element.type === 'logo') {
            $el.html('<div style="border:1px solid #ccc;background:#f5f5f5;height:100%;display:flex;align-items:center;justify-content:center;"><span class="dashicons dashicons-wordpress"></span></div>');
        } else {
            $el.html(element.type);
        }

        // Add element to page
        $page.append($el);

        // Make DRAGGABLE with jQuery UI
        if ($.fn.draggable) {
            $el.draggable({
                containment: $page,
                handle: $el, // entire element is draggable
                drag: function (event, ui) {
                    element.x = ui.position.left;
                    element.y = ui.position.top;
                    updatePropertiesPanel();
                },
                stop: function () {
                    addToHistory();
                }
            });
        }

        // Make RESIZABLE with jQuery UI - FIXED VERSION
        if ($.fn.resizable) {
            $el.resizable({
                handles: 'n, s, e, w, ne, nw, se, sw',
                containment: $page,
                minWidth: 30,
                minHeight: 20,
                resize: function (event, ui) {
                    element.width = ui.size.width;
                    element.height = ui.size.height;
                    element.x = ui.position.left;
                    element.y = ui.position.top;
                    updatePropertiesPanel();
                },
                stop: function () {
                    addToHistory();
                }
            });
        }

        // Click to select
        $el.on('click', function (e) {
            e.stopPropagation();
            selectElement(element.id);
        });

        // Hide if not on current page
        if (element.pageId !== BuilderState.currentPage) {
            $el.hide();
        }
    }


    // ---------------------------------------------------------------------
    // SELECTION + PROPERTIES
    // ---------------------------------------------------------------------
    function selectElement(id) {
        $('.canvas-element').removeClass('selected');
        $('#' + id).addClass('selected');

        BuilderState.selectedElement = BuilderState.elements.find(function (el) {
            return el.id === id;
        });

        updatePropertiesPanel();
    }

    function deselectElement() {
        $('.canvas-element').removeClass('selected');
        BuilderState.selectedElement = null;
        showEmptyProperties();
    }

    function showEmptyProperties() {
        $('#properties-panel').html(
            '<div class="properties-empty">' +
            '<span class="dashicons dashicons-admin-generic"></span>' +
            '<p>Select an element to edit properties</p>' +
            '</div>'
        );
    }

    // Replace the updatePropertiesPanel function in template-builder.js

function updatePropertiesPanel() {
    const el = BuilderState.selectedElement;
    if (!el) {
        showEmptyProperties();
        return;
    }

    let html = '';

    // Common: Element Type
    html += `
        <div class="property-group">
            <label class="property-label">Type</label>
            <div style="padding:8px;background:#f9fafb;border-radius:4px;font-weight:600;text-transform:uppercase;font-size:11px;color:#667eea;">
                ${el.type}
            </div>
        </div>
    `;

    // TEXT ELEMENTS: text, heading, label, paragraph
    if (['text', 'heading', 'label', 'paragraph'].includes(el.type)) {
        html += `
            <div class="property-group">
                <label class="property-label">Content</label>
                <textarea class="property-textarea" id="element-content">${el.content || ''}</textarea>
            </div>
            
            <div class="property-group">
                <label class="property-label">Font Size</label>
                <input type="number" class="property-input" id="element-fontsize" value="${parseInt(el.styles.fontSize, 10) || 14}" min="8" max="72">
            </div>
            
            <div class="property-group">
                <label class="property-label">Font Family</label>
                <select class="property-select" id="element-fontfamily">
                    <option value="Arial" ${el.styles.fontFamily === 'Arial' ? 'selected' : ''}>Arial</option>
                    <option value="Times New Roman" ${el.styles.fontFamily === 'Times New Roman' ? 'selected' : ''}>Times New Roman</option>
                    <option value="Courier" ${el.styles.fontFamily === 'Courier' ? 'selected' : ''}>Courier</option>
                    <option value="Georgia" ${el.styles.fontFamily === 'Georgia' ? 'selected' : ''}>Georgia</option>
                    <option value="Helvetica" ${el.styles.fontFamily === 'Helvetica' ? 'selected' : ''}>Helvetica</option>
                </select>
            </div>
            
            <div class="property-group">
                <label class="property-label">Font Weight</label>
                <select class="property-select" id="element-fontweight">
                    <option value="normal" ${el.styles.fontWeight === 'normal' ? 'selected' : ''}>Normal</option>
                    <option value="bold" ${el.styles.fontWeight === 'bold' ? 'selected' : ''}>Bold</option>
                    <option value="600" ${el.styles.fontWeight === '600' ? 'selected' : ''}>Semi-Bold</option>
                </select>
            </div>
            
            <div class="property-group">
                <label class="property-label">Text Align</label>
                <select class="property-select" id="element-textalign">
                    <option value="left" ${el.styles.textAlign === 'left' ? 'selected' : ''}>Left</option>
                    <option value="center" ${el.styles.textAlign === 'center' ? 'selected' : ''}>Center</option>
                    <option value="right" ${el.styles.textAlign === 'right' ? 'selected' : ''}>Right</option>
                    <option value="justify" ${el.styles.textAlign === 'justify' ? 'selected' : ''}>Justify</option>
                </select>
            </div>
            
            <div class="property-group">
                <label class="property-label">Text Color</label>
                <input type="color" class="property-color-picker" id="element-color" value="${el.styles.color || '#000000'}">
            </div>
        `;
    }

    // INPUT FIELD
    else if (el.type === 'input') {
        html += `
            <div class="property-group">
                <label class="property-label">Placeholder</label>
                <input type="text" class="property-input" id="element-placeholder" value="${el.placeholder || ''}" placeholder="Enter placeholder...">
            </div>
            
            <div class="property-group">
                <label class="property-label">Field Name</label>
                <input type="text" class="property-input" id="element-fieldname" value="${el.fieldName || ''}" placeholder="field_name">
            </div>
            
            <div class="property-group">
                <label class="property-label">Input Type</label>
                <select class="property-select" id="element-inputtype">
                    <option value="text" ${el.inputType === 'text' ? 'selected' : ''}>Text</option>
                    <option value="email" ${el.inputType === 'email' ? 'selected' : ''}>Email</option>
                    <option value="number" ${el.inputType === 'number' ? 'selected' : ''}>Number</option>
                    <option value="date" ${el.inputType === 'date' ? 'selected' : ''}>Date</option>
                    <option value="tel" ${el.inputType === 'tel' ? 'selected' : ''}>Phone</option>
                </select>
            </div>
            
            <div class="property-group">
                <label class="property-label">Border Color</label>
                <input type="color" class="property-color-picker" id="element-bordercolor" value="${el.styles.borderColor || '#cccccc'}">
            </div>
        `;
    }

    // TEXTAREA
    else if (el.type === 'textarea') {
        html += `
            <div class="property-group">
                <label class="property-label">Placeholder</label>
                <input type="text" class="property-input" id="element-placeholder" value="${el.placeholder || ''}" placeholder="Enter placeholder...">
            </div>
            
            <div class="property-group">
                <label class="property-label">Field Name</label>
                <input type="text" class="property-input" id="element-fieldname" value="${el.fieldName || ''}" placeholder="field_name">
            </div>
            
            <div class="property-group">
                <label class="property-label">Rows</label>
                <input type="number" class="property-input" id="element-rows" value="${el.rows || 4}" min="2" max="20">
            </div>
        `;
    }

    // CHECKBOX & RADIO
    else if (['checkbox', 'radio'].includes(el.type)) {
        html += `
            <div class="property-group">
                <label class="property-label">Label Text</label>
                <input type="text" class="property-input" id="element-label" value="${el.label || ''}" placeholder="Checkbox label">
            </div>
            
            <div class="property-group">
                <label class="property-label">Field Name</label>
                <input type="text" class="property-input" id="element-fieldname" value="${el.fieldName || ''}" placeholder="field_name">
            </div>
            
            <div class="property-group">
                <label class="property-label">Checked by Default</label>
                <select class="property-select" id="element-checked">
                    <option value="false" ${!el.checked ? 'selected' : ''}>No</option>
                    <option value="true" ${el.checked ? 'selected' : ''}>Yes</option>
                </select>
            </div>
        `;
    }

    // SELECT DROPDOWN
    else if (el.type === 'select') {
        html += `
            <div class="property-group">
                <label class="property-label">Field Name</label>
                <input type="text" class="property-input" id="element-fieldname" value="${el.fieldName || ''}" placeholder="field_name">
            </div>
            
            <div class="property-group">
                <label class="property-label">Options (one per line)</label>
                <textarea class="property-textarea" id="element-options" placeholder="Option 1\nOption 2\nOption 3">${el.options || ''}</textarea>
            </div>
        `;
    }

    // SIGNATURE
    else if (el.type === 'signature') {
        html += `
            <div class="property-group">
                <label class="property-label">Field Name</label>
                <input type="text" class="property-input" id="element-fieldname" value="${el.fieldName || ''}" placeholder="signature_field">
            </div>
            
            <div class="property-group">
                <label class="property-label">Border Style</label>
                <select class="property-select" id="element-borderstyle">
                    <option value="solid" ${el.styles.borderStyle === 'solid' ? 'selected' : ''}>Solid</option>
                    <option value="dashed" ${el.styles.borderStyle === 'dashed' ? 'selected' : ''}>Dashed</option>
                    <option value="dotted" ${el.styles.borderStyle === 'dotted' ? 'selected' : ''}>Dotted</option>
                </select>
            </div>
            
            <div class="property-group">
                <label class="property-label">Border Color</label>
                <input type="color" class="property-color-picker" id="element-bordercolor" value="${el.styles.borderColor || '#cccccc'}">
            </div>
        `;
    }

    // IMAGE
    else if (el.type === 'image') {
        html += `
            <div class="property-group">
                <label class="property-label">Image Source</label>
                <input type="text" class="property-input" id="element-imagesrc" value="${el.imageSrc || ''}" placeholder="Image URL">
                <button class="r2pdf-btn r2pdf-btn-small" id="select-image-btn" style="margin-top:8px; width:100%; background:#667eea; color:white;">
                    <span class="dashicons dashicons-admin-media"></span> Select from Media Library
                </button>
            </div>
            
            <div class="property-group">
                <label class="property-label">Alt Text</label>
                <input type="text" class="property-input" id="element-alttext" value="${el.altText || ''}" placeholder="Alternative text">
            </div>
            
            <div class="property-group">
                <label class="property-label">Object Fit</label>
                <select class="property-select" id="element-objectfit">
                    <option value="contain" ${el.styles.objectFit === 'contain' ? 'selected' : ''}>Contain</option>
                    <option value="cover" ${el.styles.objectFit === 'cover' ? 'selected' : ''}>Cover</option>
                    <option value="fill" ${el.styles.objectFit === 'fill' ? 'selected' : ''}>Fill</option>
                    <option value="none" ${el.styles.objectFit === 'none' ? 'selected' : ''}>None</option>
                </select>
            </div>
        `;
    }

    // LOGO
    else if (el.type === 'logo') {
        html += `
            <div class="property-group">
                <label class="property-label">Logo Source</label>
                <select class="property-select" id="element-logosource">
                    <option value="site" ${el.logoSource === 'site' ? 'selected' : ''}>Site Logo</option>
                    <option value="custom" ${el.logoSource === 'custom' ? 'selected' : ''}>Custom Image</option>
                </select>
            </div>
            
            <div class="property-group" id="custom-logo-group" style="display:${el.logoSource === 'custom' ? 'block' : 'none'};">
                <label class="property-label">Custom Logo URL</label>
                <input type="text" class="property-input" id="element-customlogo" value="${el.customLogo || ''}" placeholder="Image URL">
                <button class="r2pdf-btn r2pdf-btn-small" id="select-logo-btn" style="margin-top:8px; width:100%; background:#667eea; color:white;">
                    <span class="dashicons dashicons-admin-media"></span> Select from Media Library
                </button>
            </div>
            
            <div class="property-group">
                <label class="property-label">Logo Max Height</label>
                <input type="number" class="property-input" id="element-logoheight" value="${el.logoHeight || 50}" min="20" max="200">
            </div>
        `;
    }

    // SHAPES: rectangle, circle, line
    else if (['rectangle', 'circle', 'line'].includes(el.type)) {
        html += `
            <div class="property-group">
                <label class="property-label">Fill Color</label>
                <input type="color" class="property-color-picker" id="element-bgcolor" value="${el.styles.backgroundColor || '#ffffff'}">
            </div>
            
            <div class="property-group">
                <label class="property-label">Border Color</label>
                <input type="color" class="property-color-picker" id="element-bordercolor" value="${el.styles.borderColor || '#000000'}">
            </div>
            
            <div class="property-group">
                <label class="property-label">Border Width</label>
                <input type="number" class="property-input" id="element-borderwidth" value="${parseInt(el.styles.borderWidth, 10) || 2}" min="0" max="20">
            </div>
        `;
    }

    // SHORTCODE / DYNAMIC CONTENT
    else if (['shortcode', 'post-title', 'custom-field'].includes(el.type)) {
        html += `
            <div class="property-group">
                <label class="property-label">Dynamic Content</label>
                <input type="text" class="property-input" id="element-content" value="${el.content || ''}" placeholder="Enter shortcode or field name">
                <small style="display:block; margin-top:6px; color:#6b7280; font-size:11px;">
                    Examples: [my_shortcode] or {post_title} or {custom_field_name}
                </small>
            </div>
            
            <div class="property-group">
                <label class="property-label">Font Size</label>
                <input type="number" class="property-input" id="element-fontsize" value="${parseInt(el.styles.fontSize, 10) || 14}" min="8" max="72">
            </div>
            
            <div class="property-group">
                <label class="property-label">Text Color</label>
                <input type="color" class="property-color-picker" id="element-color" value="${el.styles.color || '#000000'}">
            </div>
        `;
    }

    // COMMON: Position & Size (for all elements)
    html += `
        <div class="property-group">
            <label class="property-label">Position & Size</label>
            <div class="property-row">
                <div><small>X</small><input type="number" class="property-input" id="element-x" value="${el.x}"></div>
                <div><small>Y</small><input type="number" class="property-input" id="element-y" value="${el.y}"></div>
            </div>
            <div class="property-row" style="margin-top:8px;">
                <div><small>Width</small><input type="number" class="property-input" id="element-width" value="${el.width}"></div>
                <div><small>Height</small><input type="number" class="property-input" id="element-height" value="${el.height}"></div>
            </div>
        </div>
    `;

    // Background color (for text elements)
    if (['text', 'heading', 'label', 'paragraph'].includes(el.type)) {
        html += `
            <div class="property-group">
                <label class="property-label">Background Color</label>
                <input type="color" class="property-color-picker" id="element-bgcolor" value="${el.styles.backgroundColor || '#ffffff'}">
            </div>
        `;
    }

    // COMMON: Delete button
    html += `
        <div class="property-group">
            <button class="property-btn" id="delete-element-btn">
                <span class="dashicons dashicons-trash"></span> Delete Element
            </button>
        </div>
    `;

    $('#properties-panel').html(html);

    // Bind all the event handlers
    bindPropertyHandlers(el);
}

    function bindPropertyHandlers(el) {
        // Content
        $('#element-content').on('input', function () {
            el.content = $(this).val();
            $('#' + el.id).html(el.content);
        });

        // Position
        $('#element-x').on('input', function () {
            el.x = parseInt($(this).val(), 10) || 0;
            $('#' + el.id).css('left', el.x + 'px');
        });
        $('#element-y').on('input', function () {
            el.y = parseInt($(this).val(), 10) || 0;
            $('#' + el.id).css('top', el.y + 'px');
        });

        // Size
        $('#element-width').on('input', function () {
            el.width = parseInt($(this).val(), 10) || 0;
            $('#' + el.id).css('width', el.width + 'px');
        });
        $('#element-height').on('input', function () {
            el.height = parseInt($(this).val(), 10) || 0;
            $('#' + el.id).css('height', el.height + 'px');
        });

        // Font
        $('#element-fontsize').on('input', function () {
            el.styles.fontSize = $(this).val() + 'px';
            $('#' + el.id).css('fontSize', el.styles.fontSize);
        });
        $('#element-fontfamily').on('change', function () {
            el.styles.fontFamily = $(this).val();
            $('#' + el.id).css('fontFamily', el.styles.fontFamily);
        });
        $('#element-fontweight').on('change', function () {
            el.styles.fontWeight = $(this).val();
            $('#' + el.id).css('fontWeight', el.styles.fontWeight);
        });
        $('#element-textalign').on('change', function () {
            el.styles.textAlign = $(this).val();
            $('#' + el.id).css('textAlign', el.styles.textAlign);
        });

        // Colors
        $('#element-color').on('input', function () {
            el.styles.color = $(this).val();
            $('#' + el.id).css('color', el.styles.color);
        });
        $('#element-bgcolor').on('input', function () {
            el.styles.backgroundColor = $(this).val();
            $('#' + el.id).css('backgroundColor', el.styles.backgroundColor);
        });
        $('#element-bordercolor').on('input', function () {
            el.styles.borderColor = $(this).val();
            $('#' + el.id).css('borderColor', el.styles.borderColor);
        });
        $('#element-borderwidth').on('input', function () {
            el.styles.borderWidth = $(this).val() + 'px';
            $('#' + el.id).css('borderWidth', el.styles.borderWidth);
        });

        // Form fields
        $('#element-placeholder').on('input', function () {
            el.placeholder = $(this).val();
            $('#' + el.id).find('input, textarea').attr('placeholder', el.placeholder);
        });
        $('#element-fieldname').on('input', function () {
            el.fieldName = $(this).val();
        });
        $('#element-label').on('input', function () {
            el.label = $(this).val();
            $('#' + el.id).html('<input type="' + el.type + '"> ' + el.label);
        });

        // Image
        $('#element-imagesrc').on('input', function () {
            el.imageSrc = $(this).val();
            $('#' + el.id).html('<img src="' + el.imageSrc + '" style="width:100%;height:100%;object-fit:' + (el.styles.objectFit || 'contain') + ';">');
        });
        $('#element-alttext').on('input', function () {
            el.altText = $(this).val();
        });
        $('#element-objectfit').on('change', function () {
            el.styles.objectFit = $(this).val();
            $('#' + el.id).find('img').css('object-fit', el.styles.objectFit);
        });

        // Logo
        $('#element-logosource').on('change', function () {
            el.logoSource = $(this).val();
            $('#custom-logo-group').toggle(el.logoSource === 'custom');
        });
        $('#element-customlogo').on('input', function () {
            el.customLogo = $(this).val();
        });

        // Media Library buttons
        $('#select-image-btn, #select-logo-btn').on('click', function () {
            openMediaLibrary(el, $(this).attr('id') === 'select-logo-btn' ? 'logo' : 'image');
        });

        // Delete button
        $('#delete-element-btn').on('click', deleteSelectedElement);
    }

    // WordPress Media Library integration
    function openMediaLibrary(element, type) {
        if (typeof wp === 'undefined' || !wp.media) {
            alert('WordPress Media Library not available');
            return;
        }

        const frame = wp.media({
            title: type === 'logo' ? 'Select Logo' : 'Select Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            
            if (type === 'logo') {
                element.customLogo = attachment.url;
                $('#element-customlogo').val(attachment.url);
            } else {
                element.imageSrc = attachment.url;
                $('#element-imagesrc').val(attachment.url);
                $('#' + element.id).html('<img src="' + attachment.url + '" style="width:100%;height:100%;object-fit:' + (element.styles.objectFit || 'contain') + ';">');
            }
            
            addToHistory();
        });

        frame.open();
    }


    function deleteSelectedElement() {
        const el = BuilderState.selectedElement;
        if (!el) return;

        $('#' + el.id).remove();
        BuilderState.elements = BuilderState.elements.filter(function (e) {
            return e.id !== el.id;
        });

        const page = BuilderState.pages.find(p => p.id === el.pageId);
        if (page) {
            page.elements = page.elements.filter(id => id !== el.id);
        }

        BuilderState.selectedElement = null;
        showEmptyProperties();
        addToHistory();
    }

    function duplicateSelectedElement() {
        const el = BuilderState.selectedElement;
        if (!el) return;
        addElement(el.type, el.x + 20, el.y + 20);
    }

    // ---------------------------------------------------------------------
    // ZOOM / ALIGN / HISTORY
    // ---------------------------------------------------------------------
    function setZoom(level) {
        BuilderState.zoom = Math.max(50, Math.min(200, level));
        $('#zoom-level').val(BuilderState.zoom + '%');
        $('.pdf-canvas').css('transform', 'scale(' + (BuilderState.zoom / 100) + ')');
    }

    function alignElement(alignment) {
        const el = BuilderState.selectedElement;
        if (!el) return;

        const $page = $('.canvas-page[data-page="' + el.pageId + '"]');
        const w = $page.width();

        if (alignment === 'left') el.x = 10;
        if (alignment === 'center') el.x = (w - el.width) / 2;
        if (alignment === 'right') el.x = w - el.width - 10;

        $('#' + el.id).css('left', el.x + 'px');
        updatePropertiesPanel();
        addToHistory();
    }

    function addToHistory() {
        const snapshot = JSON.stringify({
            pages: BuilderState.pages,
            elements: BuilderState.elements
        });

        BuilderState.history = BuilderState.history.slice(0, BuilderState.historyIndex + 1);
        BuilderState.history.push(snapshot);
        BuilderState.historyIndex = BuilderState.history.length - 1;
    }

    function undo() {
        if (BuilderState.historyIndex <= 0) return;
        BuilderState.historyIndex--;
        loadHistory();
    }

    function redo() {
        if (BuilderState.historyIndex >= BuilderState.history.length - 1) return;
        BuilderState.historyIndex++;
        loadHistory();
    }

    function loadHistory() {
        const data = JSON.parse(BuilderState.history[BuilderState.historyIndex]);
        BuilderState.pages = data.pages || [{ id: 1, name: 'Page 1', elements: [] }];
        BuilderState.elements = data.elements || [];

        buildPagesDOM();
        $('.canvas-element').remove();
        BuilderState.elements.forEach(renderElement);
        renderPagesList();
        updateCanvasForPage(BuilderState.currentPage);
    }

    // ---------------------------------------------------------------------
    // SAVE / LOAD / EXPORT / PREVIEW
    // ---------------------------------------------------------------------
    function saveTemplate() {
        const templateName = $('#template-name').val() || 'Untitled Template';
        const templateData = JSON.stringify({
            pages: BuilderState.pages,
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

        $.post(reverse2pdf_builder.ajaxurl, data)
            .done(function (resp) {
                if (resp && resp.success) {
                    alert('Template saved successfully');
                    if (resp.data && resp.data.template_id) {
                        BuilderState.templateId = resp.data.template_id;
                    }
                } else {
                    alert('Error saving template');
                }
            })
            .fail(function () {
                alert('Network error while saving');
            });
    }

    function loadTemplate(templateData) {
        try {
            const data = JSON.parse(templateData.template_data);

            BuilderState.pages = data.pages || [{ id: 1, name: 'Page 1', elements: [] }];
            BuilderState.elements = data.elements || [];
            BuilderState.settings = data.settings || BuilderState.settings;

            buildPagesDOM();
            BuilderState.elements.forEach(renderElement);
            renderPagesList();
            updateCanvasForPage(BuilderState.currentPage);
        } catch (e) {
            console.error('Reverse2PDF: failed to load template', e);
        }
    }

    function exportPDF() {
        alert('Hook exportPDF() to your server-side generation endpoint.');
    }

    function previewPDF() {
        alert('Hook previewPDF() to your preview endpoint.');
    }

    // ---------------------------------------------------------------------
    // DEFAULT VALUES
    // ---------------------------------------------------------------------
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
            text: 'Text',
            heading: 'Heading',
            label: 'Label',
            paragraph: 'Paragraph text',
            shortcode: '[shortcode]',
            'post-title': '{post_title}',
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

    // ---------------------------------------------------------------------
    // START
    // ---------------------------------------------------------------------
    init();
});
