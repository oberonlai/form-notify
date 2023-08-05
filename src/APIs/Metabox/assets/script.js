(function ($) {
    'use strict';

    $(document).ready(function () {

        var image_frame;

        $('.form-notify-box__field-container').on('click', '.js-form-notify-box-image-upload-button', function (e) {
            e.preventDefault();

            image_frame = wp.media.frames.image_frame = wp.media({library: {type: 'image'}});
            image_frame.open();

            var id = $(this).data('hidden-input').replace(/(\[|\])/g, '\\$1');
            console.log(id);

            image_frame.on('select', function () {
                var attachment = image_frame.state().get('selection').first().toJSON();
                console.log(id);
                $('#image-' + id).val(attachment.url);

                $('#js-' + id + '-image-preview').removeClass('is-hidden').attr('src', attachment.url);

                $('.js-form-notify-box-image-upload-button').text('Change Image');

                $('#' + id).css({background: 'red'});
            });

            image_frame.open();

        });

        $('.form-notify-box__field-container').on('click', '.form-notify-box-repeated-header', function () {
            $(this).siblings('.form-notify-box__repeated-content').toggleClass('is-hidden');
        });

        $('.form-notify-box__repeated-blocks').on('click', '.form-notify-box__remove', function () {
            $(this).closest('.form-notify-box__repeated').remove();
            return false;
        });

        $('.form-notify-box__repeated-blocks').sortable({
            opacity: 0.6,
            revert: true,
            cursor: 'move',
            handle: '.js-form-notify-box-sort'
        });


        // receiver field display
        const hiddenReceiverField = val => ['by_order'].includes(val)

        const receiverFieldDisplay = selector => {
            const receiverField = $('.receiver-field');
            !hiddenReceiverField(selector.val()) ? receiverField.show() : receiverField.hide();

            $('#js-form_notify_action_module-repeated-blocks .form-notify-box__repeated select').on('change', function () {
                const parent = $(this).parent().parent().parent();
                $(this).val() === 'notify' ? parent.find('div.receiver-field').hide() : !hiddenReceiverField(selector.val()) ? parent.find('div.receiver-field').show() : parent.find('div.receiver-field').hide();
            });
        };

        const triggerEvent = $('select[name=form_notify_trigger_event]');
        const updateReceiverFieldDisplay = () => receiverFieldDisplay(triggerEvent);
        updateReceiverFieldDisplay();

        $('div.receiver-field').parent().parent().each(function () {
            if ($(this).find('.form_notify_action_module_type select').val() === 'notify' || $('select[name="form_notify_trigger_event"]').val() === 'by_order') {
                $(this).find('div.receiver-field').hide()
            } else {
                $(this).find('div.receiver-field').show()
            }
        });

        triggerEvent.on('change', () => updateReceiverFieldDisplay());
        $('#js-form_notify_action_module-add').click(() => updateReceiverFieldDisplay());

        // show field by select value for repeater
        function show_field_by_select_value(container, repeater) {
            container.each(function () {
                const that = $(this);
                const showBy = that.find('[data-show-by]').data('show-by');
                let selectObj = (repeater) ? that.find('.' + showBy).find('select') : $(`select[name="${showBy}"]`);
                that.find(`[data-show-value]`).parent().parent().hide();
                selectObj.on('change', function () {
                    let showValue = $(this).val()
                    that.find(`[data-show-by="${showBy}"]`).parent().parent().hide();
                    that.find(`[data-show-value="${showValue}"]`).parent().parent().show();
                })
                if (repeater) {
                    that.find(`[data-show-value="${that.find('.' + showBy).find('select').val()}"]`).parent().parent().show();
                } else {
                    that.find(`[data-show-value="${$(`select[name="${showBy}"]`).val()}"]`).parent().parent().show();
                    that.find(`[data-show-value="${$(`input[name="${showBy}"]`).val()}"]`).parent().parent().show();
                    that.find(`[data-show-value="${$(`${showBy}`).val()}"]`).parent().parent().show();
                }
            })
        }

        show_field_by_select_value($('.form-notify-box > div'));
        show_field_by_select_value($('.data-show'));
        show_field_by_select_value($('.form-notify-box').find('div[class$="__repeated"]'), true);
        $('#js-form_notify_trigger_rule-add,#js-form_notify_action_module-add').on('click', function () {
            show_field_by_select_value($('.form-notify-box').find('div[class$="__repeated"]'), true);
        })

    });

})(jQuery);