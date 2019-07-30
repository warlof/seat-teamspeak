<div class="modal modal-info fade" role="dialog" id="confirm-ts-registration">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">
          <i class="fa fa-headphones"></i>
          Teamspeak Registration
        </h4>
      </div>
      <div class="modal-body">
        <p>Please join the teamspeak server with the information displayed bellow.<br/>
          Once you'll be on the server with proper nickname, click on the <code>Confirm</code> button.</p>
        <form method="post" id="ts-registration-form" class="form-horizontal">
          {{ csrf_field() }}
          <div class="form-group">
            <label class="col-sm-3 control-label" for="ts-server-host">Server Address</label>
            <div class="col-sm-9">
              <input type="text" value="{{ $settings->server_host }}:{{ $settings->server_port }}" readonly="readonly" id="ts-server-host" class="form-control input-sm" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label" for="ts-server-password">Server Password</label>
            <div class="col-sm-9">
              @if(property_exists($settings, 'server_password'))
                <input type="text" value="{{ $settings->server_password }}" readonly="readonly" id="ts-server-password" class="form-control input-sm" />
              @else
                <input type="text" readonly="readonly" id="ts-server-password" class="form-control input-sm" />
              @endif
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label" for="ts-nickname">Nickname</label>
            <div class="col-sm-9">
              <input type="text" value="{{ $registration_nickname }}" readonly="readonly" id="ts-nickname" class="form-control input-sm" />
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline pull-left" type="button" data-dismiss="modal">Close</button>
        <button class="btn btn-success" type="submit" form="ts-registration-form">Confirm</button>
      </div>
    </div>
  </div>
</div>
