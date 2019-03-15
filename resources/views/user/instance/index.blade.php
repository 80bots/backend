@extends('layouts.app')

@section('css')
    <link rel="stylesheet" type="text/css" href="{{asset('assets/pages/datatables.css')}}">
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <!-- NEW WIDGET START -->
            <article class="col-12">
                <!-- Widget ID (each widget will need unique ID)-->
                <div class="jarviswidget jarviswidget-color-darken no-padding" id="wid-id-0"
                     data-widget-editbutton="false">
                    <header>
                        <div class="widget-header">
                            <span class="widget-icon"> <i class="fa fa-table"></i> </span>
                            <h2>Inspection Listing</h2>
                        </div>

                        <div class="widget-toolbar">
                            <!-- add: non-hidden - to disable auto hide -->
                        </div>
                    </header>
                    <div>
                        <!-- widget edit box -->
                        <div class="jarviswidget-editbox">
                            <!-- This area used as dropdown edit box -->
                        </div>
                        <!-- end widget edit box -->
                        <!-- widget content -->
                        <div class="widget-body p-0">
                            <table id="dt_basic"
                                   class="table table-striped table-bordered table-hover"
                                   width="100%">
                                <thead>
                                <tr>
                                    <th data-hide="phone">Name</th>
                                    <th data-class="expand">Instance Id</th>
                                    <th data-hide="phone">AWS Public Ip</th>
                                    <th data-hide="phone,tablet">AWS Public DNS</th>
                                    <th data-hide="phone,tablet">Status</th>
                                    <th data-hide="phone,tablet">Launch Time</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @if(isset($UserInstance) && !empty($UserInstance))
                                    @foreach($UserInstance as $instance)
                                        <tr>
                                            <td>{{!empty($instance->name) ? $instance->name : ''}}</td>
                                            <td>{{!empty($instance->aws_instance_id) ? $instance->aws_instance_id : ''}}</td>
                                            <td>{{!empty($instance->aws_public_ip) ? $instance->aws_public_ip : ''}}</td>
                                            <td>{{!empty($instance->aws_public_dns) ? $instance->aws_public_dns : ''}}</td>
                                            <td>{{!empty($instance->status) ? $instance->status : ''}}</td>
                                            <td>{{!empty($instance->created_at) ? $instance->created_at : ''}}</td>
                                            <td><a href="{{!empty($instance->aws_pem_file_path) ? route('download-pem',['path' => $instance->aws_pem_file_path]) : 'javascript:void(0)'}}" title="Download pem file">
                                                    <i class="fa fa-download"></i>
                                                </a></td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>
                        <!-- end widget content -->
                    </div>
                    <!-- end widget div -->
                </div>
            </article>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">

        // DO NOT REMOVE : GLOBAL FUNCTIONS!


        /* // DOM Position key index //

        l - Length changing (dropdown)
        f - Filtering input (search)
        t - The Table! (datatable)
        i - Information (records)
        p - Pagination (paging)
        r - pRocessing
        < and > - div elements
        <"#id" and > - div with an id
        <"class" and > - div with a class
        <"#id.class" and > - div with an id and class

        Also see: http://legacy.datatables.net/usage/features
        */

        /* BASIC ;*/
        var responsiveHelper_dt_basic = responsiveHelper_dt_basic || undefined;
        var responsiveHelper_datatable_fixed_column = responsiveHelper_datatable_fixed_column || undefined;
        var responsiveHelper_datatable_col_reorder = responsiveHelper_datatable_col_reorder || undefined;
        var responsiveHelper_datatable_tabletools = responsiveHelper_datatable_tabletools || undefined;

        var breakpointDefinition = {
            tablet: 1024,
            phone: 480
        };

        $('#dt_basic').dataTable({
            "sDom": "<'dt-toolbar d-flex'<f><'ml-auto hidden-xs show-control'l>r>" +
                "t" +
                "<'dt-toolbar-footer d-flex'<'hidden-xs'i><'ml-auto'p>>",
            "autoWidth": true,
            "oLanguage": {
                "sSearch": '<span class="input-group-addon"><i class="fa fa-search"></i></span>'
            },
            classes: {
                sWrapper: "dataTables_wrapper dt-bootstrap4"
            },
            responsive: true
        });

        /* END BASIC */

        /* COLUMN FILTER  */
        var otable = $('#datatable_fixed_column').DataTable({
            //"bFilter": false,
            //"bInfo": false,
            //"bLengthChange": false
            //"bAutoWidth": false,
            //"bPaginate": false,
            //"bStateSave": true // saves sort state using localStorage
            "sDom": "<'dt-toolbar d-flex align-items-center'<'hidden-xs'f><'ml-auto hidden-xs'<'right-toolbar'>>r>" +
                "t" +
                "<'dt-toolbar-footer d-flex'<'hidden-xs'i><'ml-auto'p>>",
            "autoWidth": true,
            "classes": {
                "sWrapper": "dataTables_wrapper dt-bootstrap4"
            },
            "oLanguage": {
                "sSearch": '<span class="input-group-addon"><i class="fa fa-search"></i></span>'
            },
            responsive: true

        });

        // custom toolbar
        $("div.right-toolbar").html('<div class="text-right"><img src="{{asset('assets/img/common/sa-logo.png')}}" alt="{{config('app.name')}}" style="width: 111px;"></div>');

        // Apply the filter
        $("#datatable_fixed_column thead th input[type=text]").on('keyup change', function () {

            otable
                .column($(this).parent().index() + ':visible')
                .search(this.value)
                .draw();

        });
        /* END COLUMN FILTER */

        /* COLUMN SHOW - HIDE */
        $('#datatable_col_reorder').dataTable({
            "sDom": "<'dt-toolbar d-flex align-items-center'<f><'hidden-xs ml-auto'B>r>" +
                "t" +
                "<'dt-toolbar-footer d-flex'<'hidden-xs'i><'ml-auto'p>>",
            "autoWidth": true,
            "classes": {
                "sWrapper": "dataTables_wrapper dt-bootstrap4"
            },
            "oLanguage": {
                "sSearch": '<span class="input-group-addon"><i class="fa fa-search"></i></span>'
            },
            buttons: [{
                extend: 'colvis',
                text: 'Show / hide columns',
                className: 'btn btn-default',
                columnText: function (dt, idx, title) {
                    return title;
                }
            }],

            responsive: true
        });

        /* END COLUMN SHOW - HIDE */

        /* TABLETOOLS */
        $('#datatable_tabletools').dataTable({

            // Tabletools options:
            //   https://datatables.net/extensions/tabletools/button_options
            "sDom": "<'dt-toolbar d-flex'<f><'hidden-xs ml-auto'B>r>" +
                "t" +
                "<'dt-toolbar-footer d-flex'<'hidden-xs'i><'ml-auto'p>>",
            "oLanguage": {
                "sSearch": '<span class="input-group-addon"><i class="fa fa-search"></i></span>'
            },
            "classes": {
                "sWrapper": "dataTables_wrapper dt-bootstrap4"
            },
            buttons: [{
                extend: 'print',
                className: 'btn btn-default'
            }],
            "autoWidth": true

        });

        /* END TABLETOOLS */


    </script>
@endsection
