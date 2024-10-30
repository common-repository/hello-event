(function($){

  $(document).ready(function()
  {
    dateformat = "Y-m-d"; // default
    timeformat = "H:i";
    defaultEndDate = false;
    if ($("input#end_date_meta_box_field").val() == "") {
      defaultEndDate = $("input#start_date_meta_box_field").val();
    }
    if ($("input.datepicker").length) {
      dateformat_option=$("input.datepicker:first").data("dateformat");
      switch( dateformat_option) {
      case 'iso':
        dateformat = "Y-m-d"
        break;
      case 'us':
        dateformat = "m/d/Y"
        break;
      case 'fr':
        dateformat = "d/m/Y"
        break;
      }
    }
    var endDateSet = function(currentDateTime) {
      var currentStartDate = $("input#start_date_meta_box_field").val();
      var currentEndDate = $("input#end_date_meta_box_field").val();
      if(true || currentEndDate == ""){
        if (currentStartDate != "") {
          defaultEndDate = currentStartDate;
          $("input#end_date_meta_box_field").val(currentStartDate);
          $("input#end_date_meta_box_field").datetimepicker('setOptions', {defaultDate: defaultEndDate});
        }
      };
      return true;
    };
    
    if (typeof locale !== 'undefined') 
      jQuery.datetimepicker.setLocale(locale);
		$("input#start_date_meta_box_field").datetimepicker( {
      timepicker: false,
      format: dateformat,
		  scrollInput: false,
      onClose: endDateSet,
		})
    
		$("input#end_date_meta_box_field").datetimepicker( {
      timepicker: false,
      format: dateformat,
		  scrollInput: false,
      defaultDate: defaultEndDate,
		});
    
    // $("input.datepicker").datetimepicker( {
    //       timepicker: false,
    //       format: dateformat
    // });
    
    if ($("input.timepicker").length) {
      timeformat_option=$("input.timepicker:first").data("timeformat");
      timeformat = (timeformat_option == "am-pm") ? "h:mm p" : "H:mm"
    }
    $("input.timepicker").timepicker({
      timeFormat: timeformat,
    });
    


  });



})(jQuery)