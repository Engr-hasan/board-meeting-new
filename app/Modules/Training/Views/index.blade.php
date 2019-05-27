@extends('layouts.admin')

@section('content')
    <?php
    $accessMode = ACL::getAccsessRight('Training');
    if (!ACL::isAllowed($accessMode, 'A'))
        die('no access right!');

    ?>

    <div class="col-lg-12">

        {!! Session::has('success') ? '<div class="alert alert-success alert-dismissible"><button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>'. Session::get("success") .'</div>' : '' !!}
        {!! Session::has('error') ? '<div class="alert alert-danger alert-dismissible"><button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>'. Session::get("error") .'</div>' : '' !!}
        <div class="panel panel-primary">
            <div class="panel-heading">

                <div class="pull-left" style="font-size: large">
                   {{trans('messages.training_material_list')}}

                </div>
                <div class="pull-right">       {{trans('')}}
                    <a class="" href="{{ url('Training/create') }}">
                        {!! Form::button('<i class="fa fa-plus"></i> <strong>'.trans('messages.new_training_material').'</strong>', array('type' => 'button', 'class' => 'btn btn-info')) !!}
                    </a>
                        </div>

                <div class="clearfix"></div>


            </div>

            <!-- /.panel-heading -->
            <div class="panel-body">

                <div class="tab-content">
                    <div class="table-responsive">
                        <table id="training_list" class="table table-striped table-bordered dt-responsive nowrap" cellspacing="0" width="100%">
                            <thead class="alert alert-info">
                            <tr>
                                <th>User Types</th>
                                <th>Training Title</th>
                                <th>Training Details</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                </div><!-- /.table-responsive -->
            </div><!-- /.panel-body -->
        </div><!-- /.panel -->
    </div><!-- /.col-lg-12 -->
@endsection

@section('footer-script')
    @include('partials.datatable-scripts')
    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
    <script>

        $(function () {
            $('#training_list').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{url("Training/get-training-details-data")}}',
                    method: 'get',
                    data: function (d) {
                        d._token = $('input[name="_token"]').val();
                    }
                },
                columns: [
                    {data: 'user_types', name: 'user_types'},
                    {data: 'title', name: 'title'},
                    {data: 'description', name: 'description'},
                    {data: 'status', name: 'status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                "aaSorting": []
            });

        });
/*
        function togglePassportList(id)
        {
            $('.span_id_'+id).toggle();
        }
        */
    </script>
@endsection
