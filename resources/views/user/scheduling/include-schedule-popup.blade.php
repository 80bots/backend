<div class="modal fade" id="create-scheduler" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div id="message">
            </div>
            <form id="CreateSchedulerForm" name="CreateSchedulerForm" action="{{route('user.scheduling.store')}}" method="post">
                <input type="hidden" id="instance-id" name="instance_id" value="">
                <input type="hidden" id="user-time-zone" name="userTimeZone" value="">
                @csrf
                <div class="modal-header">
                    <h4 class="modal-title">{{ __('user.scheduling.create_scheduler') }}&nbsp;<span id="bot-name"></span></h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="text-right">
                        <button type="button" onclick="addSchedulerRow()" class="btn btn-success btn-round btn-icon"><i
                                class="fa fa-plus"></i></button>
                    </div>
                    <div class="row">
                        <div class="col-sm-3 border-right">
                            {{ __('keywords.scheduling.type') }}
                        </div>
                        <div class="col-sm-3 border-right">
                            {{ __('keywords.scheduling.days') }}
                        </div>
                        <div class="col-sm-5 border-right">
                            {{ __('keywords.scheduling.time') }}
                        </div>
                        <div class="col-sm-1 align-items-center"></div>
                    </div>
                    <div id="scheduler-row">
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" id="launch-inspection-submit-btn" class="btn btn-success" value="Save">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('keywords.close') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<input type="hidden" name="row-data-val" id="row-data-val">


