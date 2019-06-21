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
            <form id="CreateSchedulerForm" name="" action="{{route('user.scheduling.store')}}" method="post">
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
                        <div class="col-sm-4 border-right">
                            Days
                        </div>
                        <div class="col-sm-4 border-right">
                            Start Time
                        </div>
                        <div class="col-sm-4 align-items-center">
                            End Time
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

<script type="text/javascript" src="{{asset('js/jquery-3.3.1.min.js')}}"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-form-validator/2.3.26/jquery.form-validator.min.js"></script>
<script>
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
                        data.push(TimeVal+' '+'AM');
                    } else {
                        data.push(TimeVal+' '+val);
                    }
                });
            });
            fullTime[weekVal] = data;
            endFulltime[weekVal] = data;
        });
    };

    function addSchedulerRow(ids = null, day = null, start_time = null, end_time = null) {
        storeRow();
        var numRow = $('#scheduler-row .row').length;
        var asides = ['AM', 'PM'];
        var row =
            '<div class="row">\n';
        if (ids === null) {
            ids = [0, 0];
        }
        row += '<input type="hidden" name="ids[]" value="' + ids + '" >';

        row += '    <div class="col-sm-4 border-right">\n' +
            '        <div class="form-group">\n' +
            '            <select name="day[]" id="day_'+numRow+'" class="form-control">\n' +
            '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '    <div class="col-sm-4 border-right">\n' +
            '        <div class="form-group">\n' +
            '            <select name="start_time[]" id="start_time_'+numRow+'" class="form-control">\n' +
            '                <option value="">-Select-</option>\n';
            row += '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '    <div class="col-sm-4">\n' +
            '        <div class="form-group">\n' +
            '            <select name="end_time[]" id="end_time_'+numRow+'" class="form-control">\n' +
            '                <option value="">-Select-</option>\n';
            row += '            </select>\n' +
            '        </div>\n' +
            '    </div>\n' +
            '</div>';

        $('#scheduler-row').append(row);
        CreateOptions(weekDays, numRow, 'day', day);
        if(start_time != null){
            start_time = convertUtcToUser(start_time);
            CreateOptions(fullTime[day], numRow, 'start_time', start_time);
        }
        if(end_time != null){
            end_time = convertUtcToUser(end_time);
            CreateOptions(fullTime[day], numRow, 'end_time', end_time);
        }
    }

    function convertUtcToUser(time){
        var url = '{{url('user/scheduling/convert-time-utc-to-user')}}/'+time+'/'+current_time_zone;
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
                        var start_time = scheduling_instance_details[i].selected_time;
                        var end = scheduling_instance_details[i + 1].schedule_type;
                        var end_time = scheduling_instance_details[i + 1].selected_time;


                        var row =
                            '<div class="row">\n';
                        var ids = [];
                        if (scheduling_instance_details[i] != '' && scheduling_instance_details[i + 1] != '') {
                            ids.push(scheduling_instance_details[i].id);
                            ids.push(scheduling_instance_details[i + 1].id);
                        }
                        addSchedulerRow(ids, day, start_time, end_time);

                        i = i + 2;
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
                end = '12:30 PM';
            }
            var startKey, endKey;
            startKey = fullTime[day].indexOf(start);
            endKey = fullTime[day].indexOf(end);
            if(endKey == 47){
                endKey += 1;
            }

            if(aboveSelectedDay == day && aboveSelectEndTime == ''){
                if(startKey > preTimeKey){
                    preTimeKey = startKey;
                    isEndTimeProvide = 1;
                }
            }

            if(selectedVal == day){
                removeItemStart = removeItemStart.concat(newfull[day].slice(startKey, endKey));
            } else {
                if(isEndTimeProvide == 1){
                    removeItemStart = removeItemStart.concat(newfull[day].slice(0, preTimeKey));
                }
            }
        });

        NewStartTimeArray[selectedVal] = NewStartTimeArray[selectedVal].filter(a => !removeItemStart.includes(a));
        var startTime = NewStartTimeArray[selectedVal];
        CreateOptions(startTime, id, 'start_time');
        if(selectedValKey != -1 && aboveselectedDayKey != -1){
            if(selectedValKey > aboveselectedDayKey){
                CreateOptions(startTime, id, 'end_time');
            }
        }
    });

    $(document).on('change', '[name="start_time[]"]', function () {
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
        $('[name="start_time[]"]').each(function(key, val) {
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
