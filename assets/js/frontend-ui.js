jQuery(document).ready(function ($) {
    var setData = null;
    var userInputs = {
        condition: 'used',
        box_condition: 'good',
        is_complete: true,
        has_minifigs: true,
        is_assembled: false,
        missing_minifigs: []
    };

    // Step 1: Search Set
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
        $('#tee-result-ui, #tee-minifigs-ui, #tee-main-ui, #tee-set-preview').hide();

        // Reset Minifig rendering state
        $('#tee-minifigs-list').empty().removeData('rendered-set');

        // Reset user inputs to default
        userInputs = {
            condition: 'used',
            box_condition: 'good',
            is_complete: true,
            has_minifigs: true,
            is_assembled: false,
            missing_minifigs: []
        };

        // Reset UI Toggles
        $('#tee-check-parts, #tee-check-minifigs, #tee-check-box, #tee-check-instructions').prop('checked', true);
        $('#tee-check-assembled').prop('checked', false);

        // Reset Condition Cards
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

                    // Show Set Preview
                    $('#tee-set-image-thumb').attr('src', setData.image);
                    $('#tee-set-name-preview').text(setData.name + ' (#' + setData.id + ')');
                    $('#tee-set-preview').fadeIn();

                    $('#tee-main-ui').fadeIn();
                    // Initialize with default 'used' logic or keep current
                    updateLogicAndCalculate();
                } else {
                    $('#tee-search-error').text(response.data).show();
                }
            }
        });
    }

    // Condition Card Selection
    $('.tee-cond-card').on('click', function () {
        $('.tee-cond-card').removeClass('active');
        $(this).addClass('active');
        userInputs.condition = $(this).data('cond');
        updateLogicAndCalculate();
    });

    // Toggle Switches
    $('.tee-switch input').on('change', function () {
        updateLogicAndCalculate();
    });

    function updateLogicAndCalculate() {
        if (!setData) return;

        // Sync inputs from UI
        userInputs.is_complete = $('#tee-check-parts').is(':checked');
        userInputs.has_minifigs = $('#tee-check-minifigs').is(':checked');
        userInputs.has_box = $('#tee-check-box').is(':checked');
        userInputs.has_instructions = $('#tee-check-instructions').is(':checked');
        userInputs.is_assembled = $('#tee-check-assembled').is(':checked');

        // Show/Hide relevant rows based on condition
        if (userInputs.condition === 'new_sealed') {
            $('#row-parts, #row-minifigs, #row-instructions').hide();
            $('#row-assembled').hide();
            $('#row-box strong').text('Box is in Good Condition');
            $('#row-box p').text('Uncheck if box is damaged');
            userInputs.box_condition = userInputs.has_box ? 'good' : 'damaged';
        } else {
            $('#row-parts, #row-minifigs, #row-instructions').show();
            $('#row-box strong').text('Has Original Box');
            $('#row-box p').text('Original packaging included');

            if (userInputs.condition === 'used') {
                $('#row-assembled').show();
            } else {
                $('#row-assembled').hide();
                $('#tee-check-assembled').prop('checked', false);
                userInputs.is_assembled = false;
            }
        }

        // Show minifigs checklist if not 'new_sealed' and the "Has All Minifigures" toggle is OFF
        if (userInputs.condition !== 'new_sealed' && !userInputs.has_minifigs && Object.keys(setData.minifigs_data).length > 0) {
            renderMinifigs();
            $('#tee-minifigs-ui').fadeIn();
        } else {
            $('#tee-minifigs-ui').fadeOut();
            if (userInputs.has_minifigs) {
                userInputs.missing_minifigs = []; // Reset if they check the toggle back
            }
        }

        calculateOffer();
    }

    function renderMinifigs() {
        var container = $('#tee-minifigs-list');
        // Check if we already rendered THIS set
        if (container.data('rendered-set') === setData.id) return;

        container.empty();
        container.data('rendered-set', setData.id);
        $.each(setData.minifigs_data, function (id, minifig) {
            var item = $('<div class="minifig-item">' +
                '<img src="' + minifig.thumbnail + '" alt="' + minifig.name + '">' +
                '<strong>' + minifig.name + '</strong><br>' +
                '<label><input type="checkbox" class="minifig-check" value="' + id + '" checked> I have this</label>' +
                '</div>');
            container.append(item);
        });

        $('.minifig-check').on('change', function () {
            userInputs.missing_minifigs = [];
            $('.minifig-check:not(:checked)').each(function () {
                userInputs.missing_minifigs.push($(this).val());
            });
            calculateOffer();
        });
    }

    function calculateOffer() {
        if (!setData) return;

        // Show loader
        $('#tee-final-price').html('<span class="tee-calc-loader"></span>');
        $('#tee-add-to-cart').prop('disabled', true);

        // Map UI inputs to what the backend expects
        var payload_inputs = $.extend({}, userInputs);

        // Final completeness check: if they have the toggle ON, it's complete. 
        // If OFF, it's only complete if they checked every box in the list.
        var hasAllMinifigs = userInputs.has_minifigs || (userInputs.missing_minifigs.length === 0);

        payload_inputs.is_complete = userInputs.is_complete;
        payload_inputs.has_minifigs = hasAllMinifigs;

        $.ajax({
            url: tee_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'tee_calculate_offer',
                nonce: tee_vars.nonce,
                set_data: setData,
                user_inputs: payload_inputs
            },
            success: function (response) {
                $('#tee-add-to-cart').prop('disabled', false);
                if (response.success) {
                    updateResultBanner(response.data);
                }
            }
        });
    }

    function updateResultBanner(data) {
        $('#tee-res-name').text(setData.name);
        $('#tee-res-id').text('Set #' + setData.id);
        $('#tee-final-price').text('£' + data.offer);

        // Tags
        var tagsContainer = $('#tee-res-tags');
        tagsContainer.empty();

        var condLabels = { 'new_sealed': 'New Sealed', 'new_open': 'New Opened', 'used': 'Used' };
        tagsContainer.append('<span class="tee-tag">' + condLabels[userInputs.condition] + '</span>');

        var totalMinifigs = Object.keys(setData.minifigs_data).length;
        var hasAllMinifigs = totalMinifigs === 0 || userInputs.missing_minifigs.length === 0;

        if (userInputs.is_complete && hasAllMinifigs) tagsContainer.append('<span class="tee-tag">All Parts</span>');
        if (hasAllMinifigs && totalMinifigs > 0) tagsContainer.append('<span class="tee-tag">Minifigures</span>');
        if (userInputs.has_box) tagsContainer.append('<span class="tee-tag">Box</span>');
        if (userInputs.has_instructions) tagsContainer.append('<span class="tee-tag">Instructions</span>');
        if (userInputs.condition === 'used' && userInputs.is_assembled) tagsContainer.append('<span class="tee-tag">Assembled</span>');

        // Calc text
        var pct = getTierPercentage();

        $('#tee-result-ui').fadeIn();
    }

    function getTierPercentage() {
        // Simplified frontend pct display (ideally should come from backend response)
        // This is just for the UI text "x 40%"
        return 40;
    }

    // Final Add to Cart
    $('#tee-add-to-cart').on('click', function () {
        var priceStr = $('#tee-final-price').text().replace('£', '');
        var price = parseFloat(priceStr);
        var product_id = tee_vars.product_id;

        if (!product_id || product_id == 0) {
            alert('Error: No evaluation product selected in settings.');
            return;
        }

        if (price <= 0) {
            alert('Error: Evaluation result is £0.00.');
            return;
        }

        $(this).prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Adding...');

        $.ajax({
            url: tee_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'tee_add_to_cart',
                nonce: tee_vars.nonce,
                product_id: product_id,
                price: price,
                metadata: {
                    'Set': setData.name + ' (' + setData.id + ')',
                    'Condition': userInputs.condition.replace('_', ' '),
                    'Details': (function () {
                        var parts = [];
                        if (!userInputs.is_complete) parts.push('Missing Parts');
                        if (!userInputs.has_box) parts.push('No Box');
                        if (!userInputs.has_instructions) parts.push('No Instructions');

                        var totalMinifigs = Object.keys(setData.minifigs_data).length;
                        var hasAllMinifigs = totalMinifigs === 0 || userInputs.missing_minifigs.length === 0;

                        if (userInputs.missing_minifigs.length > 0) {
                            var names = [];
                            $.each(userInputs.missing_minifigs, function (i, id) {
                                if (setData.minifigs_data[id]) {
                                    names.push(setData.minifigs_data[id].name);
                                }
                            });
                            parts.push('Missing Minifigs: ' + names.join(', '));
                        } else if (!hasAllMinifigs) {
                            parts.push('Missing All Minifigures');
                        }

                        return parts.join(' | ') || 'Complete';
                    })(),
                    'Weight': setData.weight + 'g',
                    'image': setData.image
                }
            },
            success: function (response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data);
                    $('#tee-add-to-cart').prop('disabled', false).html('<span class="dashicons dashicons-cart"></span> Add to Cart');
                }
            }
        });
    });
});
