@extends('web::layouts.grids.12')

@section('title', trans('teamspeak::seat.user_mapping'))
@section('page_header', trans('teamspeak::seat.user_mapping'))

@section('full')

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{{ trans_choice('web::seat.user', 2) }}</h3>
        </div>
        <div class="panel-body">
            <table class="table table-condensed table-hover table-responsive no-margin" id="users-table" data-page-length="25">
                <thead>
                    <tr>
                        <th>SeAT Group ID</th>
                        <th>SeAT User ID</th>
                        <th>SeAT Username</th>
                        <th>Teamspeak ID</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
            <form method="post" id="user-remove" action="{{ route('teamspeak.users.remove') }}" class="hidden">
                {{ csrf_field() }}
                <input type="hidden" name="teamspeak_id" />
            </form>
        </div>
    </div>

@endsection

@push('head')
<link rel="stylesheet" type="text/css" href="{{ asset('web/css/wt-discord-hook.css') }}" />
@endpush

@push('javascript')
<script type="text/javascript">
    $(function() {
        var table = $('table#users-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('teamspeak.users') }}',
            columns: [
                {data: 'group_id', type: 'num'},
                {data: 'user_id', type: 'num'},
                {data: 'user_name', type: 'string'},
                {data: 'teamspeak_id', type: 'string'},
                {
                    data: null,
                    targets: -1,
                    defaultContent: '<button class="btn btn-xs btn-danger">Remove</button>',
                    orderable: false
                }
            ],
            "fnDrawCallback": function(){
                $(document).ready(function(){
                    $('img').unveil(100);
                });
            }
        });

        $('#users-table').find('tbody')
            .on('click', 'button.btn-danger', function(){
                var data = table.row($(this).parents('tr')).data();
                $('#user-remove').find('input[name="teamspeak_id"]').val(data.teamspeak_id).parent().submit();
            });
    });
</script>
@endpush