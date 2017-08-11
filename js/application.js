(function($) {
    $(function() {
        $('#add-time').click(function (e) {
            e.preventDefault();
            var row = '';
            row  = '<li>';
            row += '    <input type="hidden" name="ept_opt_schedule_times_counter[]" />'
            row += '    <select name="ept_opt_schedule_times_hour[]" style="width: 60px">';
            for (var i = 0; i < 24; i++) {
                if (i < 10) {
                    var index = '0' + i;
                } else{
                    var index = i;
                };
                row += '        <option value="' + index + '">' + index + '</option>';
            };
            row += '    </select>';
            row += '    <select name="ept_opt_schedule_times_minute[]" style="width: 60px">';
            for (var i = 0; i < 60; i++) {
                if (i < 10) {
                    var index = '0' + i;
                } else{
                    var index = i;
                };
                row += '        <option value="' + index + '">' + index + '</option>';
            };
            row += '    </select>';
            row += '    <a class="remove-time tooltip" original-title="Remove posting time">×</a>';
            row += '</li>';
            $('#schedule-times').append(row);
            e.stopPropagation();
        });
        
        $('#schedule-times').on('click', '.remove-time', function (e) {
            e.preventDefault();
            $(this).closest('li').remove();
            e.stopPropagation();
        });

        function showCheckedDays (collection, selector) {
            var weekdays = [];

            $(collection).each(function (i, el) {
                if ( $(this).hasClass('checked') ) {
                    weekdays.push($.trim($(this).text()));
                }
            });

            $(selector).html(weekdays.join(", "));
        }

        $('.schedule-day-checkbox').on('click', 'input', function (e) {

            if ( $(this).attr('checked') == 'checked' ) {
                $(this).closest('li').addClass('checked');
            } else {
                $(this).closest('li').removeClass('checked');
            }

            showCheckedDays('.schedule-day-checkbox', '#scheduled-days');
        });

        showCheckedDays('.schedule-day-checkbox', '#scheduled-days');
    });
})(jQuery);