var DNS = {
    _template: null

    , init: function() {
        // Generate row template
        var first = $('.edit-record:first');
        DNS._template = first.parents('tr').clone().removeClass('hidden');
        DNS._template.find('input').removeAttr('value');
        DNS._template.find('select').prop('selectedIndex', 0);
        DNS._template.find('textarea').text('');
        DNS._template.find('*').removeClass('disabled').removeAttr('disabled');
        first.remove();

        $(document).on( 'click', '.edit-record', DNS.editRecord );
        $(document).on( 'click', '.delete-record', DNS.deleteRecord );
        $('#aAddRecord').click( DNS.addRecord );
        $('#fEditDNS').submit( DNS.submit );
    }

    , editRecord: function(e) {
        e.preventDefault();

        var row = $(this).parents('tr:first');
        var new_row = row.clone();

        if ($(this).hasClass('cloudflare') ) {
            row.find('*').removeClass('disabled').removeAttr('disabled');
            row.find('.action').val('2');
        } else {
            // current row action=0 (delete)
            row.find('*').removeClass('disabled').removeAttr('disabled');
            row.find('.action').val('0');

            // new row action=1 (add)
            new_row.find('*').removeClass('disabled').removeAttr('disabled');
            new_row.find('.action').val('1');

            // show
            row.after(new_row);
            row.hide();
        }
    }

    , deleteRecord: function(e) {
        e.preventDefault();

        var row = $(this).parents('tr:first');

        // current row action=0 (delete)
        row.find('*').removeClass('disabled').removeAttr('disabled');
        row.find('.action').val('0');

        row.hide();
    }

    , addRecord: function(e) {
        e.preventDefault();
        var row = DNS._template.clone();

        // new row action=1 (add)
        row.find('.action').val('1');

        // show
        $('#fEditDNS tbody').append(row);
    }

    , submit: function(e) {
        var form = $(this);

        form.find('.changes-type:visible').each( function() {
            var changeType = $(this).val()
            var record_input = $(this).parents('tr:first').find('.changes-records:first')
            var records = record_input.val().split("\n");

            if ( !DNS.validateType( changeType, records ) ) {
                alert('The records you have entered do not match the type you have selected.');
                record_input.focus();
                return false;
            }
        });

        return true;
    }

    , validateType: function( changeType, records ) {
        switch ( changeType ) {
            case 'A':
                // Check for IPs
                var regex = /^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/;
                break;

            case 'CNAME':
                // Check domains
                var regex = /^(?:[-a-zA-Z0-9]+\.)*([-a-zA-Z0-9]+\.[a-zA-Z]{2,3}){1,2}$/;
                break;

            default:
                return true;
                break;
        }

        for ( var i in records ) {
            var charPos = records[i].match( regex );

            if ( null == charPos )
                return false;
        }

        return true;
    }

}

jQuery(DNS.init);