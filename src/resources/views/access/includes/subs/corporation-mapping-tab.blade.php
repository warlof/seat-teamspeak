<table class="table table-condensed table-hover table-responsive">
  <thead>
  <tr>
    <th>{{ trans('teamspeak::seat.corporation') }}</th>
    <th>{{ trans('teamspeak::seat.group') }}</th>
    <th>{{ trans('teamspeak::seat.created') }}</th>
    <th>{{ trans('teamspeak::seat.updated') }}</th>
    <th>{{ trans('teamspeak::seat.status') }}</th>
    <th></th>
  </tr>
  </thead>
  <tbody>
  @foreach($group_corporations as $group)
    <tr>
      <td>{{ $group->corporation->name }}</td>
      <td>{{ $group->group->name }}</td>
      <td>{{ $group->created_at }}</td>
      <td>{{ $group->updated_at }}</td>
      <td>
        @if($group->enable)
          <span class="fa fa-check text-success"></span>
        @else
          <span class="fa fa-times text-danger"></span>
        @endif
      </td>
      <td>
        <div class="btn-group">
          <a href="{{ route('teamspeak.corporation.remove', ['corporation_id' => $group->corporation_id, 'group_id' => $group->teamspeak_sgid]) }}"
             type="button" class="btn btn-danger btn-xs col-xs-12">
            {{ trans('web::seat.remove') }}
          </a>
        </div>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>