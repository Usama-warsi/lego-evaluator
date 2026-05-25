jQuery(document).ready(function ($) {
    // State Management
    var setData = null;
    var agreementList = [];
    var userInputs = {};

    // Acceptance helpers — read toggles localised from PHP
    var _acc = (tee_vars.acceptance) ? tee_vars.acceptance : { new_sealed: true, new_open: true, used_100: true, used_95: true, used_mixed: true };

    function getDefaultCondition() {
        if (_acc.used_100 || _acc.used_95 || _acc.used_mixed) return 'used';
        if (_acc.new_sealed || _acc.new_open) return 'new';
        return 'used';
    }
    function getDefaultCompletion() {
        if (_acc.used_100)   return '100';
        if (_acc.used_95)    return '95';
        if (_acc.used_mixed) return 'less';
        return '100';
    }
    function getDefaultSeals() {
        return !!_acc.new_sealed;
    }
    function initConditionCards() {
        // Both condition cards are always visible; disabled notices appear inline within each flow
    }

    // Initialization
    function resetSetState() {
        userInputs = {
            condition: getDefaultCondition(),
            seals_intact: getDefaultSeals(),
            box_condition: 'like_new',
            is_complete: true,
            completion_level: getDefaultCompletion(),
            is_built: true,
            weight: 0,
            has_box: true,
            has_instructions: true,
            missing_minifigs: {}
        };
    }
    resetSetState();
    initConditionCards();

    // 1. Search Logic

    // --- Name search (Rebrickable) ---
    var _nameSearchTimer = null;

    $('#tee-set-number').on('input', function () {
        var q = $(this).val().trim();
        clearTimeout(_nameSearchTimer);
        $('#tee-name-search-results').hide().empty();
        if (q.length < 3) return;
        // Pure set numbers (digits + optional dash) go straight to BrickLink via the button
        if (/^\d[\d-]*$/.test(q)) return;
        _nameSearchTimer = setTimeout(function () { doNameSearch(q); }, 450);
    });

    function doNameSearch(q) {
        var $grid = $('#tee-name-search-results');
        $grid.html('<p style="color:#606266;font-size:13px;margin:0;">Searching…</p>').show();
        $.ajax({
            url: tee_vars.ajax_url,
            type: 'POST',
            data: { action: 'tee_search_sets', nonce: tee_vars.nonce, query: q },
            success: function (response) {
                if (response.success && response.data.length > 0) {
                    renderNameSearchGrid(response.data);
                } else {
                    $grid.html('<p style="color:#606266;font-size:13px;margin:0;">No sets found — try a different name or enter the set number directly.</p>');
                }
            },
            error: function () { $grid.hide(); }
        });
    }

    function renderNameSearchGrid(sets) {
        var $grid = $('#tee-name-search-results').empty();
        var $cards = $('<div class="tee-name-search-grid"></div>');
        $.each(sets, function (i, set) {
            var img = set.image || '';
            var $card = $(
                '<div class="tee-name-search-card" tabindex="0">' +
                (img ? '<img src="' + img + '" alt="' + set.name + '">' : '<div class="tee-name-search-no-img"></div>') +
                '<div class="tee-name-search-body">' +
                    '<strong class="tee-name-search-title">' + set.name + '</strong>' +
                    '<span class="tee-name-search-meta">#' + set.set_num + ' &nbsp;·&nbsp; ' + set.year + ' &nbsp;·&nbsp; ' + set.parts + ' pcs</span>' +
                '</div>' +
                '</div>'
            );
            $card.on('click keypress', function (e) {
                if (e.type === 'keypress' && e.which !== 13) return;
                $('#tee-set-number').val(set.set_num);
                $grid.hide().empty();
                searchSet();
            });
            $cards.append($card);
        });
        $grid.append($cards).show();
    }

    // --- Direct set-number search ---
    $('#tee-search-set').on('click', function () {
        var q = $('#tee-set-number').val().trim();
        if (!q) return;
        // Only send to BrickLink when input is a pure set number (digits + optional dash)
        if (/^\d[\d-]*$/.test(q)) {
            searchSet();
        } else {
            doNameSearch(q);
        }
    });

    $('#tee-set-number').on('keypress', function (e) {
        if (e.which !== 13) return;
        var q = $(this).val().trim();
        if (!q) return;
        if (/^\d[\d-]*$/.test(q)) {
            searchSet();
        } else {
            doNameSearch(q);
        }
    });

    function searchSet() {
        var set_number = $('#tee-set-number').val().trim();
        if (!set_number) return;
        $('#tee-name-search-results').hide().empty();

        $('#tee-search-error').hide();
        $('#tee-loading').show();
        $('#tee-search-set').prop('disabled', true);
        $('#tee-result-ui, #tee-main-ui, #tee-set-preview, #tee-minifigs-ui').hide();

        resetSetState();
        $('#tee-minifigs-list').empty().removeData('rendered-set');
        $('.tee-cond-card').removeClass('active');
        $('.tee-cond-card[data-cond="' + getDefaultCondition() + '"]').addClass('active');

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
        var noticeHtml =
            '<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;' +
            'padding:13px 16px;margin:10px 0 14px;font-size:13.5px;color:#78350f;line-height:1.5;">' +
            '<strong>We\'re not currently accepting incomplete sets for individual evaluations.</strong><br>' +
            'You can still sell your LEGO by weight — enter the weight below and see our Mixed LEGO offer.' +
            '</div>';

        var sealOpts = [
            { label: 'Yes', value: true,  desc: 'Original tape/seals unbroken' },
            { label: 'No',  value: false, desc: 'Seals cut or box opened' }
        ];

        // Determine if the currently selected seal option is disabled
        var sealDisabled = (userInputs.seals_intact === true  && !_acc.new_sealed) ||
                           (userInputs.seals_intact === false && !_acc.new_open);

        container.append('<label class="tee-question-label">Are all box seals intact?</label>');
        container.append(renderSwatches('seals_intact', sealOpts, userInputs.seals_intact));

        if (sealDisabled) {
            container.append(noticeHtml);
            container.append($('<div class="tee-question-item">' +
                '<label class="tee-question-label">Enter the weight of the set (grams)</label>' +
                '<input type="number" id="tee-weight-input" class="tee-input" value="' + userInputs.weight + '">' +
                '</div>'));
        } else if (userInputs.seals_intact) {
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
                container.append($('<div class="tee-question-item">' +
                    '<label class="tee-question-label">Enter weight of all bags present (grams)</label>' +
                    '<input type="number" id="tee-weight-input" class="tee-input" value="' + userInputs.weight + '">' +
                    '</div>'));
            }
        }

        bindDynamicEvents();
    }

    function renderUsedFlow(container) {
        var noticeHtml =
            '<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;' +
            'padding:13px 16px;margin:10px 0 14px;font-size:13.5px;color:#78350f;line-height:1.5;">' +
            '<strong>We\'re not currently accepting incomplete sets for individual evaluations.</strong><br>' +
            'You can still sell your LEGO by weight — enter the weight below and see our Mixed LEGO offer.' +
            '</div>';

        // All 3 completion options always available
        var compOpts = [
            { label: '100% Complete', value: '100',  desc: 'Includes all minifigures' },
            { label: 'Over 95%',      value: '95',   desc: 'Missing minor parts' },
            { label: 'Under 95%',     value: 'less', desc: 'Incomplete/Mixed' }
        ];

        // FIX: jQuery auto-parses numeric data-value attributes as integers (e.g. 95, 100).
        // Use String() on both sides so indexOf('95') !== indexOf(95) doesn't reset the selection.
        var completionStr = String(userInputs.completion_level);

        // Determine if the currently selected option is disabled
        var compDisabled = (completionStr === '100' && !_acc.used_100) ||
                           (completionStr === '95'  && !_acc.used_95);

        if (!compDisabled) {
            // Only reset to first valid option when no disabled-notice is being shown
            var validVals = compOpts.map(function(o) { return String(o.value); });
            if (validVals.length > 0 && validVals.indexOf(completionStr) === -1) {
                userInputs.completion_level = compOpts[0].value;
                completionStr = String(compOpts[0].value);
            }
        }

        // Show estimated-price notice if BrickLink had no used data
        if (setData && setData.prices && setData.prices.used_price_estimated) {
            container.append('<p class="tee-notice" style="font-size:0.85em;color:#f59e0b;margin:0 0 8px;">No used sold data found on BrickLink — used price is estimated at ' + Math.round((setData.prices.used_avg / setData.prices.new_avg) * 100) + '% of new price.</p>');
        }

        container.append('<label class="tee-question-label">How complete is the set?</label>');
        container.append(renderSwatches('completion_level', compOpts, completionStr));

        if (compDisabled) {
            // 100% or Over 95% selected but disabled — show notice + weight input
            container.append(noticeHtml);
            container.append($('<div class="tee-question-item">' +
                '<label class="tee-question-label">Enter the weight of the set (grams)</label>' +
                '<input type="number" id="tee-weight-input" class="tee-input" value="' + userInputs.weight + '">' +
                '</div>'));
        } else if (completionStr !== 'less') {
            // 100% or Over 95% selected and enabled — show built/details questions
            container.append('<label class="tee-question-label">Is the set built up?</label>');
            container.append(renderSwatches('is_built', [
                { label: 'Yes', value: true, desc: 'Currently assembled' },
                { label: 'No', value: false, desc: 'Partially or fully dismantled' }
            ], userInputs.is_built));

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
            // Under 95% selected — always show weight input; add notice if toggle is off
            if (!_acc.used_mixed) {
                container.append(noticeHtml);
            }
            container.append($('<div class="tee-question-item">' +
                '<label class="tee-question-label">Enter the weight of the set (grams)</label>' +
                '<input type="number" id="tee-weight-input" class="tee-input" value="' + userInputs.weight + '">' +
                '</div>'));
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
            else if (val === false || val === 'false') val = false;
            // jQuery auto-parses numeric data-value attributes as integers (e.g. data-value="95" → 95).
            // Normalise back to string so comparisons like completion_level === '95' stay consistent.
            else if (typeof val === 'number') val = String(val);

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
    var _offerXhr = null; // track in-flight offer request so we can abort stale ones

    function calculateOffer() {
        if (!setData) return;

        // Abort any previous in-flight request — prevents a slow response for an
        // old selection (e.g. Under 95%) from overwriting the result banner after
        // the user has already moved to a new selection (e.g. 100% Complete).
        if (_offerXhr) { _offerXhr.abort(); _offerXhr = null; }

        $('#tee-final-price').html('<span class="tee-calc-loader"></span>');
        $('#tee-accept-set').prop('disabled', true);

        _offerXhr = $.ajax({
            url: tee_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'tee_calculate_offer',
                nonce: tee_vars.nonce,
                set_data: setData,
                user_inputs: userInputs
            },
            success: function (response) {
                _offerXhr = null;
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
            if (userInputs.seals_intact) {
                var boxLabel = userInputs.box_condition.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                tagsContainer.append('<span class="tee-tag">Box: ' + boxLabel + '</span>');
            } else {
                tagsContainer.append('<span class="tee-tag">' + (userInputs.is_complete ? 'Complete' : 'Incomplete') + '</span>');
                if (!userInputs.is_complete && userInputs.weight > 0) {
                    tagsContainer.append('<span class="tee-tag">' + userInputs.weight + 'g</span>');
                }
            }
        } else {
            var compLabel = String(userInputs.completion_level) === '100' ? '100% Complete' : (String(userInputs.completion_level) === '95' ? 'Over 95%' : 'Under 95%');
            tagsContainer.append('<span class="tee-tag">' + compLabel + '</span>');
            
            if (userInputs.completion_level !== 'less') {
                tagsContainer.append('<span class="tee-tag">' + (userInputs.is_built ? 'Built' : 'Dismantled') + '</span>');
                if (userInputs.has_box && userInputs.has_instructions) tagsContainer.append('<span class="tee-tag">Box & Ins</span>');
                else if (userInputs.has_box) tagsContainer.append('<span class="tee-tag">Box Only</span>');
                else if (userInputs.has_instructions) tagsContainer.append('<span class="tee-tag">Ins Only</span>');
                else tagsContainer.append('<span class="tee-tag">No Box/Ins</span>');
            } else if (userInputs.weight > 0) {
                tagsContainer.append('<span class="tee-tag">' + userInputs.weight + 'g</span>');
            }
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
            if (userInputs.seals_intact) {
                var boxLabel = userInputs.box_condition.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                parts.push('Box: ' + boxLabel);
            } else {
                parts.push(userInputs.is_complete ? 'Complete' : 'Incomplete');
            }
        } else {
            var compLabel = String(userInputs.completion_level) === '100' ? '100% Complete' : (String(userInputs.completion_level) === '95' ? 'Over 95%' : 'Under 95%');
            parts.push('Completion: ' + compLabel);
            if (userInputs.completion_level !== 'less') {
                parts.push(userInputs.is_built ? 'Built' : 'Dismantled');
                if (userInputs.has_box && userInputs.has_instructions) parts.push('Box & Ins');
                else if (userInputs.has_box) parts.push('Box Only');
                else if (userInputs.has_instructions) parts.push('Instructions Only');
                else parts.push('No Box/Instructions');
            }
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

