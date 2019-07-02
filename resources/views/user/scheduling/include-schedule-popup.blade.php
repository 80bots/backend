@php
    $timestamp = strtotime('next Sunday');
    $weekDays = array();
    for ($i = 0; $i < 7; $i++) {
        $weekDays[] = strftime('%A', $timestamp);
        $timestamp = strtotime('+1 day', $timestamp);
    }

    $timesArr = [];
    for($i = 1; $i <= 12; $i++){
        for($j=1; $j<=2; $j++){
            if($j%2 == 0){
                $time = date('h:i', strtotime("$i:30"));
            } else {
                $time = date('h:i', strtotime("$i:00"));
            }
            array_push($timesArr, $time);
        }
    }
@endphp

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
                    <h4 class="modal-title">Create Scheduler For <span id="bot-name"></span></h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="text-right">
                        <button type="button" onclick="addSchedulerRow()" class="btn btn-success btn-round btn-icon"><i
                                class="fa fa-plus"></i></button>
                    </div>
                    <div class="row">
                        <div class="col-sm-3 border-right">
                            Days
                        </div>
                        <div class="col-sm-8 border-right">
                            Scheduled Time
                        </div>
                        <div class="col-sm-1 align-items-center">
                            Action
                        </div>
                    </div>
                    <div id="scheduler-row">
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

<input type="hidden" name="row-data-val" id="row-data-val">

{{--<script type="text/javascript" src="{{asset('js/jquery-3.3.1.min.js')}}"></script>--}}
@section('script')
<script src="{{asset('js/jquery.validate.min.js')}}" type="text/javascript"></script>
<script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
<script>
    var current_time_zone =  moment().format('Z');
    $('#user-time-zone').val(current_time_zone);

    let weekDays = <?= json_encode($weekDays) ?>;
    let TimeArray = <?= json_encode($timesArr) ?>;
    let AsideArray = ['AM', 'PM'];
    const fullTime = {};
    let rowStartTimeVal = [];
    let rowEndTimeVal = [];
    let endFulltime = [];
    window.onload = function () {
        $.each(weekDays, function (weekKey, weekVal) {
            var data = [];
            $.each(AsideArray, function (key, val) {
                $.each(TimeArray, function (TimeKey, TimeVal) {
                    if(TimeVal == '12:00' && val == 'AM' || TimeVal == '12:30' && val == 'AM'){
                        data.push(TimeVal+' '+'PM');
                    }else if(TimeVal == '12:00' && val == 'PM' || TimeVal == '12:30' && val == 'PM'){
                        // data.push(TimeVal+' '+'AM');
                        if(TimeVal == '12:00'){
                            data.splice(0, 0, TimeVal+' '+'AM');
                        } else {
                            data.splice(1, 0, TimeVal+' '+'AM');
                        }
                    } else {
                        data.push(TimeVal+' '+val);
                    }
                });
            });
            fullTime[weekVal] = data;
            endFulltime[weekVal] = data;
        });
    };

    // $('#launch-inspection-submit-btn').click(function () {
        /*$("form[name='CreateSchedulerForm']").validate({
            ignore: [],
            rules: {
                'scheduled_time[]': {
                    required: true
                },
                'end_time[]': {
                    required: true
                }
            },
            messages: {
            },
            submitHandler: function(form) {
                form.submit();
            }
        });*/
    // });

    function addSchedulerRow(ids = null, day = null, scheduled_time = null, end_time = null) {
        storeRow();
        var numRow = $('#scheduler-row .row').length;
        var asides = ['AM', 'PM'];
        var newfull = Object.assign({},fullTime);
        var row =
            '<div class="row" id="row_'+numRow+'">\n';
        if (ids === null) {
            ids = [0, 0];
        }
        row += '<input type="hidden" name="ids[]" value="' + ids + '" >';

        row += '    <div class="col-sm-3 border-right">\n' +
            '        <div class="form-group">\n' +
            '            <select name="day[]" id="day_'+numRow+'" class="form-control">\n' +
            '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '    <div class="col-sm-8 border-right">\n' +
            '        <div class="form-group">\n' +
            '            <select name="scheduled_time[]" id="scheduled_time_'+numRow+'" class="form-control">\n' +
            '                <option value="">-Select-</option>\n';
            row += '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' + 
                '<div class="col-sm-1 border-right">' +
                '<a href="javascript:void(0)" onclick="deleteRow(['+ids+'],'+numRow+')" class="btn btn-round btn-icon btn-danger">x</a>' +
                '</div>' +
            '</div>';

        $('#scheduler-row').append(row);
        CreateOptions(weekDays, numRow, 'day', day);

        if(end_time != ''){
            end_time = convertUtcToUser(end_time);
        }

        if(scheduled_time != ''){
            scheduled_time = convertUtcToUser(scheduled_time);
        }

        if(scheduled_time != ''){
            CreateOptions(newfull[day], numRow, 'scheduled_time', scheduled_time);
        } else {
            if(end_time != ''){
                var endKey = newfull[day].indexOf(end_time);
                scheduled_time = newfull[day].slice(0,endKey);
                CreateOptions(scheduled_time, numRow, 'scheduled_time');
            }
        }

        if(end_time != ''){
            CreateOptions(newfull[day], numRow, 'end_time', end_time);
        } else {
            if(scheduled_time != ''){
                var startKey = newfull[day].indexOf(scheduled_time);
                scheduled_time = newfull[day].slice(startKey+1, 48);
                CreateOptions(scheduled_time, numRow, 'end_time');
            }
        }
    }

    function deleteRow(ids, numRow){
        var url = '{{route('user.scheduling.delete-scheduler-details')}}';
        $.ajax({
            type: 'post',
            async: false,
            url: url,
            cache: false,
            data: {
                _token : function () {
                    return '{{csrf_token()}}';
                },
                ids : ids
            },
            success: function (data) {
                var response = JSON.parse(data);
                if(response.status == 'true'){
                    $("#message").fadeIn().html("<div class='alert alert-success fade show'>" +
                        "<button data-dismiss='alert' class='close close-sm' type='button'>" +
                        "<i class='fa fa-times'></i>" +
                        "</button><p>"+response.message+"</p></div>").delay(3000).fadeOut();
                    $('#row_'+numRow).remove();
                } else {
                    $("#message").fadeIn().html("<div class='alert alert-danger fade show'>" +
                        "<button data-dismiss='alert' class='close close-sm' type='button'>" +
                        "<i class='fa fa-times'></i>" +
                        "</button><p>"+response.message+"</p></div>").delay(3000).fadeOut();
                }
            },
            complete: function() {
                var row_lenth = $('#scheduler-row .row').length;
                if(row_lenth == 0){
                    addSchedulerRow();
                }
            }
        });
    }

    function convertUtcToUser(str){
        var url = '{{url('user/scheduling/convert-time-utc-to-user')}}/'+str+'/'+current_time_zone;
        var value = '';
        $.ajax({
            type: 'get',
            async: false,
            url: url,
            cache: false,
            success: function (data) {
                value = data;
            }
        });
        return value;
    }

    function SetBotName(name, id) {
        $('#scheduler-row').empty();
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
                    var schedulingInstance = response.data;
                    var scheduling_instance_details = schedulingInstance.scheduling_instance_details;
                    var lenth = scheduling_instance_details.length;
                    for (i = 0; i < lenth;) {
                        var day = scheduling_instance_details[i].day;
                        var start = scheduling_instance_details[i].schedule_type;
                        var scheduled_time = scheduling_instance_details[i].selected_time;
                        var end = scheduling_instance_details[i + 1].schedule_type;
                        var end_time = scheduling_instance_details[i + 1].selected_time;


                        var row =
                            '<div class="row">\n';
                        var ids = [];
                        if (scheduling_instance_details[i] != '' && scheduling_instance_details[i + 1] != '') {
                            ids.push(scheduling_instance_details[i].id);
                            ids.push(scheduling_instance_details[i + 1].id);
                        }
                        addSchedulerRow(ids, day, scheduled_time, end_time);

                        i = i + 2;
                    }
                    if(lenth == 0){
                        addSchedulerRow();
                    }

                } else {
                    addSchedulerRow();
                }
            }
        });
    }

    $(document).on('change', '[name="day[]"]', function () {
        var id = $(this).attr('id').split("_");
        var selectedVal = $(this).val();
        var newfull = Object.assign({},fullTime);
        var NewStartTimeArray = newfull;
        id = id[1];
        var rowData = JSON.parse($('#row-data-val').val());
        var removeItemStart = [];
        var isEndTimeProvide = 0;
        var preTimeKey = 0;

        var preId = id-1;
        var aboveSelectEndTime = $('#end_time_'+preId).val();
        var aboveSelectedDay = $('#day_'+preId).val();
        var selectedValKey = weekDays.indexOf(selectedVal);
        var aboveselectedDayKey = weekDays.indexOf(aboveSelectedDay);
        $.each(rowData, function (key, val) {
            var ValArr = val.split('|');
            var day = ValArr[0];
            var start = ValArr[1];
            if(start == 'null'){
                start = '01:00 AM';
            }
            var end = ValArr[2];
            if(end == 'null'){
                end = '12:30 AM';
            }
            var startKey, endKey;
            startKey = fullTime[day].indexOf(start);
            endKey = fullTime[day].indexOf(end);
            if(endKey == 47){
                endKey += 1;
            }

            /*if(aboveSelectedDay == day && aboveSelectEndTime == ''){
                if(startKey > preTimeKey){
                    preTimeKey = startKey;
                    isEndTimeProvide = 1;
                }
                if(endKey > preTimeKey && end != 'null'){
                    preTimeKey = startKey;
                    isEndTimeProvide = 1;
                }
            }*/

            if(selectedVal == day){

                removeItemStart = removeItemStart.concat(newfull[day].slice(startKey, endKey));
            // } else {
                // if(isEndTimeProvide == 1){
                //     removeItemStart = removeItemStart.concat(newfull[day].slice(0, preTimeKey));
                // }
            }
        });
        //NewStartTimeArray[selectedVal] = NewStartTimeArray[selectedVal].filter(a => !removeItemStart.includes(a));
        var startTime = NewStartTimeArray[selectedVal];
        CreateOptions(startTime, id, 'scheduled_time');
        // if(selectedValKey != -1 && aboveselectedDayKey != -1){
        //     if(selectedValKey > aboveselectedDayKey){
                CreateOptions(startTime, id, 'end_time');
            // }
        // }
        storeRow();
    });

    $(document).on('change', '[name="scheduled_time[]"]', function () {
        var id = $(this).attr('id').split("_");
        id = id[2];
        var selectedVal = $(this).val();
        var selectedDay = $('#day_'+id).val();
        var rowData = JSON.parse($('#row-data-val').val());
        var NewEndTimeArray = endFulltime;
        var removeItemEnd = [];
        var keyArray = [];
        var selectedValueIndex = NewEndTimeArray[selectedDay].indexOf(selectedVal);
        if(selectedValueIndex == -1){
            selectedValueIndex = 0;
        }
        $.each(rowData, function (key, val) {
            var ValArr = val.split('|');
            var day = ValArr[0];
            var start = ValArr[1];
            var end = ValArr[2];

            if(selectedDay === day){
                var startKey = NewEndTimeArray[day].indexOf(start); //4
                if(startKey > selectedValueIndex){
                    keyArray.push(startKey);
                }
            }
        });

        var minKey = 48;
        if(keyArray.length > 0){
            minKey = Math.min.apply(Math,keyArray);
        }
        var endTime = NewEndTimeArray[selectedDay].slice(selectedValueIndex+1, minKey+1);
        CreateOptions(endTime, id, 'end_time');
        storeRow();
    });

    function CreateOptions(Time, id, field, selectedVal = null){
        var row = '<option value=""> -Select- </option>';
        $.each(Time, function (Key, Val) {
            row += '<option value="'+Val+'"';
                if (selectedVal != null && selectedVal === Val) {
                    row += 'selected';
                }
            row += '>'+Val+'</option>';
        });
        $('#'+field+'_'+id).empty().append(row);
    }

    function storeRow(){
        var DaysArray = [];
        $('[name="day[]"]').each(function(key, val) {
            DaysArray.push($(this).val());
        });

        var StartTimeArray = [];
        $('[name="scheduled_time[]"]').each(function(key, val) {
            StartTimeArray.push($(this).val());
        });

        var EndTimeArray = [];
        $('[name="end_time[]"]').each(function(key, val) {
            EndTimeArray.push($(this).val());
        });

        var rowDataArray = [];
        $.each(DaysArray, function (key, val) {
            var dayVal,startTimeVal,endTimeVal;
            if(val === ''){
                dayVal = 'null';
            } else {
                dayVal = val;
            }

            if(StartTimeArray[key] === ''){
                startTimeVal = 'null';
            } else {
                startTimeVal = StartTimeArray[key];
            }

            if(EndTimeArray[key] === ''){
                endTimeVal = 'null';
            } else {
                endTimeVal = EndTimeArray[key];
            }

            var tempVal = dayVal+'|'+startTimeVal+'|'+endTimeVal;
            rowDataArray.push(tempVal);
        });
        $('#row-data-val').val(JSON.stringify(rowDataArray));
    }


</script>
@endsection
