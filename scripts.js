let functionUpload = function () {

    jQuery(function ($) {
        let form_data = new FormData($('#login_cv_form')[0]);// all data from form.
        form_data.append('action', window.obj.plugin_acronym);// add ajax action.
        form_data.append('security', jQuery('#_wpnonce').val()); //add nonce.
        form_data.append('files', jQuery('#uploaded_file')[0]);//add file! [0]

        $.ajax({
            url: window.obj.ajax_url,
            type: 'POST',
            contentType: false,
            processData: false,
            data: form_data,
            success: function (response) {
                let data = response.data;
                jQuery('#modal-body').append(data.html);
                jQuery('#but').click();
                setTimeout("window.location=window.obj.site_url", 5000);
            }
        });
    });
}


let functionAjax = function () {

    let make_form_for_first_time = false;
    if (!jQuery('#login_cv_form').length) {
        make_form_for_first_time = true;//first form's addition
    }

    jQuery.ajax(
        {
            type: 'POST',
            url: window.obj.ajax_url,// url for WP ajax url (get on frontend! set in wp_localize_script like object).
            data: {
                action: obj.plugin_acronym,// must be equal to add_action( 'wp_ajax_filter_plugin', 'ajax_filter_posts_query' ).
                first_time: make_form_for_first_time,
                name: jQuery('#login').val(),
                password: jQuery('#password').val(),
                remember: jQuery('#remember').is(':checked'),
                security: jQuery('#_wpnonce').val(),
            },

            success: function (response) {
                jQuery('nav').after(response.data.html);// insert form after navigation.
            }
        }
    );
};

jQuery(
    function () {// for first time page open - form insertion.
        functionAjax();// make AJAX content update
    }
);



jQuery(function () {
    jQuery(document).on('change', '#uploaded_file', function () {
        let file_obj = jQuery('#uploaded_file')

        jQuery('#file-message').remove();
        file_obj.css('border', '');

        if (file_obj[0].files[0].size > 5242880) {//5242880 = 5mb = 5*1024*1024
            jQuery('#upload_message').remove();
            file_obj.after("<b id ='upload_message' >file size is big!</b>");
            jQuery('#uploaded-file-div').css('border', '4px solid fuchsia');
        } else {
            jQuery('#upload_message').remove();
            jQuery('#uploaded-file-div').css('border', '');
        }
    });
});

jQuery(function () {//selector may be like '#login, #password' for multiply controls
    jQuery(document).on('click', '#upload_button', function () {

        let login_name = jQuery('#login_name');
        let login_surname = jQuery('#login_surname');
        let login_mail = jQuery('#login_mail');
        let uploaded_file = jQuery('#uploaded_file');

        let login_name_error_message = jQuery('#login-name-message');
        let login_surname_error_message = jQuery('#login-surname-message');
        let login_mail_error_message = jQuery('#login-mail-message');
        let file_error_message = jQuery('#file-message');

        let can_upload = true;

        if (login_name.val() !== '') {
            login_name_error_message.remove();
            login_name.css('border', '');
        } else {
            can_upload = false;
            login_name_error_message.remove();
            login_name.after('<p id="login-name-message">field must be filled</p>');
            login_name.css('border', '4px solid red');
        }

        if (login_surname.val() !== '') {
            login_surname_error_message.remove();
            login_surname.css('border', '');
        } else {
            can_upload = false;
            login_surname_error_message.remove();
            login_surname.after('<p id="login-surname-message">field must be filled</p>');
            login_surname.css('border', '4px solid red');
        }

        if (login_mail.val() !== '') {
            login_mail_error_message.remove();
            login_mail.css('border', '');

            //mail validation (https://stackoverflow.com/questions/2507030/how-can-one-use-jquery-to-validate-email-addresses)
            let regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;

            if (regex.test(login_mail.val()) === false) {
                can_upload = false;
                login_mail.after('<p id="login-mail-message">invalid mail format</p>');
                login_mail.css('border', '4px solid orange');
            }

        } else {
            can_upload = false;
            login_mail_error_message.remove();
            login_mail.after('<p id="login-mail-message">mail field must be filled</p>');
            login_mail.css('border', '4px solid red');
        }

        if (uploaded_file.val() !== '') {
            file_error_message.remove();
            uploaded_file.css('border', '');
        } else {
            can_upload = false;
            file_error_message.remove();
            uploaded_file.after('<p id="file-message">field must be filled</p>');
            uploaded_file.css('border', '4px solid red');
        }

        if (can_upload != false) {
            functionUpload();
        }
    });
});