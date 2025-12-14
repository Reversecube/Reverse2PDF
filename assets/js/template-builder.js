(function($) {
    'use strict';

    let currentZoom = 100;
    let selectedElement = null;
    let elements = [];
    let elementIdCounter = 0;
    let isDragging = false;
    let isResizing = false;

    $(document).ready(function() {
        console.log('🚀 Reverse2PDF Builder Initializing...');
        initBuilder();
    });

    function initBuilder() {
        loadTemplate();
        initDragAndDrop();
        initToolbar();
        initCanvas();

        // Button handlers
        $('#save-template-btn').on('click', saveTemplate);
        $('#test-pdf-btn').on('click', testPDF);
        $('#add-page-btn').on('click', addPage);

        console.log('✅ Builder initialized successfully');
    }

    // ========================================================================
    // DRAG AND DROP
    // ========================================================================

    function initDragAndDrop() {
        $('.element-item').each(function() {
            $(this).draggable({
                helper: 'clone',
                cursor: 'grabbing',
                revert: 'invalid',
                zIndex: 10000,
                start: function() {
                    $(this).css('opacity', 0.7);
                },
                stop: function() {
                    $(this).css('opacity', 1);
                }
            });
        });

        $('.canvas-page').droppable({
            accept: '.element-item',
            drop: function(event, ui) {
                const type = ui.draggable.data('type');
                const offset = $(this).offset();
                const x = event.pageX - offset.left;
                const y = event.pageY - offset.top;

                addElement(type, x, y);
                console.log('✅ Element added:', type);
            }
        });
    }

    function addElement(type, x, y) {
        const id = 'element-' + (++elementIdCounter);

        const defaults = {
            text: { content: 'Text', width: 200, height: 40, fontSize: 14 },
            heading: { content: 'Heading', width: 300, height: 50, fontSize: 24 },
            image: { content: '[Image]', width: 200, height: 150, fontSize: 12 },
            input: { content: '[Input Field]', width: 250, height: 40, fontSize: 14 },
            textarea: { content: '[Text Area]', width: 300, height: 100, fontSize: 14 },
            line: { content: '', width: 200, height: 2, fontSize: 0 },
            box: { content: '', width: 150, height: 150, fontSize: 0 }
        };

        const def = defaults[type] || defaults.text;

        const element = {
            id: id,
            type: type,
            x: Math.max(0, x - 50),
            y: Math.max(0, y - 20),
            width: def.width,
            height: def.height,
            content: def.content,
            fontSize: def.fontSize,
            fontFamily: 'Arial',
            fontWeight: 'normal',
            fontStyle: 'normal',
            textAlign: 'left',
            color: '#000000',
            backgroundColor: 'transparent',
            borderWidth: type === 'box' ? 2 : 0,
            borderColor: '#000000',
            borderStyle: 'solid'
        };

        elements.push(element);
        renderElement(element);
        selectElement(id);
    }

    function renderElement(element) {
        const $el = $('<div>')
            .addClass('canvas-element')
            .attr('data-id', element.id)
            .attr('data-type', element.type)
            .css({
                left: element.x + 'px',
                top: element.y + 'px',
                width: element.width + 'px',
                height: element.height + 'px',
                fontSize: element.fontSize + 'px',
                fontFamily: element.fontFamily,
                fontWeight: element.fontWeight,
                fontStyle: element.fontStyle,
                textAlign: element.textAlign,
                color: element.color,
                backgroundColor: element.backgroundColor,
                border: element.borderWidth + 'px ' + element.borderStyle + ' ' + element.borderColor,
                lineHeight: element.type === 'line' ? '0' : 'normal',
                display: 'flex',
                alignItems: element.type === 'line' ? 'flex-start' : 'center',
                padding: element.type === 'box' ? '0' : '8px'
            });

        if (element.type !== 'line' && element.type !== 'box') {
            $el.text(element.content);
        }

        // Add resize handles
        ['nw', 'ne', 'sw', 'se', 'n', 's', 'e', 'w'].forEach(function(pos) {
            $el.append('<div class="resize-handle ' + pos + '"></div>');
        });

        // Make draggable
        $el.draggable({
            containment: '.canvas-page',
            start: function() {
                isDragging = true;
                $(this).addClass('dragging');
            },
            drag: function(event, ui) {
                updateElementPosition($(this).data('id'), ui.position.left, ui.position.top);
            },
            stop: function() {
                isDragging = false;
                $(this).removeClass('dragging');
            }
        });

        // Make resizable
        $el.resizable({
            handles: 'all',
            start: function() {
                isResizing = true;
            },
            resize: function(event, ui) {
                updateElementSize($(this).data('id'), ui.size.width, ui.size.height);
            },
            stop: function() {
                isResizing = false;
            }
        });

        // Click to select
        $el.on('click', function(e) {
            if (!isDragging && !isResizing) {
                e.stopPropagation();
                selectElement($(this).data('id'));
            }
        });

        // Double click to edit
        $el.on('dblclick', function() {
            if (element.type !== 'line' && element.type !== 'box' && element.type !== 'image') {
                editElementContent($(this).data('id'));
            }
        });

        $('.canvas-page').first().append($el);
    }

    // ========================================================================
    // ELEMENT SELECTION & PROPERTIES
    // ========================================================================

    function selectElement(id) {
        deselectAll();
        selectedElement = id;
        $('[data-id="' + id + '"]').addClass('selected');
        showProperties(id);
    }

    function deselectAll() {
        $('.canvas-element').removeClass('selected');
        selectedElement = null;
        hideProperties();
    }

    function showProperties(id) {
        const element = elements.find(el => el.id === id);
        if (!element) return;

        let html = '<div class="property-group">';
        html += '<label class="property-label">Type</label>';
        html += '<div style="padding: 8px; background: #f3f4f6; border-radius: 6px; font-weight: 600; color: #667eea;">' + element.type.toUpperCase() + '</div>';
        html += '</div>';

        if (element.type !== 'line' && element.type !== 'box') {
            html += '<div class="property-group">';
            html += '<label class="property-label">Content</label>';
            html += '<textarea class="property-textarea" id="prop-content">' + element.content + '</textarea>';
            html += '</div>';
        }

        html += '<div class="property-group">';
        html += '<label class="property-label">Position & Size</label>';
        html += '<div class="property-row">';
        html += '<input type="number" class="property-input" id="prop-x" placeholder="X" value="' + Math.round(element.x) + '">';
        html += '<input type="number" class="property-input" id="prop-y" placeholder="Y" value="' + Math.round(element.y) + '">';
        html += '</div>';
        html += '<div class="property-row" style="margin-top: 8px;">';
        html += '<input type="number" class="property-input" id="prop-width" placeholder="Width" value="' + Math.round(element.width) + '">';
        html += '<input type="number" class="property-input" id="prop-height" placeholder="Height" value="' + Math.round(element.height) + '">';
        html += '</div>';
        html += '</div>';

        if (element.type !== 'line' && element.type !== 'box' && element.type !== 'image') {
            html += '<div class="property-group">';
            html += '<label class="property-label">Font Size</label>';
            html += '<input type="number" class="property-input" id="prop-fontsize" value="' + element.fontSize + '">';
            html += '</div>';

            html += '<div class="property-group">';
            html += '<label class="property-label">Font Family</label>';
            html += '<select class="property-select" id="prop-fontfamily">';
            ['Arial', 'Helvetica', 'Times New Roman', 'Courier', 'Georgia', 'Verdana'].forEach(function(font) {
                html += '<option value="' + font + '"' + (element.fontFamily === font ? ' selected' : '') + '>' + font + '</option>';
            });
            html += '</select>';
            html += '</div>';

            html += '<div class="property-group">';
            html += '<label class="property-label">Text Align</label>';
            html += '<select class="property-select" id="prop-textalign">';
            ['left', 'center', 'right', 'justify'].forEach(function(align) {
                html += '<option value="' + align + '"' + (element.textAlign === align ? ' selected' : '') + '>' + align.charAt(0).toUpperCase() + align.slice(1) + '</option>';
            });
            html += '</select>';
            html += '</div>';
        }

        html += '<div class="property-group">';
        html += '<label class="property-label">Colors</label>';
        html += '<div style="margin-bottom: 8px;">';
        html += '<small style="display: block; margin-bottom: 4px; color: #6b7280;">Text Color</small>';
        html += '<input type="color" class="property-color-picker" id="prop-color" value="' + element.color + '">';
        html += '</div>';
        html += '<div>';
        html += '<small style="display: block; margin-bottom: 4px; color: #6b7280;">Background</small>';
        html += '<input type="color" class="property-color-picker" id="prop-bgcolor" value="' + (element.backgroundColor === 'transparent' ? '#ffffff' : element.backgroundColor) + '">';
        html += '</div>';
        html += '</div>';

        html += '<div class="property-group">';
        html += '<button class="property-btn" id="delete-element">Delete Element</button>';
        html += '</div>';

        $('#properties-panel').html(html);

        // Event handlers
        $('#prop-content').on('input', function() { updateProp(id, 'content', $(this).val()); });
        $('#prop-x').on('input', function() { updateProp(id, 'x', parseFloat($(this).val())); });
        $('#prop-y').on('input', function() { updateProp(id, 'y', parseFloat($(this).val())); });
        $('#prop-width').on('input', function() { updateProp(id, 'width', parseFloat($(this).val())); });
        $('#prop-height').on('input', function() { updateProp(id, 'height', parseFloat($(this).val())); });
        $('#prop-fontsize').on('input', function() { updateProp(id, 'fontSize', parseInt($(this).val())); });
        $('#prop-fontfamily').on('change', function() { updateProp(id, 'fontFamily', $(this).val()); });
        $('#prop-textalign').on('change', function() { updateProp(id, 'textAlign', $(this).val()); });
        $('#prop-color').on('input', function() { updateProp(id, 'color', $(this).val()); });
        $('#prop-bgcolor').on('input', function() { updateProp(id, 'backgroundColor', $(this).val()); });
        $('#delete-element').on('click', function() { deleteElement(id); });
    }

    function hideProperties() {
        $('#properties-panel').html('<div class="properties-empty"><span class="dashicons dashicons-admin-generic"></span><p>Select an element to edit properties</p></div>');
    }

    function updateProp(id, prop, value) {
        const element = elements.find(el => el.id === id);
        if (!element) return;

        element[prop] = value;
        const $el = $('[data-id="' + id + '"]');

        if (prop === 'content') {
            $el.text(value);
        } else if (prop === 'x') {
            $el.css('left', value + 'px');
        } else if (prop === 'y') {
            $el.css('top', value + 'px');
        } else if (prop === 'width') {
            $el.css('width', value + 'px');
        } else if (prop === 'height') {
            $el.css('height', value + 'px');
        } else if (prop === 'fontSize') {
            $el.css('fontSize', value + 'px');
        } else if (prop === 'fontFamily') {
            $el.css('fontFamily', value);
        } else if (prop === 'textAlign') {
            $el.css('textAlign', value);
        } else if (prop === 'color') {
            $el.css('color', value);
        } else if (prop === 'backgroundColor') {
            $el.css('backgroundColor', value);
        }
    }

    function updateElementPosition(id, x, y) {
        const element = elements.find(el => el.id === id);
        if (element) {
            element.x = x;
            element.y = y;
            if (selectedElement === id) {
                $('#prop-x').val(Math.round(x));
                $('#prop-y').val(Math.round(y));
            }
        }
    }

    function updateElementSize(id, width, height) {
        const element = elements.find(el => el.id === id);
        if (element) {
            element.width = width;
            element.height = height;
            if (selectedElement === id) {
                $('#prop-width').val(Math.round(width));
                $('#prop-height').val(Math.round(height));
            }
        }
    }

    function editElementContent(id) {
        const element = elements.find(el => el.id === id);
        if (!element) return;

        const newContent = prompt('Edit content:', element.content);
        if (newContent !== null) {
            updateProp(id, 'content', newContent);
            $('#prop-content').val(newContent);
        }
    }

    function deleteElement(id) {
        if (!confirm('Delete this element?')) return;

        elements = elements.filter(el => el.id !== id);
        $('[data-id="' + id + '"]').remove();
        deselectAll();
        console.log('🗑️ Element deleted:', id);
    }

    // ========================================================================
    // TOOLBAR
    // ========================================================================

    function initToolbar() {
        $('#zoom-in').on('click', function() { setZoom(currentZoom + 10); });
        $('#zoom-out').on('click', function() { setZoom(currentZoom - 10); });
        $('#zoom-fit').on('click', function() { setZoom(100); });

        $('#page-size').on('change', updatePageSize);
        $('#orientation').on('change', updateOrientation);
    }

    function setZoom(zoom) {
        zoom = Math.max(50, Math.min(200, zoom));
        currentZoom = zoom;
        $('#zoom-level').val(zoom + '%');
        $('.pdf-canvas').css('transform', 'scale(' + (zoom / 100) + ')');
        console.log('🔍 Zoom:', zoom + '%');
    }

    function updatePageSize() {
        const size = $(this).val();
        console.log('📄 Page size:', size);
    }

    function updateOrientation() {
        const orientation = $(this).val();
        $('.canvas-page').toggleClass('landscape', orientation === 'landscape');
        console.log('🔄 Orientation:', orientation);
    }

    function addPage() {
        alert('Multi-page support coming soon!');
    }

    // ========================================================================
    // CANVAS
    // ========================================================================

    function initCanvas() {
        $('.pdf-canvas').on('click', function(e) {
            if (e.target === this || $(e.target).hasClass('canvas-page')) {
                deselectAll();
            }
        });
    }

    // ========================================================================
    // SAVE / LOAD
    // ========================================================================

    function loadTemplate() {
        const templateData = $('#pdf-canvas').data('template');
        if (templateData && templateData.template_data) {
            try {
                const data = JSON.parse(templateData.template_data);
                if (data.elements && Array.isArray(data.elements)) {
                    elements = data.elements;
                    elementIdCounter = Math.max(...elements.map(el => parseInt(el.id.split('-')[1]) || 0), 0);
                    elements.forEach(renderElement);
                    console.log('✅ Template loaded:', elements.length, 'elements');
                }
            } catch (e) {
                console.error('❌ Error loading template:', e);
            }
        }
    }

    function saveTemplate() {
        const $btn = $('#save-template-btn');
        const templateId = $('#pdf-canvas').data('template-id') || 0;
        const templateName = $('#template-name').val() || 'Untitled Template';

        if (!templateName.trim()) {
            alert('Please enter a template name');
            $('#template-name').focus();
            return;
        }

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Saving...');

        const data = {
            action: 'reverse2pdf_save_template',
            nonce: reverse2pdf_builder.nonce,
            template_id: templateId,
            template_name: templateName,
            template_data: JSON.stringify({
                elements: elements,
                pageSize: $('#page-size').val(),
                orientation: $('#orientation').val()
            }),
            page_size: $('#page-size').val(),
            orientation: $('#orientation').val()
        };

        $.post(ajaxurl, data)
            .done(function(response) {
                if (response.success) {
                    alert('✅ ' + response.data.message);

                    if (!templateId && response.data.template_id) {
                        $('#pdf-canvas').data('template-id', response.data.template_id);
                        window.history.replaceState({}, '', '?page=reverse2pdf-builder&template_id=' + response.data.template_id);
                    }

                    console.log('✅ Template saved successfully');
                } else {
                    alert('❌ Error: ' + (response.data || 'Could not save template'));
                }
            })
            .fail(function() {
                alert('❌ Network error - please try again');
            })
            .always(function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Template');
            });
    }

    function testPDF() {
        alert('PDF Preview: This will generate a preview of your template.\n\nFeature coming soon!');
    }

})(jQuery);