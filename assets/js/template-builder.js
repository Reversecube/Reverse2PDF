/**
 * Reverse2PDF Template Builder – rebuilt implementation
 * Features:
 * - Multi‑page support (add/select/delete pages)
 * - Drag & drop elements per page
 * - Element resize/drag
 * - Properties panel
 * - Zoom, align, undo/redo, save skeleton
 */

jQuery(document).ready(function ($) {
    'use strict';

    console.log('Reverse2PDF Builder loaded (custom implementation)');

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
        const $canvasWrap = $('#pdf-canvas');
        if (!$canvasWrap.length) {
            console.warn('Reverse2PDF: #pdf-canvas not found');
            return;
        }

        BuilderState.templateId = $canvasWrap.data('template-id') || 0;
        const templateData = $canvasWrap.data('template');

        buildInitialPagesDOM();            // create .canvas-page for page 1

        if (templateData && templateData.template_data) {
            loadTemplate(templateData);
        }

        setupTabs();
        setupEventListeners();
        setupDragAndDrop();

        renderPagesList();
        updateCanvasForPage(BuilderState.currentPage);
        showEmptyProperties();

        console.log('Reverse2PDF Builder initialized');
    }

    // create .canvas-page containers from current pages
    function buildInitialPagesDOM() {
        const $pdfCanvas = $('.pdf-canvas');
        if (!$pdfCanvas.length) return;

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
        $('.r2pdf-tab-btn').on('click', function () {
            const tab = $(this).data('tab');

            $('.r2pdf-tab-btn').removeClass('active');
            $(this).addClass('active');

            $('.r2pdf-tab-content').removeClass('active');
            $('.r2pdf-tab-content[data-tab="' + tab + '"]').addClass('active');
        });
    }

    // ---------------------------------------------------------------------
    // EVENT LISTENERS (HEADER + TOOLBAR)
    // ---------------------------------------------------------------------
    function setupEventListeners() {
        // Save / Export / Preview
        $('#save-template-btn').on('click', saveTemplate);
        $('#export-pdf-btn').on('click', exportPDF);
        $('#preview-pdf-btn').on('click', previewPDF);

        // Undo / Redo
        $('#undo-btn').on('click', undo);
        $('#redo-btn').on('click', redo);

        // Zoom
        $('#zoom-in').on('click', function () { setZoom(BuilderState.zoom + 10); });
        $('#zoom-out').on('click', function () { setZoom(BuilderState.zoom - 10); });
        $('#zoom-fit').on('click', function () { setZoom(100); });

        // Align
        $('#align-left').on('click', function () { alignElement('left'); });
        $('#align-center').on('click', function () { alignElement('center'); });
        $('#align-right').on('click', function () { alignElement('right'); });

        // Element delete / duplicate
        $('#delete-element').on('click', deleteSelectedElement);
        $('#duplicate-element').on('click', duplicateSelectedElement);

        // Pages
        $('#add-page-btn').on('click', addPage);

        // Settings
        $('#page-size').on('change', function () {
            BuilderState.settings.pageSize = $(this).val();
            renderPagesList();
        });
        $('#orientation').on('change', function () {
            BuilderState.settings.orientation = $(this).val();
            updatePageOrientation();
            renderPagesList();
        });

        // Deselect element when clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.canvas-element').length &&
                !$(e.target).closest('#properties-panel').length) {
                deselectElement();
            }
        });
    }

    // ---------------------------------------------------------------------
    // PAGES: LIST, ADD, SELECT, DELETE
    // ---------------------------------------------------------------------
    function renderPagesList() {
        const $list = $('.pages-list');
        if (!$list.length) return;

        $list.empty();
        const canDelete = BuilderState.pages.length > 1;

        BuilderState.pages.forEach(function (page, index) {
            const isActive = page.id === BuilderState.currentPage;
            const $item = $(`
                <div class="page-item ${isActive ? 'active' : ''}" data-page-id="${page.id}">
                    <div class="page-item-info">
                        <div class="page-item-name">Page ${index + 1}</div>
                        <div class="page-item-size">
                            ${BuilderState.settings.pageSize} - ${BuilderState.settings.orientation}
                        </div>
                    </div>
                    <button class="page-delete-btn"
                            data-page-id="${page.id}"
                            ${canDelete ? '' : 'disabled'}
                            title="${canDelete ? 'Delete page' : 'Cannot delete last page'}">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            `);

            // select page
            $item.on('click', function (e) {
                if ($(e.target).closest('.page-delete-btn').length) return;
                selectPage(page.id);
            });

            // delete page
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

        const page = {
            id: nextId,
            name: 'Page ' + (BuilderState.pages.length + 1),
            elements: []
        };
        BuilderState.pages.push(page);

        // canvas node
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

        console.log('Page added:', page);
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

        // remove elements for this page
        const page = BuilderState.pages.find(p => p.id === pageId);
        if (page && page.elements.length) {
            page.elements.forEach(function (id) {
                $('#' + id).remove();
            });
            BuilderState.elements = BuilderState.elements.filter(function (el) {
                return el.pageId !== pageId;
            });
        }

        // remove canvas node
        $('.canvas-page[data-page="' + pageId + '"]').remove();

        // update pages array
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
            ensureEmptyCanvasMessage();
        } else {
            $('.canvas-empty-state').remove();
        }
    }

    function ensureEmptyCanvasMessage() {
        const $page = $('.canvas-page[data-page="' + BuilderState.currentPage + '"]');
        if (!$page.length) return;

        if (!$page.find('.canvas-empty-state').length) {
            $page.append(`
                <div class="canvas-empty-state">
                    <span class="dashicons dashicons-admin-page"></span>
                    <h3>Start Building Your PDF</h3>
                    <p>Drag elements from left sidebar</p>
                </div>
            `);
        }
    }

    function updatePageOrientation() {
        $('.canvas-page').toggleClass(
            'landscape',
            BuilderState.settings.orientation === 'landscape'
        );
    }

    // ---------------------------------------------------------------------
    // DRAG & DROP ELEMENTS
    // ---------------------------------------------------------------------
    function setupDragAndDrop() {
        // sidebar elements
        $('.element-item')
            .attr('draggable', true)
            .on('dragstart', function (e) {
                const type = $(this).data('type');
                e.originalEvent.dataTransfer.setData('elementType', type);
                $(this).addClass('dragging');
            })
            .on('dragend', function () {
                $(this).removeClass('dragging');
            });

        // canvas
        $('.canvas-page')
            .off('dragover.drop') // avoid duplicate bindings
            .on('dragover.drop', function (e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'copy';
            })
            .on('drop.drop', function (e) {
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
                left: element.x + 'px',
                top: element.y + 'px',
                width: element.width + 'px',
                height: element.height + 'px',
                fontSize: element.styles.fontSize,
                fontFamily: element.styles.fontFamily,
                color: element.styles.color,
                backgroundColor: element.styles.backgroundColor,
                textAlign: element.styles.textAlign
            }
        });

        // content
        if (['text', 'heading', 'label', 'paragraph'].includes(element.type)) {
            $el.html(element.content);
        } else if (element.type === 'input') {
            $el.html('<input type="text" style="width:100%;padding:4px;border:1px solid #ccc;">');
        } else if (element.type === 'checkbox') {
            $el.html('<input type="checkbox"> Checkbox');
        } else if (element.type === 'signature') {
            $el.html('<div style="border:2px dashed #ccc;padding:8px;text-align:center;">Signature</div>');
        } else if (element.type === 'image') {
            $el.html('<div style="border:1px solid #ccc;background:#f5f5f5;height:100%;display:flex;align-items:center;justify-content:center;"><span class="dashicons dashicons-format-image"></span></div>');
        } else {
            $el.html(element.type);
        }

        // resize handles
        ['nw', 'ne', 'sw', 'se', 'n', 's', 'e', 'w'].forEach(function (h) {
            $el.append('<div class="resize-handle ' + h + '"></div>');
        });

        // draggable
        $el.draggable({
            containment: '.canvas-page',
            drag: function (event, ui) {
                element.x = ui.position.left;
                element.y = ui.position.top;
                updatePropertiesPanel();
            },
            stop: function () {
                addToHistory();
            }
        });

        // resizable
        $el.resizable({
            handles: 'n, s, e, w, ne, nw, se, sw',
            resize: function (event, ui) {
                element.width = ui.size.width;
                element.height = ui.size.height;
                updatePropertiesPanel();
            },
            stop: function () {
                addToHistory();
            }
        });

        // select on click
        $el.on('click', function (e) {
            e.stopPropagation();
            selectElement(element.id);
        });

        $page.append($el);

        // hide if on different page
        if (element.pageId !== BuilderState.currentPage) {
            $el.hide();
        }
    }

    // ---------------------------------------------------------------------
    // ELEMENT SELECTION + PROPERTIES
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

    function updatePropertiesPanel() {
        const el = BuilderState.selectedElement;
        if (!el) {
            showEmptyProperties();
            return;
        }

        const html =
            '<div class="property-group">' +
            ' <label class="property-label">Type</label>' +
            ' <div style="padding:8px;background:#f9fafb;border-radius:4px;font-weight:600;text-transform:uppercase;font-size:11px;color:#667eea;">' +
            el.type +
            '</div>' +
            '</div>' +

            '<div class="property-group">' +
            ' <label class="property-label">Content</label>' +
            ' <textarea class="property-textarea" id="element-content">' + (el.content || '') + '</textarea>' +
            '</div>' +

            '<div class="property-group">' +
            ' <label class="property-label">Position & Size</label>' +
            ' <div class="property-row">' +
            '   <div><small>X</small><input type="number" class="property-input" id="element-x" value="' + el.x + '"></div>' +
            '   <div><small>Y</small><input type="number" class="property-input" id="element-y" value="' + el.y + '"></div>' +
            ' </div>' +
            ' <div class="property-row" style="margin-top:8px;">' +
            '   <div><small>Width</small><input type="number" class="property-input" id="element-width" value="' + el.width + '"></div>' +
            '   <div><small>Height</small><input type="number" class="property-input" id="element-height" value="' + el.height + '"></div>' +
            ' </div>' +
            '</div>' +

            '<div class="property-group">' +
            ' <label class="property-label">Font Size</label>' +
            ' <input type="number" class="property-input" id="element-fontsize" value="' + (parseInt(el.styles.fontSize, 10) || 14) + '">' +
            '</div>' +

            '<div class="property-group">' +
            ' <label class="property-label">Font Family</label>' +
            ' <select class="property-select" id="element-fontfamily">' +
            '   <option value="Arial"' + (el.styles.fontFamily === 'Arial' ? ' selected' : '') + '>Arial</option>' +
            '   <option value="Times New Roman"' + (el.styles.fontFamily === 'Times New Roman' ? ' selected' : '') + '>Times New Roman</option>' +
            '   <option value="Courier"' + (el.styles.fontFamily === 'Courier' ? ' selected' : '') + '>Courier</option>' +
            '   <option value="Georgia"' + (el.styles.fontFamily === 'Georgia' ? ' selected' : '') + '>Georgia</option>' +
            ' </select>' +
            '</div>' +

            '<div class="property-group">' +
            ' <label class="property-label">Text Align</label>' +
            ' <select class="property-select" id="element-textalign">' +
            '   <option value="left"' + (el.styles.textAlign === 'left' ? ' selected' : '') + '>Left</option>' +
            '   <option value="center"' + (el.styles.textAlign === 'center' ? ' selected' : '') + '>Center</option>' +
            '   <option value="right"' + (el.styles.textAlign === 'right' ? ' selected' : '') + '>Right</option>' +
            ' </select>' +
            '</div>' +

            '<div class="property-group">' +
            ' <label class="property-label">Colors</label>' +
            ' <div style="margin-bottom:8px;">' +
            '   <small>Text</small>' +
            '   <input type="color" class="property-color-picker" id="element-color" value="' + (el.styles.color || '#000000') + '">' +
            ' </div>' +
            ' <div>' +
            '   <small>Background</small>' +
            '   <input type="color" class="property-color-picker" id="element-bgcolor" value="' + (el.styles.backgroundColor || '#ffffff') + '">' +
            ' </div>' +
            '</div>' +

            '<div class="property-group">' +
            ' <button class="property-btn" id="delete-element-btn">' +
            '   <span class="dashicons dashicons-trash"></span> Delete Element' +
            ' </button>' +
            '</div>';

        $('#properties-panel').html(html);

        // bind property inputs
        $('#element-content').on('input', function () {
            el.content = $(this).val();
            $('#' + el.id).html(el.content);
        });
        $('#element-x').on('input', function () {
            el.x = parseInt($(this).val(), 10) || 0;
            $('#' + el.id).css('left', el.x + 'px');
        });
        $('#element-y').on('input', function () {
            el.y = parseInt($(this).val(), 10) || 0;
            $('#' + el.id).css('top', el.y + 'px');
        });
        $('#element-width').on('input', function () {
            el.width = parseInt($(this).val(), 10) || 0;
            $('#' + el.id).css('width', el.width + 'px');
        });
        $('#element-height').on('input', function () {
            el.height = parseInt($(this).val(), 10) || 0;
            $('#' + el.id).css('height', el.height + 'px');
        });
        $('#element-fontsize').on('input', function () {
            el.styles.fontSize = $(this).val() + 'px';
            $('#' + el.id).css('fontSize', el.styles.fontSize);
        });
        $('#element-fontfamily').on('change', function () {
            el.styles.fontFamily = $(this).val();
            $('#' + el.id).css('fontFamily', el.styles.fontFamily);
        });
        $('#element-textalign').on('change', function () {
            el.styles.textAlign = $(this).val();
            $('#' + el.id).css('textAlign', el.styles.textAlign);
        });
        $('#element-color').on('input', function () {
            el.styles.color = $(this).val();
            $('#' + el.id).css('color', el.styles.color);
        });
        $('#element-bgcolor').on('input', function () {
            el.styles.backgroundColor = $(this).val();
            $('#' + el.id).css('backgroundColor', el.styles.backgroundColor);
        });
        $('#delete-element-btn').on('click', deleteSelectedElement);
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
    // ZOOM, ALIGN, HISTORY
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
        const canvasWidth = $page.width();

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

        buildInitialPagesDOM();
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
            .done(function (response) {
                if (response && response.success) {
                    alert('Template saved successfully!');
                    if (response.data && response.data.template_id) {
                        BuilderState.templateId = response.data.template_id;
                    }
                } else {
                    alert('Error saving template');
                }
            })
            .fail(function () {
                alert('Network error while saving template');
            });
    }

    function loadTemplate(templateData) {
        try {
            const data = JSON.parse(templateData.template_data);

            BuilderState.pages = data.pages || [{ id: 1, name: 'Page 1', elements: [] }];
            BuilderState.elements = data.elements || [];
            BuilderState.settings = data.settings || BuilderState.settings;

            buildInitialPagesDOM();
            BuilderState.elements.forEach(renderElement);
            renderPagesList();
            updateCanvasForPage(BuilderState.currentPage);
        } catch (e) {
            console.error('Failed to load template:', e);
        }
    }

    function exportPDF() {
        alert('Export PDF: integrate server‑side generation here.');
    }

    function previewPDF() {
        alert('Preview PDF: integrate preview endpoint here.');
    }

    // ---------------------------------------------------------------------
    // DEFAULTS
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
