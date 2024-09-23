<a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#unlockModal" data-id="{{ $entry->id }}">
    Unlock
</a>
 <div class="modal fade" id="unlockModal" tabindex="-1" aria-labelledby="unlockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unlockModalLabel">Unlock Lock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="unlockForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to unlock this lock?</p>
                </div>
                <div class="modal-footer">
        
                    <button type="submit" class="btn btn-primary">Unlock</button>
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
        $('#unlockModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var lockId = button.data('id'); // Extract lock ID from data-id attribute
            var actionUrl = "{{ url('admin/lock') }}/" + lockId + "/unlock";
            var modal = $(this);
            modal.find('#unlockForm').attr('action', actionUrl);
        });

        
    });
</script>
 