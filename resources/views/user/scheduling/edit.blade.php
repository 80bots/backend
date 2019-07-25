@extends('layouts.app')

@section('title')
    {{ __('user.scheduling.title') . ' ' . __('keywords.edit') }}
@endsection

@section('css')

@endsection

@section('content')
    <link rel="stylesheet" type="text/css" media="screen"
     href="{{ asset('css/tempusdominus-bootstrap-4.min.css')}}">
   <div class="wrapper">
    <form class="card" id="scheduling_update" action="{{route('user.scheduling.update',$id)}}" method="post">
        @method('PATCH')
            @csrf
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">{{ __('keywords.scheduling.add') }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        <label for="">{{ __('keywords.scheduling.select_instance') }}*</label>
                        <p> {{isset($scheduling->userInstances['aws_instance_id']) ? $scheduling->userInstances['aws_instance_id'] : ''}}</p>

                        <input type="hidden" name="user_instances_id" value="{{ $scheduling->user_instances_id }}">
                        <!-- <select name="user_instances_id" id="user_instances_id" class="form-control">
                            <option value="">Select Instance </option>
                            @foreach($instances as $row)
                            <option value="{{$row->id}}" {{ $scheduling->user_instances_id == $row->id ? 'selected="selected"' : '' }} > {{$row->aws_instance_id}}</option>
                            @endforeach
                        </select> -->
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        <label for="">{{ __('keywords.status') }}*</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">{{ __('user.scheduling.select_status') }}</option>
                            <option {{ $scheduling->status == 'active' ? 'selected="selected"' : '' }} value="active">
                                {{ __('keywords.scheduling.statuses.active') }}
                            </option>
                            <option {{ $scheduling->status == 'inactive' ? 'selected="selected"' : '' }}  value="inactive">
                                {{ __('keywords.scheduling.statuses.active') }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        <label for="">{{ __('keywords.scheduling.start_time') }}*</label>
                          <div class="input-group date time-picker" id="startTimePicker" data-target-input="nearest">
                                <input id="start_time" type="text" class="form-control datetimepicker-input" data-target="#startTimePicker" value="{{isset($scheduling->start_time) ? $scheduling->start_time : ''}}" name="start_time" data-toggle="datetimepicker"/>
                                <div class="input-group-append" data-target="#startTimePicker" data-toggle="datetimepicker">
                                    <div class="input-group-text"><i class="fas fa-clock"></i></div>
                                </div>
                            </div>
                        <!-- <input type="text"  name="start_time" class="form-control"/> -->
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        <label for="">{{ __('keywords.scheduling.end_time') }}*</label>
                        <div class="input-group date time-picker" id="endTimePicker" data-target-input="nearest">
                                <input id="end_time" type="text" class="form-control datetimepicker-input" value="{{isset($scheduling->end_time) ? $scheduling->end_time : ''}}" data-target="#endTimePicker" data-toggle="datetimepicker" name="end_time"/>
                                <div class="input-group-append" data-target="#endTimePicker" data-toggle="datetimepicker">
                                    <div class="input-group-text"><i class="fas fa-clock"></i></div>
                                </div>
                            </div>
                        <!-- <input type="text" name="end_time" class="form-control"/> -->
                    </div>
                </div>

                <input type="hidden" name="utc_start_time"  value="{{isset($scheduling->utc_start_time) ? $scheduling->utc_start_time : ''}}" id="utc_start_time" >
                <input type="hidden" id="utc_end_time" value="{{isset($scheduling->utc_end_time) ? $scheduling->utc_end_time : ''}}"  name="utc_end_time">
                <input type="hidden" id="current_time_zone" name="current_time_zone" value="{{isset($scheduling->current_time_zone) ? $scheduling->current_time_zone : ''}}">
            </div>
        </div>
        <div class="card-footer text-right">
            <button type="submit" class="btn btn-primary btn-round">{{ __('keywords.update') }}</button>
        </div>
    </form>
</div>

@endsection
@section('script')
    <script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script src="{{ asset('js/jquery.validate.min.js')  }}" type="text/javascript"></script>
    <script>
        function get_local_to_utc_time(time)
        {
            var current_d = moment().format('YYYY-MM-DD')+' '+time;
            var localDate = new Date(current_d);
            return moment.utc( localDate ).format('HH:mm');
        }
        var current_time_zone =  moment().format('Z');
        $('#current_time_zone').val(current_time_zone);

        $(function() {  
            $('#startTimePicker').datetimepicker({
               format: 'HH:mm',
               // startDate: moment().startOf('hour'),
               // endDate:moment().endOf(String);
            });
            $('#endTimePicker').datetimepicker({
               format: 'HH:mm',
               // startDate: moment().startOf('hour'),
               // endDate:moment().endOf(String);
            });

            $("#startTimePicker").on("change.datetimepicker", function (e) {
                $('#endTimePicker').datetimepicker('minDate', e.date);
                var end_time = $('#end_time').val();
                if(end_time)
                { 
                    end_utc_time = get_local_to_utc_time(end_time);
                    $('#utc_end_time').val(end_utc_time);  
                }
            });
            $("#endTimePicker").on("change.datetimepicker", function (e) {
                $('#startTimePicker').datetimepicker('maxDate', e.date);
            });


            $('#start_time').on('blur',function(){
                var start_time = $('#start_time').val();
                start_utc_time = get_local_to_utc_time(start_time);

                $('#utc_start_time').val(start_utc_time);
            });
            $('#end_time').on('blur',function(){
                var end_time = $('#end_time').val();
                end_utc_time = get_local_to_utc_time(end_time);
                $('#utc_end_time').val(end_utc_time);
            })

        });
        $("#scheduling_update").validate({
            rules: {
                user_instances_id: {
                    required: true
                },
                start_time: {
                    required: true
                },
                end_time: {
                    required: true
                }
            }
        });
    </script>
@endsection
