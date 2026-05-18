<div class="buddyRequest" data-title="{{ $playerName }}">
    <div class="ajaxContent">
        <form action="{{ route('buddies.sendrequest') }}" method="post" id="buddyRequestForm">
            @csrf
            <input type="hidden" name="receiver_id" value="{{ $playerId }}">
            <div class="editor_wrap">
                <div>
                    <textarea name="message" class="buddy_request_textarea"></textarea>
                </div>
            </div>
            <input type="submit" class="btn_blue float_right" value="{{ __('t_buddies.ui.send') }}">
        </form>
    </div>
</div>

<script type="text/javascript">
    initBuddyRequestForm();
    initBBCodeEditor(locaKeys, itemNames, false, '.buddy_request_textarea', 5000, true);

    // Override submit to handle JSON response
    $('#buddyRequestForm').off('submit').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('input[type="submit"]');
        $btn.prop('disabled', true);

        $.post($form.attr('action'), $form.serialize())
            .done(function (data) {
                if (data.success) {
                    fadeBox(data.message, false);
                    $form.closest('.ui-dialog').find('.ui-icon-closethick').trigger('click');
                } else {
                    fadeBox(data.message, true);
                    $btn.prop('disabled', false);
                }
            })
            .fail(function () {
                fadeBox('{{ __('t_buddies.error.send_request_failed') }}', true);
                $btn.prop('disabled', false);
            });
    });
</script>
