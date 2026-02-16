jQuery(document).ready(function ($) {
    $('.tee-color-picker').wpColorPicker();

    // Tab Switching Logic
    $('.nav-tab-wrapper a').on('click', function (e) {
        e.preventDefault();
        var target = $(this).attr('href');

        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.tee-tab-content').hide();
        $(target).show();

        // Update URL hash
        if (history.replaceState) {
            history.replaceState(null, null, target);
        } else {
            location.hash = target;
        }
    });

    // Check URL hash for active tab on load
    var hash = window.location.hash;
    if (hash && $('.nav-tab-wrapper a[href="' + hash + '"]').length > 0) {
        $('.nav-tab-wrapper a[href="' + hash + '"]').trigger('click');
    }

    var rulesContainer = $('#tee-rules-tbody');
    var addRuleBtn = $('#add-rule');
    // ...

    addRuleBtn.on('click', function () {
        var index = rulesContainer.find('.rule-row').length;
        var row = $('<tr class="rule-row">' +
            '<td><input type="number" step="0.01" name="tee_pricing_rules[' + index + '][min]" value="0"></td>' +
            '<td><input type="number" step="0.01" name="tee_pricing_rules[' + index + '][max]" value="0"></td>' +
            '<td><input type="number" step="0.1" name="tee_pricing_rules[' + index + '][new_sealed]" value="70">%</td>' +
            '<td><input type="number" step="0.1" name="tee_pricing_rules[' + index + '][new_open]" value="55">%</td>' +
            '<td><input type="number" step="0.1" name="tee_pricing_rules[' + index + '][used]" value="50">%</td>' +
            '<td><button type="button" class="button remove-rule">Remove</button></td>' +
            '</tr>');
        rulesContainer.append(row);
    });

    rulesContainer.on('click', '.remove-rule', function () {
        $(this).closest('.rule-row').remove();
        reindexRules();
    });

    function reindexRules() {
        rulesContainer.find('.rule-row').each(function (i) {
            $(this).find('input').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/, '[' + i + ']');
                    $(this).attr('name', name);
                }
            });
        });
    }
});
