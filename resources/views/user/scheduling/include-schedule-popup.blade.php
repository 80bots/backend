@php
    $timestamp = strtotime('next Sunday');
    $weekDays = array();
    for ($i = 0; $i < 7; $i++) {
        $weekDays[] = strftime('%A', $timestamp);
        $timestamp = strtotime('+1 day', $timestamp);
    }
@endphp

<div class="modal fade" id="create-scheduler" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="CreateSchedulerForm" action="{{route('user.scheduling.store')}}" method="post">
                <input type="hidden" id="instance-id" name="instance_id" value="">
                @csrf
                <div class="modal-header">
                    <h4 class="modal-title">Create Scheduler For <span id="bot-name"></span></h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="text-right">
                        <button type="button" onclick="addSchedulerRow()" class="btn btn-success btn-round btn-icon"><i
                                class="fa fa-plus"></i></button>
                    </div>
                    <div class="row">
                        <div class="col-sm-2 border-right">
                            Days
                        </div>
                        <div class="col-sm-5 border-right">
                            Start Time
                        </div>
                        <div class="col-sm-5 align-items-center">
                            End Time
                        </div>
                    </div>
                    <div id="scheduler-row">
                        <div class="row">
                            <div class="col-sm-2 border-right">
                                <div class="form-group">
                                    <select name="day[]" id="" class="form-control">
                                        <option value="">-select-</option>
                                        @foreach($weekDays as $day)
                                            <option value="{{$day}}">{{$day}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <select name="start_time[]" id="" class="form-control">
                                        <option value="">-Select-</option>
                                        @for($i = 1; $i <= 12; $i++)
                                            <option value="{{date('h:i', strtotime("$i:00"))}}">{{$i}}:00</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-2 border-right">
                                <div class="form-group">
                                    <select name="start_aside[]" id="" class="form-control">
                                        <option value="">-Select-</option>
                                        <option value="am">AM</option>
                                        <option value="pm">PM</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <select name="end_time[]" id="" class="form-control">
                                        <option value="">-Select-</option>
                                        @for($i = 1; $i <= 12; $i++)
                                            <option value="{{date('h:i', strtotime("$i:00"))}}">{{$i}}:00</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <select name="end_aside[]" id="" class="form-control">
                                        <option value="">-Select-</option>
                                        <option value="am">AM</option>
                                        <option value="pm">PM</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" id="launch-inspection-submit-btn" class="btn btn-success" value="Save">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    let weekDays = <?= json_encode($weekDays) ?>;

    function addSchedulerRow() {
        var row =
            '<div class="row">\n' +
            '    <div class="col-sm-2 border-right">\n' +
            '        <div class="form-group">\n' +
            '            <select name="day[]" id="" class="form-control">\n' +
            '                <option value="">-select-</option>\n' +
            '                @foreach($weekDays as $day)\n' +
            '                    <option value="{{$day}}">{{$day}}</option>\n' +
            '                @endforeach\n' +
            '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '    <div class="col-sm-3">\n' +
            '        <div class="form-group">\n' +
            '            <select name="start_time[]" id="" class="form-control">\n' +
            '                <option value="">-Select-</option>\n' +
            '                @for($i = 1; $i <= 12; $i++)\n' +
            @php
                $time = date('h:i', strtotime("$i:00"));
            @endphp
                '                    <option value="{{$time}}">{{$i}}:00</option>\n' +
            '                @endfor\n' +
            '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '    <div class="col-sm-2 border-right">\n' +
            '        <div class="form-group">\n' +
            '            <select name="start_aside[]" id="" class="form-control">\n' +
            '                <option value="">-Select-</option>\n' +
            '                <option value="am">AM</option>\n' +
            '                <option value="pm">PM</option>\n' +
            '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '    <div class="col-sm-3">\n' +
            '        <div class="form-group">\n' +
            '            <select name="end_time[]" id="" class="form-control">\n' +
            '                <option value="">-Select-</option>\n' +
            '                @for($i = 1; $i <= 12; $i++)\n' +
            @php
                $time = date('h:i', strtotime("$i:00"));
            @endphp
                '                    <option value="{{$time}}">{{$i}}:00</option>\n' +
            '                @endfor\n' +
            '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '    <div class="col-sm-2">\n' +
            '        <div class="form-group">\n' +
            '            <select name="end_aside[]" id="" class="form-control">\n' +
            '                <option value="">-Select-</option>\n' +
            '                <option value="am">AM</option>\n' +
            '                <option value="pm">PM</option>\n' +
            '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '</div>';

        $('#scheduler-row').append(row);
    }

    function SetBotName(name, id) {
        $('#instance-id').val(id);
        $('#bot-name').text(name);

        // checkSchedule(id);
    }

    function checkSchedule(id) {
        var url = '{{url('user/scheduling/check-scheduled')}}/' + id;
        $.ajax({
            type: 'get',
            url: url,
            cache: false,
            success: function (data) {
                var response = JSON.parse(data);
                if (response.status == 'true') {
                    // $('#scheduler-row').empty();

                    var schedulingInstance = response.data;
                    var scheduling_instance_details = schedulingInstance.scheduling_instance_details;/*
                    for(i=0;i<scheduling_instance_details.length;i+2){
                        var day = scheduling_instance_details[i].day;
                        var start = scheduling_instance_details[i].schedule_type;
                        var start_time = scheduling_instance_details[i].selected_time;
                        var end = scheduling_instance_details[i+1].schedule_type;
                        var end_time = scheduling_instance_details[i+1].selected_time;

                        console.log(day);
                        console.log(start);
                        console.log(start_time);
                        console.log(end);
                        console.log(end_time);
                    }*/

                    /*$.each(scheduling_instance_details, function (key, val) {
                        var i = 0;


                    });*/
                } else {

                }
            }
        });
    }

</script>
