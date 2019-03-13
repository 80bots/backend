<ol class="align-items-center sa-page-ribbon breadcrumb" aria-label="breadcrumb" role="navigation">
    <li><span id="refresh" class="btn sa-ribbon-btn sa-theme-btn" data-action="resetWidgets"><i class="fa fa-refresh"></i></span></li>
    <li class="breadcrumb-item"><a href="javascript:void(0)">App Views</a></li>
    <li class="breadcrumb-item"><a href="profile.html">Profile</a></li>

</ol>
<div class="sa-theme-settings">
    <a href="javascript:void(0)" class="sa-theme-settings-toggle" onclick="SAtoggleClass(this, '.sa-theme-settings', 'active')"><span class="fa fa-cogs fa-spin"></span></a>
    <h4>Layout Options</h4>
    <ul class="list-unstyled">
        <li>
            <label class="vcheck">
                <input type="checkbox" id="SAFixedHeader" data-add-class="sa-fixed-header" data-remove-class="sa-fixed-header sa-fixed-navigation sa-fixed-ribbon " onchange="SASetClass(this)">
                <span>Fixed Header</span>
            </label>
        </li>
        <li>
            <label class="vcheck">
                <input type="checkbox" id="SAFixedNavigation" data-add-class="sa-fixed-navigation sa-fixed-header" data-remove-class="sa-fixed-navigation sa-fixed-ribbon" onchange="SASetClass(this)">
                <span>Fixed Navigation</span>
            </label>
        </li>
        <li><label class="vcheck"><input type="checkbox" id="SAFixedRibbon" data-add-class="sa-fixed-navigation sa-fixed-header sa-fixed-ribbon" data-remove-class="sa-fixed-ribbon" onchange="SASetClass(this)"> <span>Fixed Ribbon</span></label></li>
        <li><label class="vcheck"><input type="checkbox" id="SAFixedFooter" data-add-class="sa-fixed-page-footer" data-remove-class="sa-fixed-page-footer" onchange="SASetClass(this)"> <span>Fixed Footer</span></label></li>
        <li><label class="vcheck"><input type="checkbox" id="SAContainerInside" data-add-class="sa-inside-container" data-remove-class="sa-inside-container sa-fixed-ribbon" data-remove-class="sa-fixed-navigation" onchange="SASetClass(this)"> <span>Inside <strong>.container</strong></span></label></li>
        <li><label class="vcheck"><input type="checkbox" id="SAMenuOnTop" data-add-class="sa-menu-on-top" data-remove-class="sa-menu-on-top" onchange="SASetClass(this)"> <span>Menu on <strong>top</strong></span></label></li>
    </ul>
    <h4>Clear Localstorage</h4>
    <a href="javascript:void(0)" class="btn sa-btn-primary btn-block" data-action="resetWidgets"><span class="fa fa-refresh"></span> Factory Reset</a>

    <h6 class="margin-top-10 text-medium margin-bottom-5">SmartAdmin Skins</h6>
    <section id="smart-styles">
        <a href="javascript:void(0);" id="smart-style-0" data-skinlogo="assets/img/common/sa-logo.png" class="btn btn-block btn-xs text-white margin-right-5" style="background-color:#4E463F;">
            <i class="fa fa-check fa-fw" id="skin-checked"></i>Smart Default
        </a>
        <a href="javascript:void(0);" id="smart-style-1" data-skinlogo="assets/img/common/sa-logo-white.png" class="btn btn-block btn-xs text-white" style="background:#3A4558;">Dark Elegance</a>
        <a href="javascript:void(0);" id="smart-style-2" data-skinlogo="assets/img/common/sa-logo-blue.png" class="btn btn-xs btn-block text-darken margin-top-5" style="background:#fff;">Ultra Light</a>
        <a href="javascript:void(0);" id="smart-style-3" data-skinlogo="assets/img/common/sa-logo-pale.png" class="btn btn-xs btn-block text-white margin-top-5" style="background:#f78c40">Google Skin</a>
        <a href="javascript:void(0);" id="smart-style-4" data-skinlogo="assets/img/common/sa-logo-pale.png" class="btn btn-xs btn-block text-white margin-top-5" style="background: #bbc0cf; border: 1px solid #59779E; color: #17273D !important;">PixelSmash</a> <a href="javascript:void(0);" id="smart-style-5" data-skinlogo="assets/img/common/sa-logo-pale.png" class="btn btn-xs btn-block text-white margin-top-5" style="background: rgba(153, 179, 204, 0.2); border: 1px solid rgba(121, 161, 221, 0.8); color: #17273D !important;">Glass </a><a href="javascript:void(0);" id="smart-style-6" data-skinlogo="assets/img/common/sa-logo-pale.png" class="btn btn-xs btn-block text-white margin-top-6" style="background: #2196F3; border: 1px solid rgba(0, 0, 0, 0.3); color: #FFF !important;">MaterialDesign <sup>beta</sup> </a>
    </section>
</div>
