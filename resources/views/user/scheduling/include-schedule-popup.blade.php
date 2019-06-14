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
                                        <option value="AM">AM</option>
                                        <option value="PM">PM</option>
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
                                        <option value="AM">AM</option>
                                        <option value="PM">PM</option>
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

    function addSchedulerRow(ids = null, day = null, start_time = null, end_time = null) {
        var asides = ['AM', 'PM'];
        var row =
            '<div class="row">\n';
        if(ids === null){
            ids = [0,0];
        }
        row += '<input type="hidden" name="ids[]" value="'+ids+'" >';

        row += '    <div class="col-sm-2 border-right">\n' +
            '        <div class="form-group">\n' +
            '            <select name="day[]" id="" class="form-control">\n' +
            '                <option value="">-select-</option>\n';
        $.each(weekDays, function (key, val) {
            row += '<option value="' + val + '"';
            if (day != null && day == val) {
                row += 'selected';
            }
            row += '>' + val + '</option>';

        });
        row += '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '    <div class="col-sm-3">\n' +
            '        <div class="form-group">\n' +
            '            <select name="start_time[]" id="" class="form-control">\n' +
            '                <option value="">-Select-</option>\n';
        if(start_time != null) {
            start_time = start_time.split(" ");
        }
            @for($i = 1; $i <= 12; $i++)
            @php
                $time = date('h:i', strtotime("$i:00"));
            @endphp
        var time = "{{$time}}";
        row += '<option value="'+time+'"';
        if(start_time != null){
            if(time == start_time[0]){
                row += 'selected';
            }
        }
        row += '>'+time+'</option>';
        @endfor

            row += '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '    <div class="col-sm-2 border-right">\n' +
            '        <div class="form-group">\n' +
            '            <select name="start_aside[]" id="" class="form-control">\n' +
            '                <option value="">-Select-</option>\n';
        $.each(asides, function (aKey, aVal) {
            row += '<option value="'+aVal+'"';
            if(start_time != null){
                if(aVal == start_time[1]){
                    row += 'selected';
                }
            }
            row += '>'+aVal+'</option>\n';
        });
        row += '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '    <div class="col-sm-3">\n' +
            '        <div class="form-group">\n' +
            '            <select name="end_time[]" id="" class="form-control">\n' +
            '                <option value="">-Select-</option>\n';
        if(end_time != null) {
            end_time = end_time.split(" ");
        }
            @for($i = 1; $i <= 12; $i++)
            @php
                $time = date('h:i', strtotime("$i:00"));
            @endphp
        var endtime = "{{$time}}";
        row += '<option value="'+endtime+'"';
        if(end_time != null){
            if(endtime == end_time[0]){
                row += 'selected';
            }
        }
        row += '>'+endtime+'</option>';
        @endfor

            row += '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '    <div class="col-sm-2">\n' +
            '        <div class="form-group">\n' +
            '            <select name="end_aside[]" id="" class="form-control">\n' +
            '                <option value="">-Select-</option>\n';
        $.each(asides, function (aKey, aVal) {
            row += '<option value="'+aVal+'"';
            if(end_time != null) {
                if (aVal == end_time[1]) {
                    row += 'selected';
                }
            }
            row += '>'+aVal+'</option>\n';
        });
        row += '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '</div>';

        $('#scheduler-row').append(row);
    }

    function SetBotName(name, id) {
        $('#instance-id').val(id);
        $('#bot-name').text(name);

        checkSchedule(id);
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
                    $('#scheduler-row').empty();

                    var schedulingInstance = response.data;
                    var scheduling_instance_details = schedulingInstance.scheduling_instance_details;
                    var lenth = scheduling_instance_details.length;
                    for (i = 0; i < lenth;) {
                        var day = scheduling_instance_details[i].day;
                        var start = scheduling_instance_details[i].schedule_type;
                        var start_time = scheduling_instance_details[i].selected_time;
                        var end = scheduling_instance_details[i + 1].schedule_type;
                        var end_time = scheduling_instance_details[i + 1].selected_time;


                        var row =
                            '<div class="row">\n';
                        var ids = [];
                        if(scheduling_instance_details[i] != '' && scheduling_instance_details[i+1] != '')
                        {
                            ids.push(scheduling_instance_details[i].id);
                            ids.push(scheduling_instance_details[i+1].id);
                        }
                        addSchedulerRow(ids, day, start_time, end_time);

                        i = i + 2;
                    }

                } else {
                    // give an error
                }
            }
        });
    }

</script>
