jQuery(document).ready(function () {
    var $ = jQuery;

    var databaseType = $('#fieldset-database input[name=database_type]:checked').val();
    databaseType == 'share' ? shareDatabase() : separateDatabase();

    $('#fieldset-database input[name=database_type]').change(function () {
        this.value == 'share' ? shareDatabase() : separateDatabase();
    });
    function shareDatabase() {
        var inputs = $('#fieldset-database .field');
        $(inputs).each(function() {
            if (!$(this).find('.inputs.radio').length && !$(this).find('#database_prefix').length && !$(this).find('#database_prefix_note-label').length) {
                $(this).hide(300);
            }
        });
    }
    function separateDatabase() {
        var inputs = $('#fieldset-database .field');
        $(inputs).each(function() {
            if (!$(this).find('.inputs.radio').length && !$(this).find('#database_prefix').length) {
                $(this).show(300);
            }
        });
    }

    if ($('body.upgrade.form').hasClass('confirm')) {
        $('#fieldset-confirm').show();
    } else {
        $('#fieldset-confirm').hide();
    }
});
