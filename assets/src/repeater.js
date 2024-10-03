jQuery(document).ready(function ($) {
    var count = repeaterData.count;
    var fieldId = repeaterData.field_id;
    var jsCode = repeaterData.js_code;
    var indexPlaceholder = repeaterData.index_placeholder;
    var itemNumberPlaceholder = repeaterData.item_number_placeholder;

    $("#js-" + fieldId + "-add").on("click", function () {
        var repeater = jsCode
            .replace(new RegExp(indexPlaceholder, 'g'), count)
            .replace(new RegExp(itemNumberPlaceholder, 'g'), count + 1);

        $("#js-" + fieldId + "-repeated-blocks").append(repeater);
        count++;
        return false;
    });
});