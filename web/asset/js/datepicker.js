$(document).ready(function(){
    $('.datepicker').datepicker({
        format: 'dd/mm/yyyy',
        startDate: ('+ 0d'),
        daysOfWeekDisabled: '0,2',
        datesDisabled: ['25/01/2018', '26/01/2018']
    });
});
