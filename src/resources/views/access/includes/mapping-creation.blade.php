<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{{ trans('teamspeak::seat.quick_create') }}</h3>
  </div>
  <div class="panel-body">
    <form role="form" action="{{ route('teamspeak.add') }}" method="post">
      {{ csrf_field() }}
      <div class="box-body">

        <div class="form-group">
          <label for="teamspeak-type">{{ trans('teamspeak::seat.type') }}</label>
          <select name="teamspeak-type" id="teamspeak-type" class="col-md-12">
            <option value="user">{{ trans('teamspeak::seat.user_filter') }}</option>
            <option value="role">{{ trans('teamspeak::seat.role_filter') }}</option>
            <option value="corporation">{{ trans('teamspeak::seat.corporation_filter') }}</option>
            <option value="title">{{ trans('teamspeak::seat.title_filter') }}</option>
            <option value="alliance">{{ trans('teamspeak::seat.alliance_filter') }}</option>
            <option value="public">{{ trans('teamspeak::seat.public_filter') }}</option>
          </select>
        </div>

        <div class="form-group">
          <label for="teamspeak-user-id">{{ trans('teamspeak::seat.username') }}</label>
          <select name="teamspeak-user-id" id="teamspeak-user-id" class="col-md-12">
            @foreach($users as $user)
              @if ($user->id != 1)
                <option value="{{ $user->group_id }}">{{ $user->name }}</option>
              @endif
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label for="teamspeak-role-id">{{ trans('teamspeak::seat.role') }}</label>
          <select name="teamspeak-role-id" id="teamspeak-role-id" class="col-md-12" disabled="disabled">
            @foreach($roles as $role)
              <option value="{{ $role->id }}">{{ $role->title }}</option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label for="teamspeak-corporation-id">{{ trans('teamspeak::seat.corporation') }}</label>
          <select name="teamspeak-corporation-id" id="teamspeak-corporation-id" class="col-md-12" disabled="disabled">
            @foreach($corporations as $corporation)
              <option value="{{ $corporation->corporation_id }}">{{ $corporation->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label for="teamspeak-title-id">{{ trans('teamspeak::seat.title') }}</label>
          <select name="teamspeak-title-id" id="teamspeak-title-id" class="col-md-12" disabled="disabled"></select>
        </div>

        <div class="form-group">
          <label for="teamspeak-alliance-id">{{ trans('teamspeak::seat.alliance') }}</label>
          <select name="teamspeak-alliance-id" id="teamspeak-alliance-id" class="col-md-12" disabled="disabled">
            @foreach($alliances as $alliance)
              <option value="{{ $alliance->alliance_id }}">{{ $alliance->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label for="teamspeak-group-id">{{ trans('teamspeak::seat.group') }}</label>
          <select name="teamspeak-group-id" id="teamspeak-group-id" class="col-md-12">
            @foreach($groups as $group)
              <option value="{{ $group->id }}">{{ $group->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label for="teamspeak-enabled">{{ trans('teamspeak::seat.enabled') }}</label>
          <input type="checkbox" name="teamspeak-enabled" id="teamspeak-enabled" checked="checked" value="1"/>
        </div>

      </div>

      <div class="box-footer">
        <button type="submit" class="btn btn-primary pull-right">{{ trans('teamspeak::seat.add') }}</button>
      </div>

    </form>
  </div>
</div>