<div class="error-message" id="errorMessage">
    <span id="errorText">An error occurred!</span>
    <button class="close-btn" id="closeBtn">&times;</button>
</div>
<script>
    function showError(errorMessage) {
        const $errorMessage = $('#errorMessage');
        $('#errorText').text(errorMessage);
        $errorMessage.fadeIn();

        // Automatically hide the message after 5 seconds
        setTimeout(() => {
            $errorMessage.fadeOut();
        }, 3000);
    }

    $('#closeBtn').on('click', function() {
        $('#errorMessage').fadeOut();
    });
</script>
