@extends('layouts.app')

@section('title')
Scheduling instances
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="align-items-center bg-purple d-flex p-3 rounded shadow-sm text-white-50 mb-3">
            <h4 class="border mb-0 mr-2 pb-2 pl-3 pr-3 pt-2 rounded text-white">8</h4>
            <div class="lh-100">
                <h6 class="mb-0 text-white lh-100">80bots</h6>
                <small>Since 2019</small>
            </div>
        </div>
        @include('layouts.imports.messages')
        @if(!empty($results) && isset($results))
            
            <div class="table-responsive">
                <table id="scheduling_instances" class="table thead-default vertical-middle mb-0">
                    <thead>
                    <tr>
                        <th>Instance Id</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(isset($results) && !empty($results))
                        @foreach($results as $row)
                            <tr>
                                <td> {{!empty($row->user_instances_id) ? $row->user_instances_id : ''}}</td>
                                <td> {{!empty($row->start_time) ? $row->start_time : ''}}</td>
                                <td> {{!empty($row->end_time) ? $row->end_time : ''}}</td>
                                <td> {{!empty($row->status) ? $row->status : ''}}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{route('user.scheduling.edit',$row->id)}}" class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"
                                        title="Edit Bot"><i class="fa fa-edit"></i></a>
                                       <form action="{{ route('user.scheduling.destroy',$row->id) }}" method="POST">
                                         @csrf
                                            @method('DELETE')
                                                <button type="submit" onclick="return confirm('Are you sure? you want to remove this record')" class="form-group btn btn-icon btn-danger change-credit-model mb-0"><i class="fa fa-trash"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection