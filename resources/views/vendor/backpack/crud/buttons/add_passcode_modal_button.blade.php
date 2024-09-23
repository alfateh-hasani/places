<a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPasscode" data-id="{{ $entry->id }}">
    Add Passcode
</a>
 <div class="modal fade" id="addPasscode" tabindex="-1" aria-labelledby="addPasscodeLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPasscodeLabel">Unlock Lock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addPasscodeForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="keyboardPwd">Passcode</label>
                        <input type="number" minlength="6" maxlength="9" name="keyboardPwd" class="form-control" required>
                    </div>
                    <!-- Add more fields as needed -->
                </div>
                <div class="modal-footer">
                
                    <button type="submit" class="btn btn-primary">Add Passcode </button>
                </div>
            </form>
        </div>
    </div>
</div>
 
 
<script>

    $(document).on('show.bs.modal', '.modal', function () {
        $(this).appendTo('body');
    });
    $(document).ready(function() {
        // Handle Unlock button click
        $('#addPasscode').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var lockId = button.data('id'); // Extract lock ID from data-id attribute
            var actionUrl = "{{ url('admin/lock') }}/" + lockId + "/add-passcode";
            var modal = $(this);
            modal.find('#addPasscodeForm').attr('action', actionUrl);
        });
        
    });
</script>
 