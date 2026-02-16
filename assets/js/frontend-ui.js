jQuery(document).ready(function ($) {
    // State Management
    var setData = null;
    var agreementList = [];
    var userInputs = {};

    // Initialization
    function resetSetState() {
        userInputs = {
            condition: 'used',
            seals_intact: true,
            box_condition: 'like_new',
            is_complete: true,
            completion_level: '100',
            is_built: true,
            weight: 0,
            has_box: true,
            has_instructions: true,
            missing_minifigs: {}
        };
    }
    resetSetState();

    // 1. Search Logic
    $('#tee-search-set').on('click', function () {
        searchSet();
    });

    $('#tee-set-number').on('keypress', function (e) {
        if (e.which == 13) searchSet();
    });

    function searchSet() {
        var set_number = $('#tee-set-number').val();
        if (!set_number) return;

        $('#tee-search-error').hide();
        $('#tee-loading').show();
        $('#tee-search-set').prop('disabled', true);
        $('#tee-result-ui, #tee-main-ui, #tee-set-preview, #tee-minifigs-ui').hide();

        resetSetState();
        $('#tee-minifigs-list').empty().removeData('rendered-set');
        $('.tee-cond-card').removeClass('active');
        $('.tee-cond-card[data-cond="used"]').addClass('active');

        $.ajax({
            url: tee_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'tee_evaluate_set',
                nonce: tee_vars.nonce,
                set_number: set_number
            },
            success: function (response) {
                $('#tee-loading').hide();
                $('#tee-search-set').prop('disabled', false);

                if (response.success) {
                    setData = response.data;
                    userInputs.weight = parseFloat(setData.weight) || 0;

                    $('#tee-set-image-thumb').attr('src', setData.image);
                    $('#tee-set-name-preview').text(setData.name + ' (#' + setData.id + ')');
                    $('#tee-set-preview').fadeIn();
                    $('#tee-main-ui').fadeIn();

                    renderDynamicFlow();
                } else {
                    $('#tee-search-error').text(response.data).show();
                }
            }
        });
    }

    // 2. UI Interaction Logic
    $('.tee-cond-card').on('click', function () {
        $('.tee-cond-card').removeClass('active');
        $(this).addClass('active');
        userInputs.condition = $(this).data('cond');
        renderDynamicFlow();
    });

    function renderSwatches(field, options, currentValue) {
        var group = $('<div class="tee-swatch-group" data-field="' + field + '"></div>');
        $.each(options, function (i, opt) {
            var active = (opt.value === currentValue || opt.value === String(currentValue)) ? 'active' : '';
            var swatch = $('<div class="tee-swatch ' + active + '" data-value="' + opt.value + '">' +
                '<span>' + opt.label +
                (opt.desc ? '<span class="tee-info-icon">i<span class="tee-tooltip-text">' + opt.desc + '</span></span>' : '') +
                '</span>' +
                '</div>');
            group.append(swatch);
        });
        return group;
    }

    function renderNewFlow(container) {
        // Q: Seals Intact?
        container.append('<label class="tee-question-label">Are all box seals intact?</label>');
        container.append(renderSwatches('seals_intact', [
            { label: 'Yes', value: true, desc: 'Original tape/seals unbroken' },
            { label: 'No', value: false, desc: 'Seals cut or box opened' }
        ], userInputs.seals_intact));

        if (userInputs.seals_intact) {
            // Seals Yes: Box Condition
            container.append('<label class="tee-question-label">What is the box condition?</label>');
            container.append(renderSwatches('box_condition', [
                { label: 'Like New', value: 'like_new', desc: 'Box is in good condition, with some minor shelf wear accepted. Box should have no major scrapes/dents/holes etc' },
                { label: 'Fair', value: 'fair', desc: 'Box has some signs of larger dents, scratches, label tears/residue. Box should not be heavily crushed, have holes and box and seals must be intact' },
                { label: 'Bad', value: 'bad', desc: 'Box has signs of heavy wear to corners, tears to box artwork, crushing, holes or heavy scratching' }
            ], userInputs.box_condition));
        } else {
            // Seals No: Is Set Complete?
            container.append('<label class="tee-question-label">Is the set complete?</label>');
            container.append(renderSwatches('is_complete', [
                { label: 'Yes', value: true, desc: 'Includes all parts & bags' },
                { label: 'No', value: false, desc: 'Missing parts or bags' }
            ], userInputs.is_complete));

            if (!userInputs.is_complete) {
                // Incomplete: Weight
                var qWeight = $('<div class="tee-question-item">' +
                    '<label class="tee-question-label">Enter weight of all bags present (grams)</label>' +
                    '<input type="number" id="tee-weight-input" class="tee-input" value="' + userInputs.weight + '">' +
                    '</div>');
                container.append(qWeight);
            }
        }

        bindDynamicEvents();
    }

    function renderUsedFlow(container) {
        // Q: How complete?
        container.append('<label class="tee-question-label">How complete is the set?</label>');
        container.append(renderSwatches('completion_level', [
            { label: '100% Complete', value: '100', desc: 'Includes all minifigures' },
            { label: 'Over 95%', value: '95', desc: 'Missing minor parts' },
            { label: 'Under 95%', value: 'less', desc: 'Incomplete/Mixed' }
        ], userInputs.completion_level));

        if (userInputs.completion_level !== 'less') {
            // Built?
            container.append('<label class="tee-question-label">Is the set built up?</label>');
            container.append(renderSwatches('is_built', [
                { label: 'Yes', value: true, desc: 'Currently assembled' },
                { label: 'No', value: false, desc: 'Partially or fully dismantled' }
            ], userInputs.is_built));

            // Box/Instructions (Converted to Swatch)
            container.append('<label class="tee-question-label">Additional Details</label>');
            var detailVal = 'none';
            if (userInputs.has_box && userInputs.has_instructions) detailVal = 'both';
            else if (userInputs.has_box) detailVal = 'box';
            else if (userInputs.has_instructions) detailVal = 'ins';

            container.append(renderSwatches('details_combo', [
                { label: 'Box & Instructions', value: 'both' },
                { label: 'Box Only', value: 'box' },
                { label: 'Instructions Only', value: 'ins' },
                { label: 'Neither', value: 'none' }
            ], detailVal));
        } else {
            // Under 95%: Weight
            var qWeight = $('<div class="tee-question-item">' +
                '<label class="tee-question-label">Enter the weight of the set (grams)</label>' +
                '<input type="number" id="tee-weight-input" class="tee-input" value="' + userInputs.weight + '">' +
                '</div>');
            container.append(qWeight);
        }

        bindDynamicEvents();
    }

    function renderDynamicFlow() {
        var container = $('#tee-dynamic-questions');
        container.empty();

        if (userInputs.condition === 'new') {
            renderNewFlow(container);
        } else {
            renderUsedFlow(container);
        }

        updateMinifigsUI();
        calculateOffer();
    }

    function bindDynamicEvents() {
        // Swatch Clicks
        $('.tee-swatch').off('click').on('click', function () {
            var group = $(this).closest('.tee-swatch-group');
            var field = group.data('field');
            var val = $(this).data('value');

            // Handle boolean strings
            if (val === true || val === 'true') val = true;
            if (val === false || val === 'false') val = false;

            if (field === 'details_combo') {
                userInputs.has_box = (val === 'both' || val === 'box');
                userInputs.has_instructions = (val === 'both' || val === 'ins');
            } else {
                userInputs[field] = val;
            }

            renderDynamicFlow();
        });


        $('#tee-weight-input').on('change keyup', function () {
            userInputs.weight = parseFloat($(this).val()) || 0;
            calculateOffer();
        });
    }

    function updateMinifigsUI() {
        if (userInputs.condition === 'new' && userInputs.seals_intact) {
            $('#tee-minifigs-ui').hide();
        } else {
            if (Object.keys(setData.minifigs_data).length > 0) {
                $('#minifig-instruction-text').text(userInputs.completion_level === 'less' ? 'Which minifigures are present?' : 'Please verify which minifigures are present (unchecked = missing):');
                renderMinifigs();
                $('#tee-minifigs-ui').fadeIn();
            } else {
                $('#tee-minifigs-ui').hide();
            }
        }
    }

    function renderMinifigs() {
        var container = $('#tee-minifigs-list');
        if (container.data('rendered-set') === setData.id) return;

        container.empty();
        container.data('rendered-set', setData.id);

        $.each(setData.minifigs_data, function (id, minifig) {
            var qtyOwned = minifig.qty;
            var item = $('<div class="minifig-item" data-id="' + id + '" data-max="' + minifig.qty + '">' +
                '<img src="' + minifig.thumbnail + '" alt="' + minifig.name + '">' +
                '<strong>' + minifig.name + '</strong><br>' +
                '<div class="qty-selector">' +
                '<button type="button" class="qty-btn minus">-</button>' +
                '<span class="qty-val">' + qtyOwned + '</span> / ' + minifig.qty +
                '<button type="button" class="qty-btn plus">+</button>' +
                '</div>' +
                '<p class="minifig-status">I have all of these</p>' +
                '</div>');
            container.append(item);
        });

        $('.qty-btn').off('click').on('click', function () {
            var item = $(this).closest('.minifig-item');
            var id = item.data('id');
            var max = parseInt(item.data('max'));
            var valSpan = item.find('.qty-val');
            var current = parseInt(valSpan.text());

            if ($(this).hasClass('plus') && current < max) current++;
            else if ($(this).hasClass('minus') && current > 0) current--;

            valSpan.text(current);
            var status = item.find('.minifig-status');
            if (current === max) {
                status.text('I have all of these').css('color', '');
                delete userInputs.missing_minifigs[id];
            } else {
                var missing = max - current;
                status.text(current === 0 ? 'I am missing all' : 'I am missing ' + missing).css('color', current === 0 ? '#ef4444' : '#f59e0b');
                userInputs.missing_minifigs[id] = missing;
            }
            calculateOffer();
        });
    }

    // 3. Calculation & Results
    function calculateOffer() {
        if (!setData) return;

        $('#tee-final-price').html('<span class="tee-calc-loader"></span>');
        $('#tee-accept-set').prop('disabled', true);

        $.ajax({
            url: tee_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'tee_calculate_offer',
                nonce: tee_vars.nonce,
                set_data: setData,
                user_inputs: userInputs
            },
            success: function (response) {
                $('#tee-accept-set').prop('disabled', false);
                if (response.success) {
                    var data = response.data;
                    if (data.rejected) {
                        $('#tee-final-price').text('£0.00');
                        $('#tee-accept-set').hide();
                        $('#tee-rejection-msg').show();
                        $('#tee-rejection-btn').attr('href', data.rejection_url);
                    } else {
                        var rawOffer = data.offer || '0.00';
                        var formatter = new Intl.NumberFormat('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        var formattedOffer = formatter.format(parseFloat(rawOffer));

                        $('#tee-final-price').text('£' + formattedOffer);
                        $('#tee-rejection-msg').hide();

                        // Weight limit check
                        var currentTotalWeight = 0;
                        agreementList.forEach(function (i) {
                            currentTotalWeight += parseFloat(i.weight) || 0;
                        });
                        var incomingWeight = parseFloat(userInputs.weight) || 0;

                        if (currentTotalWeight + incomingWeight > 18000) {
                            $('#tee-accept-set').hide();
                            $('#tee-weight-error-msg').show();
                        } else {
                            $('#tee-accept-set').show();
                            $('#tee-weight-error-msg').hide();
                            updateStickyBar(rawOffer);
                        }
                    }
                    updateResultBanner(data.offer);
                }
            }
        });
    }

    function updateResultBanner(price) {
        $('#tee-res-name').text(setData.name);
        $('#tee-res-id').text('Set #' + setData.id);

        var tagsContainer = $('#tee-res-tags').empty();
        tagsContainer.append('<span class="tee-tag">' + (userInputs.condition === 'new' ? 'New' : 'Used') + '</span>');

        if (userInputs.condition === 'new') {
            tagsContainer.append('<span class="tee-tag">' + (userInputs.seals_intact ? 'Seals Intact' : 'Seals Broken') + '</span>');
        } else {
            tagsContainer.append('<span class="tee-tag">' + userInputs.completion_level + '% Complete</span>');
        }

        $('#tee-result-ui').fadeIn();
    }

    function updateStickyBar(currentOffer) {
        var total = 0;
        var totalWeight = 0;
        agreementList.forEach(function (item) {
            total += parseFloat(item.offer) || 0;
            totalWeight += parseFloat(item.weight) || 0;
        });

        var currentVal = parseFloat(currentOffer) || 0;
        var formatter = new Intl.NumberFormat('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        $('#tee-sticky-current').text('£' + formatter.format(currentVal));
        $('#tee-sticky-total').text('£' + formatter.format(total));
        $('#tee-sticky-bar').fadeIn();

        if (totalWeight >= 18000) { // 18KG in grams
            $('#tee-weight-limit-msg').show();
            $('#tee-accept-set').hide();
            $('#tee-weight-error-msg').show();
        } else {
            $('#tee-weight-limit-msg').hide();
        }
    }

    // 4. Batch Agreement List
    $('#tee-accept-set').on('click', function () {
        var offerText = $('#tee-final-price').text().replace('£', '').replace(/,/g, '');
        var offer = parseFloat(offerText) || 0;

        // Check weight limit before accepting (Double check for safety)
        var currentTotalWeight = 0;
        agreementList.forEach(function (i) {
            currentTotalWeight += parseFloat(i.weight) || 0;
        });

        var incomingWeight = parseFloat(userInputs.weight) || 0;
        if (currentTotalWeight + incomingWeight > 18000) {
            $('#tee-accept-set').hide();
            $('#tee-weight-error-msg').show();
            return;
        }

        agreementList.push({
            id: setData.id,
            name: setData.name,
            offer: offer,
            weight: userInputs.weight,
            image: setData.image,
            metadata: getMetadataString()
        });

        renderAgreementList();
        $('#tee-main-ui, #tee-result-ui, #tee-minifigs-ui, #tee-set-preview').hide();
        $('#tee-set-number').val('').focus();
        updateStickyBar(0);
    });

    function getMetadataString() {
        var parts = [];
        parts.push(userInputs.condition.toUpperCase());
        if (userInputs.condition === 'new') {
            parts.push(userInputs.seals_intact ? 'Seals Intact' : 'Seals Broken');
            if (userInputs.seals_intact) parts.push('Box: ' + userInputs.box_condition);
        } else {
            parts.push('Completion: ' + userInputs.completion_level + '%');
        }

        var missing = Object.keys(userInputs.missing_minifigs).length;
        if (missing > 0) parts.push('Missing ' + missing + ' Minifigs');

        return parts.join(' | ');
    }

    function renderAgreementList() {
        var container = $('#tee-agreement-items').empty();
        var total = 0;
        var totalWeight = 0;
        var formatter = new Intl.NumberFormat('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        agreementList.forEach(function (item, index) {
            total += parseFloat(item.offer) || 0;
            totalWeight += parseFloat(item.weight) || 0;

            var el = $('<div class="tee-agreement-item">' +
                '<div class="tee-item-info">' +
                '<strong>' + item.name + ' (#' + item.id + ')</strong>' +
                '<span>' + item.metadata + ' | ' + item.weight + 'g</span>' +
                '</div>' +
                '<div style="display:flex; align-items:center;">' +
                '<span class="tee-item-price">£' + formatter.format(item.offer) + '</span>' +
                '<button type="button" class="tee-remove-item" data-index="' + index + '">×</button>' +
                '</div>' +
                '</div>');
            container.append(el);
        });

        $('#tee-agreement-total').text('£' + formatter.format(total));
        $('#tee-agreement-weight').text((totalWeight / 1000).toFixed(2));

        if (agreementList.length > 0) {
            $('#tee-agreement-list-wrap').fadeIn();
        } else {
            $('#tee-agreement-list-wrap').hide();
            $('#tee-sticky-bar').hide();
        }

        $('.tee-remove-item').on('click', function () {
            var idx = $(this).data('index');
            agreementList.splice(idx, 1);
            renderAgreementList();
            updateStickyBar(0);
        });
    }

    // 5. Final Add to Basket
    $('#tee-add-all-to-cart').on('click', function () {
        if (agreementList.length === 0) return;

        var totalWeight = 0;
        agreementList.forEach(function (i) {
            totalWeight += parseFloat(i.weight) || 0;
        });

        if (totalWeight > 18000) {
            alert('Cannot checkout: Total weight exceeds 18KG. Please remove some items.');
            return;
        }

        $(this).prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Adding all to basket...');

        // Serial process adding to cart since WC AJAX isn't great with parallel identical product adds
        addBatchToCart(0);
    });

    function addBatchToCart(index) {
        if (index >= agreementList.length) {
            window.location.href = tee_vars.cart_url || '/cart/';
            return;
        }

        var item = agreementList[index];
        $.ajax({
            url: tee_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'tee_add_to_cart',
                nonce: tee_vars.nonce,
                product_id: tee_vars.product_id,
                price: item.offer,
                metadata: {
                    'Set': item.name + ' (' + item.id + ')',
                    'Details': item.metadata,
                    'Weight': item.weight + 'g',
                    'image': item.image
                }
            },
            success: function () {
                addBatchToCart(index + 1);
            }
        });
    }
});

