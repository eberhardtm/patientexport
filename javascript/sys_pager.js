
var runOnLoad = function()
{     
    window.addEventListener( "pageshow", function ( event ) {
  var historyTraversal = event.persisted || ( typeof window.performance != "undefined" && window.performance.navigation.type === 2 );
  if ( historyTraversal ) {
    // Handle page restore.
    window.location.reload();
  }
});
    $('.pager-row').click(function() {
        $("#save-device-button").css("display","none");
        $("#device-save-result").css("display", "none");
        patient_id = $(this).data('rowId');
        window.location.href = "patientexport/patient/export?patient_id="+patient_id;
        
        /** used for the modal popup which I may use again.
        $.getJSON('patientexport/patient/getDetails/',{
            'patient_id': $(this).data('rowId'),
            'row_index': $(this).index() }, function(jsondata) {
            // display data
            $("h4.modal-title").text('Patient Demographics');
            loadPatientDetails(jsondata);
            //alert(jsondata['FIRSTNAME']);
            $("#device-modal").modal('show');
            
        });
        */
    });
};
   
    function loadPatientDetails(jsondata){
        
            address1 = jsondata['ADDRESS1'];
            address2 = jsondata['ADDRESS2'];
            city = jsondata['CITY'];
            state = jsondata['STATE'];
            zip = jsondata['ZIP'];
            name = jsondata['FIRSTNAME']+" "+jsondata['MIDDLENAME']+" "+jsondata['LASTNAME'];
            home_phone = jsondata['HOMEPHONE'];
            cell_phone = jsondata['CELLPHONE'];
            dob = jsondata['BIRTHDATE'];
            ssn = jsondata['SSN'];
            gender = jsondata['SEX'];
            if(gender = 'F')
                gender = 'Female';
            else
                gender = 'Male';
            
            $("#acct-number").text(jsondata['PR_ACCT_ID']);
            $("#name").text(name);
            $("#address1").text(address1);
            if(address2 === "")
                $("#address2").remove();
            else
                $("#address2").text(address2);
            $("#city-state").text(city+", "+state+" "+zip);
            
            $("#home-phone").text(home_phone);
            $("#cell-phone").text(cell_phone);
            $("#dob").text(dob);
            $("#ssn").text(ssn);
            $("#gender").text(gender);
            $("#patient-id").val(jsondata['PR_ACCT_ID']);
                       
    }

 function enableFormFields(){
       var pc_attributes = ["physical-id","model","processor","ram","hd","video-card","os","vlan","server","battery-backup","redundant-backup","rotation","smart-room","mac","mac2","primary-ip","secondary-ip","primary-monitor","secondary-monitor","purchase-date","server-type","manufacturer","vlan", "touch-screen","stand","dual-monitor","check-in"];
       var ipad_attributes = ["physical-id","hd","generation","mac","primary-ip","apple-id","purchase-date","case"];
       var printer_attributes = ["physical-id","model","toner-cartridge","manufacturer","purchase-date","color","duplex","network"];
       var camera_attributes = ["physical-id","model","megapixels","manufacturer","purchase-date","room-number","department","location","notes","sd-support","exterior","covert","is-on"];
       var digital_sign_attributes = ["physical-id","model","processor","ram","hd","manufacturer","mac","primary-ip","screen-size","screen-manufacturer","purchase-date","room-number","department","location","vlan","hi-def","notes"];
       var time_clock_attributes = ["physical-id","model","manufacturer","purchase-date","room-number","department","location","vlan","notes"];
       var user_attributes = ["system-usage","username","first-name","last-name","department","location","room-number","phone","notes"];
       
       $.each(pc_attributes, function(index, d){
        $("#"+d).prop("disabled", false);
    });
    
    $.each(ipad_attributes, function(index, d){
        $("#"+d).prop("disabled", false);
    });
    
    $.each(printer_attributes, function(index, d){
        $("#"+d).prop("disabled", false);
    });
    
    $.each(camera_attributes, function(index, d){
        $("#"+d).prop("disabled", false);
    });
    
    $.each(digital_sign_attributes, function(index, d){
        $("#"+d).prop("disabled", false);
    });
    
    $.each(time_clock_attributes, function(index, d){
        $("#"+d).prop("disabled", false);
    });
    
    $.each(user_attributes, function(index, d){
        $("#"+d).prop("disabled", false);
    });
        
    //$("#edit-device-button").css("display","none");
    $("#edit-device-button").prop('disabled',true);
    $("#edit-device-button1").css("display","none");
    $("#delete-device-button").css("display","none");
    $("#save-device-button").css("display","block");
}
    

