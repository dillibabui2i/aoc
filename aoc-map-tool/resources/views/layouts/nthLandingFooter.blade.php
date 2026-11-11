<div id="mainFooter">
   <p>Need help finding the right case?</p>
   <p>Call us at <a href="tel:1-844-384-2567">1-844-38-HALOS</a> or <a href="javascript:void(0);" id="sendUsMessageLinkNth" onclick="getGeneralInquiryForm()">send us a message</a></p>
 
</div>
<div id="secondaryFooter">
<a href="https://www.angelsofcare.com" target="_blank">Visit Angels of Care Website</a>
<br>
<a href="https://angelsofcare.com/privacy-policy/" target="_blank">Privacy Policy</a> | <a href="https://angelsofcare.com/terms-of-use/" target="_blank">Terms of Use</a>
</div>
<div id="bottomFooter">
   <p>&copy; 2025 Angels of Care Pediatric Home Health. All Rights reserved.</p>
</div>
<script>
   function getGeneralInquiryForm(){
      let _token = $('meta[name="csrf-token"]').attr('content');
      $.ajax({
         url: "{{route('nthlanding.getNthGeneralInquiry')}}",
         type: "GET",
         dateType:"html",
         success: function(data) {
            $("#tempGeneralInquiryCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempGeneralInquiryCnt').html(data);
            $('#generalInquiryModal').modal('show');
         },
      });
   }
</script>
