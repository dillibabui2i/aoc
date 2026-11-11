<div class="row pt-5">
    <div class="col-md-6 attampted_to_contact_container">
        <label></label>
        <div class="custom-control custom-switch" style="margin: 12px 0px;">
            <input class="custom-control-input" type="checkbox" id="attampted_to_contact"
                name="attampted_to_contact" />
            <label class="custom-control-label" for="attampted_to_contact">Attempted to Contact?</label>
        </div>
    </div>
    <div class="col-md-6">
        <textarea name="note" id="note" placeholder="Add Notes" class="form-control"></textarea>
    </div>
</div>
<script>
    validation = {
        errorLabelContainer: "#error-note",
        wrapper: "li",
        onfocusout: function(element, event) {
            this.element(element);
        },
        rules: {
            "attampted_to_contact": {
                required: function(element) {
                    return !($("#attampted_to_contact").val() !== 'on');
                }
            },
            "note": {
                required: function(element) {
                    return !($("#note").val() !== '');
                }
            }
        },
        messages: {
            attampted_to_contact: "Please check attampted to contact",
            note: "Please add notes",
        },
        errorPlacement: function(error, element) {}
    };
</script>
