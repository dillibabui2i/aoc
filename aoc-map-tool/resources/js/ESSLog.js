const ESSLog = {
    /**
     * @param {*} text String what we need to log
     * @param {*} fileName Backend file name where we want to log
     * @param {function} cb Its callback function if we need to wait for the log to complete
    */
   log: function(text, fileName,cb){
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: logUrl,
        type: "POST",
        data: {
            _token: _token,
            text: text,
            fileName: fileName
        },
        success: function(response) {
            cb(response);
        },
    });
   }
}
