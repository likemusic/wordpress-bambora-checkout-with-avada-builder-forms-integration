window.fusionForms.ajaxSubmit = function (e, r) {
    var o = this;
    e.find(".form-form-submit").addClass("fusion-form-working"),
        jQuery.ajax({
            type: "POST",
            url: formCreatorConfig.ajaxurl,
            data: window.fusionForms.getFormData(e, r),
            action: "fusion_form_submit_form_to_" + r.form_type,
            dataType: "json",
            processData: !1,
            contentType: !1
        }).done(function (i) {
            var redirectUrl = i.redirect_url ? i.redirect_url : r.redirect_url;
            
            i.captcha || jQuery(e)[0].reset(),
                "success" == i.status && "redirect" == r.confirmation_type && "" !== redirectUrl ? window.location = redirectUrl : o.revealAlert(e, i.status),
                jQuery(window).trigger("fusion-form-ajax-submit-done", i)
        }).fail(function () {
            o.revealAlert(e, "error")
        }).always(function () {
            e.find(".form-form-submit").removeClass("fusion-form-working"),
                jQuery(window).trigger("fusion-form-ajax-submitted")
        })
};
