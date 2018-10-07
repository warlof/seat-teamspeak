@extends('web::layouts.grids.3-9')

@section('title', trans('teamspeak::seat.management'))
@section('page_header', trans('teamspeak::seat.management'))

@section('left')
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Information</h3>
    </div>
    <div class="panel-body">
      <p>This section display Teamspeak related event.
        You will for example find which user and when it has been added or removed from a group.</p>
      <p>It will display settings issue as well like if people didn't change their mail address.</p>
    </div>
  </div>

  <div class="box box-solid">
    <div class="box-header with-border">
      <h3 class="box-title">Legend</h3>
    </div>
    <div class="box-body">
      <ul class="list-unstyled">
        <li>
          <span class="label label-info">&nbsp;</span> Common information message
        </li>
        <li>
          <span class="label label-success">&nbsp;</span> Success information message, like good news
        </li>
        <li>
          <span class="label label-warning">&nbsp;</span> Important information message
        </li>
        <li>
          <span class="label label-danger">&nbsp;</span> Information message related to an error
        </li>
      </ul>
    </div>
  </div>
@stop

@section('right')
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Event logs</h3>
    </div>
    <div class="panel-body">
      <table class="table table-condensed table-hover table-responsive no-margin">
        <thead>
        <tr>
          <th>{{ trans('web::seat.date') }}</th>
          <th>{{ trans('web::seat.category') }}</th>
          <th>{{ trans('web::seat.message') }}</th>
        </tr>
        </thead>
        <tbody>

        @foreach($logs as $log)
          <tr>
            <td>{{ $log->created_at }}</td>
            <td>
              @if ($log->event == 'mail')
                <span class="label label-danger">{{ $log->event }}</span>
              @elseif($log->event == 'invite')
                <span class="label label-success">{{ $log->event }}</span>
              @elseif($log->event == 'kick')
                <span class="label label-warning">{{ $log->event }}</span>
              @else
                <span class="label label-info">{{ $log->event }}</span>
              @endif
            </td>
            <td>{{ $log->message }}</td>
          </tr>
        @endforeach

        </tbody>
      </table>
    </div>
    <div class="panel-footer clearfix">
      @if($logs->count() == 0)
        <a href="#" type="button" class="btn btn-danger btn-sm pull-right disabled" role="button">
          Clear</a>
      @else
        <a href="{{ route('teamspeak.command.run', ['commandName' => 'teamspeak:logs:clear']) }}" type="button"
           class="btn btn-danger btn-sm pull-right" role="button">Clear</a>
      @endif
    </div>
  </div>
@stop