{{--
    Click handler for the "Buy Commander" upsell shown in resource/facility build
    slots when the player has no Commander officer and the queue is at its 1-item
    cap. Reuses the legacy errorBoxDecision() jQuery UI dialog already loaded by
    the ingame layout (used by inventory.js, trader.js, etc.).
--}}
<script type="text/javascript">
    (function () {
        // Title key reused from ajax_object.error to stay consistent with the other
        // "no Commander → buy Commander" dialog at ingame/ajax/object.blade.php:368.
        var dialogTitle    = @json(__('t_ingame.ajax_object.error'));
        var dialogYesLabel = @json(__('t_ingame.shared.yes'));
        var dialogNoLabel  = @json(__('t_ingame.shared.no'));

        $(document).off('click.commanderCta', '.build-it_premium')
                   .on('click.commanderCta', '.build-it_premium', function (e) {
            e.preventDefault();
            var $el = $(this);
            var url = $el.data('url');
            var question = $el.data('question') || '';
            errorBoxDecision(dialogTitle, question, dialogYesLabel, dialogNoLabel, function () {
                if (url) { window.location.href = url; }
            });
        });
    })();
</script>
