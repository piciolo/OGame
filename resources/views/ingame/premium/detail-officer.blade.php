<div class="image">
    <div class="officers200 {{ $officerKey === 'all_officers' ? 'allOfficers' : $officerKey }}"></div>
</div>
<div id="content">
    <h2>{{ __('t_ingame.premium.officer_' . $officerKey . '_title') }}</h2>
    <span class="level">
        @if($isActive && $expiresAt)
            <span class="overmark">
                {{ __('t_ingame.premium.active_until', ['date' => $expiresAt->format('d.m.Y H:i')]) }}
            </span>
        @else
            <span class="undermark">{{ __('t_ingame.premium.not_active') }}</span>
        @endif
    </span>
    <div id="wrapper">
        <p>{{ __('t_ingame.premium.officer_' . $officerKey . '_description') }}</p>

        <div class="purchase_options">
            @foreach($costs as $days => $cost)
                <div class="purchase_option">
                    <button
                        class="btn_blue officer_purchase_btn {{ $darkMatter < $cost ? 'insufficient' : '' }}"
                        data-type="{{ $typeId }}"
                        data-duration="{{ $days }}"
                        data-cost="{{ $cost }}"
                        {{ $darkMatter < $cost ? 'disabled' : '' }}
                    >
                        <span>
                            {{ $days }} {{ __('t_ingame.premium.days') }}
                            - {{ number_format($cost, 0, ',', '.') }} {{ __('t_ingame.premium.dm') }}
                        </span>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
    <a href="javascript:void(0);" class="close_details">x</a>
</div>

<script>
$(function() {
    $('.officer_purchase_btn').off('click').on('click', function() {
        var btn = $(this);
        if (btn.prop('disabled')) return;

        var typeId = btn.data('type');
        var duration = btn.data('duration');
        var cost = btn.data('cost');

        if (!confirm('{{ __('t_ingame.premium.confirm_purchase') }}'.replace(':cost', cost.toLocaleString()).replace(':days', duration))) {
            return;
        }

        btn.prop('disabled', true);

        $.ajax({
            url: '{{ route('premium.purchase') }}',
            type: 'POST',
            data: {
                type: typeId,
                duration: duration,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Reload the detail panel to show updated status
                    loadDetails(typeId);
                    // Update dark matter display in header
                    if (typeof response.newBalance !== 'undefined') {
                        $('#currentdm, .dark_matter_amount').text(response.newBalance.toLocaleString('de-DE'));
                    }
                    // Reload page to update officer status icons
                    location.reload();
                } else {
                    fadeBox(response.error || 'Purchase failed.', true);
                }
            },
            error: function() {
                fadeBox('{{ __('t_ingame.premium.purchase_error') }}', true);
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });
});
</script>
