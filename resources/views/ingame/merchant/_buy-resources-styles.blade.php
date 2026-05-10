{{--
    Inline styles for the "Procura risorse" tab. Inlined so the partial keeps
    working when AJAX-swapped into #contentWrapper without a full asset reload.
--}}
<style>
    /* Resource package icons — official OGame "Procura risorse" sprites,
       self-hosted at /img/merchant/items/. The "all" bundle reuses the
       metal icon (matches OGame ufficiale: same hash on the live page). */
    #tabs-buyResource .resource_img {
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
    }
    #tabs-buyResource .resource_img.resource_img_metal,
    #tabs-buyResource .resource_img.resource_img_allLocalResources {
        background-image: url('/img/merchant/items/metal.png');
    }
    #tabs-buyResource .resource_img.resource_img_crystal {
        background-image: url('/img/merchant/items/crystal.png');
    }
    #tabs-buyResource .resource_img.resource_img_deuterium {
        background-image: url('/img/merchant/items/deuterium.png');
    }

    /* Editable amount input — replicate the OGame ufficiale style provided in
       the issue: red text, light-blue inset background, narrow 75x16 box. */
    #tabs-buyResource .resource_name input {
        color: #D43635;
        text-align: right;
        background-color: #B3C3CB;
        border: 1px solid #668599;
        border-bottom-color: #D3D9DE;
        border-radius: 3px;
        box-shadow: inset 0 1px 3px 0 #454F54;
        padding: 2px 5px;
        -webkit-appearance: none;
        margin: 0;
        width: 75px;
        font-size: 11px;
        line-height: 10px;
        box-sizing: border-box;
        height: 16px;
    }
    #tabs-buyResource .resource_name input.overmark { color: #D43635; }
</style>
