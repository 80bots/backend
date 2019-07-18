<div class="modal fade" id="launch-instance" role="dialog">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form id="lunchInstance" >
                @csrf
                <input type="hidden" name="bot_id" value="" id="bot_id">
                <div class="modal-header">
                    <h4 class="modal-title">Launch Bot</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <h4>Are you sure?</h4>
                </div>
                <div class="modal-footer">
                    <input type="button" id="launch-inspection-submit-btn" class="btn btn-success" value="Ok">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>