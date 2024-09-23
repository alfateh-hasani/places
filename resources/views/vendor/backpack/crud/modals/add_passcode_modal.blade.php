<div class="modal fade" id="addPasscodeModal" tabindex="-1" role="dialog" aria-labelledby="addPasscodeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPasscodeModalLabel">Add Custom Passcode</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addPasscodeForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="keyboardPwd">Passcode</label>
                        <input type="text" name="keyboardPwd" class="form-control" required>
                    </div>
                    <!-- Add more fields as needed -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Passcode </button>
                </div>
            </form>
        </div>
    </div>
</div>
