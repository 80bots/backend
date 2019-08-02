@extends('layouts.app')

@section('title')
    {{ __('keywords.scheduling.title') }}
@endsection

@section('css')
    <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('css/tempusdominus-bootstrap-4.min.css')}}">
@endsection

@section('content')
    <div class="wrapper">
        @include('includes.banner')
        @include('layouts.imports.messages')
        <form class="card" id="scheduling_create" action="{{route('scheduling.store')}}" method="post">
            @csrf
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">{{ __('keywords.scheduling.add') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.scheduling.select_instance') }}*</label>
                            <select name="user_instances_id" id="user_instances_id" class="form-control">
                                <option value="">{{ __('keywords.scheduling.select_instance') }}</option>
                                @foreach($instances as $row)
                                <option value="{{$row->id}}"> {{$row->aws_instance_id}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.status') }}*</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">{{ __('keywords.scheduling.select_status') }}</option>
                                <option value="active">{{ __('keywords.scheduling.statuses.active') }}</option>
                                <option value="inactive">{{ __('keywords.scheduling.statuses.inactive') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.scheduling.start_time') }}*</label>
                                <div class="input-group date time-picker" id="startTimePicker" data-target-input="nearest">
                                    <input type="text" class="form-control datetimepicker-input" id="start_time"
                                           data-target="#startTimePicker" name="start_time" data-toggle="datetimepicker"/>
                                    <div class="input-group-append" data-target="#startTimePicker"
                                         data-toggle="datetimepicker">
                                        <div class="input-group-text"><i class="fas fa-clock"></i></div>
                                    </div>
                                </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">End time*</label>
                            <div class="input-group date time-picker" id="endTimePicker" data-target-input="nearest">
                                <input id="end_time" type="text" class="form-control datetimepicker-input"
                                       data-target="#endTimePicker" data-toggle="datetimepicker" name="end_time"/>
                                <div class="input-group-append" data-target="#endTimePicker" data-toggle="datetimepicker">
                                    <div class="input-group-text"><i class="fas fa-clock"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="utc_start_time" id="utc_start_time" value=""/>
                    <input type="hidden" id="utc_end_time" name="utc_end_time" value=""/>
                    <input type="hidden" id="current_time_zone" name="current_time_zone" value=""/>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary btn-round">{{ __('keywords.add') }}</button>
            </div>
        </form>
    </div>
@endsection

@section('script')
 <script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
 <script type="text/javascript" src="{{ asset('js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script src="{{ asset('js/jquery.validate.min.js')  }}" type="text/javascript"></script>
    <script type="text/javascript">
        function get_local_to_utc_time(time) {
            let current_d = moment().format('YYYY-MM-DD')+' '+time;
            let localDate = new Date(current_d);
            return moment.utc( localDate ).format('HH:mm');
        }

        const current_time_zone =  moment().format('Z');
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

            $("#startTimePicker").on('change.datetimepicker', function (e) {
                $('#endTimePicker').datetimepicker('minDate', e.date);
                const end_time = $('#end_time').val();
                if(end_time) {
                    const end_utc_time = get_local_to_utc_time(end_time);
                    $('#utc_end_time').val(end_utc_time);  
                }    
            });
            $("#endTimePicker").on('change.datetimepicker', function (e) {
                $('#startTimePicker').datetimepicker('maxDate', e.date);
            });

            $('#start_time').on('blur',function(){
                const start_time = $('#start_time').val();
                const start_utc_time = get_local_to_utc_time(start_time);
                $('#utc_start_time').val(start_utc_time);

            });
            $('#end_time').on('blur',function(){
                const end_time = $('#end_time').val();
                const end_utc_time = get_local_to_utc_time(end_time);
                $('#utc_end_time').val(end_utc_time);
            })
        });

        $("#scheduling_create").validate({
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
