@extends('web::layouts.grids.6-6')

@section('title', trans('teamspeak::register'))
@section('page_header', trans('teamspeak::register'))

@section('left')
    <div class="box box-primary" id="register-box">
        <div class="box-header with-border">
            <h3 class="box-title">Register my Teamspeak User</h3>
        </div>
        <div class="box-body">
            <p>Log into the Teamspeak server with your nickname set to the EXACT SAME as your Main character's name.</p>

            <p>Your Teamspeak Nickname should be <b>{{ $teamspeak_username }}</b></p>
            <div class="form-group has-feedback" id="forms">
              <div class="input-group input-group-sm">
                  <input type="text" class="form-control loading" id="ts3id" name="ts3id" readonly="readonly" />
                  <span class="input-group-btn">
                      <button type="button" id="ts3register" name="ts3register" class="btn btn-info btn-flat">Click to find you and register</button>
                  </span>
              </div>
            </div>

            <a href="ts3server://{{ setting('teamspeak_hostname', true) }}?port={{ setting('teamspeak_server_port', true) }}&nickname={{ $teamspeak_username }}" class="btn btn-success pull-right">Join the server</a>
        </div>
    </div>
@stop

@push('javascript')
<script type="application/javascript">
    $('#ts3register').on('click', function () {
        $('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>')
            .appendTo($('#register-box'));

        $.post({
            url: "{{ route('teamspeak.api.user') }}"
        }).done(function (data) {
            $('#forms').removeClass('has-error');
            $('#forms').addClass('has-success');
            $('#verify').removeClass('fa-times text-red');
            $('#verify').addClass('fa-check text-green');
            $('#ts3id').val(data.id);
        }).fail(function (jqXHR) {
            var error = 'An unhandled error occurred. Please contact your administrator.';

            if (jqXHR.responseJSON.hasOwnProperty('error'))
                error = jqXHR.responseJSON.error;

            $('#forms').removeClass('has-success');
            $('#forms').addClass('has-error');
            $('#verify').removeClass('fa-check text-green');
            $('#verify').addClass('fa-times text-red');
            $('#ts3id').val(error);
        }).always(function() {
            $('#register-box').find('.overlay').remove();
        });
    });
</script>
@endpush
