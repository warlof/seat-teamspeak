@extends('web::layouts.grids.4-4-4')

@section('title', trans('teamspeak::seat.settings'))
@section('page_header', trans('teamspeak::seat.settings'))

@section('left')
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Configuration</h3>
    </div>
    <div class="panel-body">
      <form role="form" action="{{ route('teamspeak.configuration.post') }}" method="post" class="form-horizontal">

        <div class="box-body">

          <legend>Teamspeak</legend>

          @if(! is_null(setting(\Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup::SERVER_HOSTNAME_KEY, true)))
            <p class="callout callout-warning text-justify">It appears you already have a Teamspeak Server access setup.
              In order to prevent any mistakes, <code>Server Hostname</code>, <code>Server Port</code>, <code>Server
                Query Port</code>,
              <code>Server Query Username</code> and <code>Server Query Password</code> fields have been disabled.<br/>
              Please use the rubber in order to enable modifications.</p>
          @endif

          <div class="form-group">
            <label for="teamspeak-configuration-hostname" class="col-md-4">Server Hostname</label>
            <div class="col-md-7">
              <div class="input-group input-group-sm">
                @if (is_null(setting(\Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup::SERVER_HOSTNAME_KEY, true)))
                  <input type="text" class="form-control" id="teamspeak-configuration-hostname"
                         name="teamspeak-configuration-hostname"/>
                @else
                  <input type="text" class="form-control" id="teamspeak-configuration-hostname"
                         name="teamspeak-configuration-hostname"
                         value="{{ setting(\Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup::SERVER_HOSTNAME_KEY, true) }}"
                         readonly/>
                @endif
                {{ csrf_field() }}
                <span class="input-group-btn">
                                    <button type="button" class="btn btn-danger btn-flat" id="hostname-eraser">
                                        <i class="fa fa-eraser"></i>
                                    </button>
                                </span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="teamspeak-configuration-port" class="col-md-4">Server Port</label>
            <div class="col-md-7">
              <div class="input-group input-group-sm">
                @if (is_null(setting(\Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup::SERVER_INSTANCE_PORT_KEY, true)))
                  <input type="text" class="form-control" id="teamspeak-configuration-port"
                         name="teamspeak-configuration-port"/>
                @else
                  <input type="text" class="form-control" id="teamspeak-configuration-port"
                         name="teamspeak-configuration-port"
                         value="{{ setting(\Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup::SERVER_INSTANCE_PORT_KEY, true) }}"
                         readonly/>
                @endif
                <span class="input-group-btn">
                                    <button type="button" class="btn btn-danger btn-flat" id="port-eraser">
                                        <i class="fa fa-eraser"></i>
                                    </button>
                                </span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="teamspeak-configuration-query" class="col-md-4">Server Query Port</label>
            <div class="col-md-7">
              <div class="input-group input-group-sm">
                @if (is_null(setting(\Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup::SERVER_QUERY_PORT_KEY, true)))
                  <input type="text" class="form-control" id="teamspeak-configuration-query"
                         name="teamspeak-configuration-query"/>
                @else
                  <input type="text" class="form-control" id="teamspeak-configuration-query"
                         name="teamspeak-configuration-query"
                         value="{{ setting(\Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup::SERVER_QUERY_PORT_KEY, true) }}"
                         readonly/>
                @endif
                <span class="input-group-btn">
                                    <button type="button" class="btn btn-danger btn-flat" id="query-eraser">
                                        <i class="fa fa-eraser"></i>
                                    </button>
                                </span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="teamspeak-configuration-username" class="col-md-4">Server Query Username</label>
            <div class="col-md-7">
              <div class="input-group input-group-sm">
                @if (is_null(setting(\Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup::SERVER_QUERY_USERNAME_KEY, true)))
                  <input type="text" class="form-control" id="teamspeak-configuration-username"
                         name="teamspeak-configuration-username"/>
                @else
                  <input type="text" class="form-control" id="teamspeak-configuration-username"
                         name="teamspeak-configuration-username"
                         value="{{ setting(\Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup::SERVER_QUERY_USERNAME_KEY, true) }}"
                         readonly/>
                @endif
                <span class="input-group-btn">
                                    <button type="button" class="btn btn-danger btn-flat" id="username-eraser">
                                        <i class="fa fa-eraser"></i>
                                    </button>
                                </span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="teamspeak-configuration-password" class="col-md-4">Server Query Password</label>
            <div class="col-md-7">
              <div class="input-group input-group-sm">
                @if (is_null(setting(\Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup::SERVER_QUERY_PASSWORD_KEY, true)))
                  <input type="text" class="form-control" id="teamspeak-configuration-password"
                         name="teamspeak-configuration-password"/>
                @else
                  <input type="text" class="form-control" id="teamspeak-configuration-password"
                         name="teamspeak-configuration-password"
                         value="{{ setting(\Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup::SERVER_QUERY_PASSWORD_KEY, true) }}"
                         readonly/>
                @endif
                <span class="input-group-btn">
                                    <button type="button" class="btn btn-danger btn-flat" id="password-eraser">
                                        <i class="fa fa-eraser"></i>
                                    </button>
                                </span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="teamspeak-configuration-tags" class="col-md-4">Use Corp Tags?</label>
            <div class="col-md-7">
              <div class="form-check">
                @if (setting('warlof.teamspeak-connector.tags', true) !== true)
                  <input type="checkbox" class="form-check-input" id="teamspeak-configuration-tags"
                         name="teamspeak-configuration-tags"/>
                @else
                  <input type="checkbox" class="form-check-input" id="teamspeak-configuration-tags"
                         name="teamspeak-configuration-tags" checked/>
                @endif
              </div>
            </div>
          </div>
        </div>

        <div class="box-footer">
          <button type="submit" class="btn btn-primary pull-right">Update</button>
        </div>

      </form>
    </div>
  </div>
@stop

@section('center')
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Commands</h3>
    </div>
    <div class="panel-body">
      <div class="form-group">
        <div class="col-md-12">
          @if(is_null(setting(\Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup::SERVER_HOSTNAME_KEY, true)))
            <button type="button" id="sync-groups" class="btn btn-success btn-md col-md-12 disabled" role="button">
              Update Teamspeak server groups
            </button>
          @else
            <button type="button" id="sync-groups" class="btn btn-success btn-md col-md-12" role="button">Update
              Teamspeak server groups
            </button>
          @endif
          <span class="help-block">
                        This will update known Teamspeak server groups.
                    </span>
        </div>
      </div>
      <div class="form-group">
        <div class="col-md-12">
          @if(is_null(setting(\Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup::SERVER_HOSTNAME_KEY, true)))
            <button type="button" id="reset-command" class="btn btn-danger btn-md col-md-12 disabled" role="button">
              Reset everybody
            </button>
          @else
            <button type="button" id="reset-command" class="btn btn-danger btn-md col-md-12" role="button">Reset
              everybody
            </button>
          @endif
          <span class="help-block">
                        This will remove roles from every members into the connected Teamspeak Server.
                        Please proceed carefully.
                    </span>
        </div>
      </div>
    </div>
  </div>
@stop

@section('right')
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title"><i class="fa fa-rss"></i> Update feed</h3>
    </div>
    <div class="panel-body" style="height: 500px; overflow-y: scroll">
      {!! $changelog !!}
    </div>
    <div class="panel-footer">
      <div class="row">
        <div class="col-md-6">
          Installed version: <b>{{ config('teamspeak.config.version') }}</b>
        </div>
        <div class="col-md-6">
          Latest version:
          <a href="https://packagist.org/packages/warlof/seat-teamspeak">
            <img src="https://poser.pugx.org/warlof/seat-teamspeak/v/stable" alt="Teamspeak version"/>
          </a>
        </div>
      </div>
    </div>
  </div>
@stop

@push('javascript')
  <script type="application/javascript">
      $(function () {
          ['hostname', 'port', 'query', 'username', 'password'].forEach(function (value) {
              $('#' + value + '-eraser').on('click', function () {
                  var element = $('#teamspeak-configuration-' + value);

                  element.val('');
                  element.removeAttr('readonly');
                  element.focus();
              });
          });

          $('#sync-groups').on('click', function () {
              $.post('{{ route('teamspeak.command.run') }}', {
                  command: 'teamspeak:group:sync'
              });
          });

          $('#reset-command').on('click', function () {
              $.post('{{ route('teamspeak.command.run') }}', {
                  command: 'teamspeak:user:policy',
                  parameters: {
                      '--terminator': true
                  }
              });
          });
      });

      $('[data-toggle="tooltip"]').tooltip();
  </script>
@endpush
