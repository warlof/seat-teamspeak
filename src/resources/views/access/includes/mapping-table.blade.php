<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{{ trans('teamspeak::seat.authorisations') }}</h3>
  </div>
  <div class="panel-body">

    <ul class="nav nav-pills" id="teamspeak-tabs">
      <li role="presentation" class="active">
        <a href="#teamspeak-public">{{ trans('teamspeak::seat.public_filter') }}</a>
      </li>
      <li role="presentation">
        <a href="#teamspeak-user">{{ trans('teamspeak::seat.user_filter') }}</a>
      </li>
      <li role="presentation">
        <a href="#teamspeak-role">{{ trans('teamspeak::seat.role_filter') }}</a>
      </li>
      <li role="presentation">
        <a href="#teamspeak-title">{{ trans('teamspeak::seat.title_filter') }}</a>
      </li>
      <li role="presentation">
        <a href="#teamspeak-corporation">{{ trans('teamspeak::seat.corporation_filter') }}</a>
      </li>
      <li role="presentation">
        <a href="#teamspeak-alliance">{{ trans('teamspeak::seat.alliance_filter') }}</a>
      </li>
    </ul>

    <div class="tab-content">
      <div role="tabpanel" class="tab-pane active" id="teamspeak-public">
        @include('teamspeak::access.includes.subs.public-mapping-tab')
      </div>
      <div role="tabpanel" class="tab-pane" id="teamspeak-user">
        @include('teamspeak::access.includes.subs.user-mapping-tab')
      </div>
      <div role="tabpanel" class="tab-pane" id="teamspeak-role">
        @include('teamspeak::access.includes.subs.role-mapping-tab')
      </div>
      <div role="tabpanel" class="tab-pane" id="teamspeak-title">
        @include('teamspeak::access.includes.subs.title-mapping-tab')
      </div>
      <div role="tabpanel" class="tab-pane" id="teamspeak-corporation">
        @include('teamspeak::access.includes.subs.corporation-mapping-tab')
      </div>
      <div role="tabpanel" class="tab-pane" id="teamspeak-alliance">
        @include('teamspeak::access.includes.subs.alliance-mapping-tab')
      </div>
    </div>
  </div>
</div>