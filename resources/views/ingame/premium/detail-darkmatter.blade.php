<div class="officers200 darkMatter"></div>
<div id="content">
    <h2>{{ __('t_ingame.premium.dark_matter_title') }}</h2>
    <a id="close" class="close_details" href="javascript:void(0);"></a>
    <span class="level dmLevel">
        <span class="undermark">{{ number_format($darkMatter, 0, ',', '.') }} {{ __('t_ingame.premium.dark_matter_title') }}</span>
    </span>
    <br class="clearfloat">
    <div id="wrapper">
        <div id="features">
            <p>
                {{ __('t_ingame.premium.dark_matter_description') }}<br>
                <b>{{ __('t_ingame.premium.dark_matter_safe') }}</b>
            </p>
            <div class="build-it_wrap">
                <a class="build-it overlay" href="{{ route('shop.index') }}">
                    <span>{{ __('t_ingame.premium.buy_dark_matter') }}</span>
                </a>
            </div>
            <br class="clearfloat">
        </div>
    </div>
</div>
<br clear="all">
<div id="description">
    <div class="benefits">{{ __('t_ingame.premium.advantages_label') }}:</div>
    <div class="benefitlist">
        {{ __('t_ingame.premium.dark_matter_advantages') }}
    </div>
</div>
