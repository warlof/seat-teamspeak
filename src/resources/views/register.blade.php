@extends('web::layouts.grids.6-6')

@section('title', trans('teamspeak::register'))
@section('page_header', trans('teamspeak::register'))

@section('left')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Register my Teamspeak User</h3>
        </div>
        <div class="box-body">
            <p>Log into the Teamspeak server with your nickname set to the EXACT SAME as your Main character's name.</p>
	    
            <p>TS Name should be: <b>

              {{ main_character }}
			  
            <div class="form-group has-feedback" id="forms">
              <div class="input-group input-group-sm">
                  <input type="text" class="form-control loading" id="ts3id" name="ts3id" value="" />
                  <span class="input-group-btn">
                      <button type="button" id="ts3register" name="ts3register" class="btn btn-info btn-flat">Click to find your name and register</button>
                  </span>
              </div>
            </div>
        </div>
    </div>
@stop

@section('right')
@stop

@push('javascript')
<style>
.loading {    
    background-color: #ffffff;
    background-size: 20px 20px;
    background-position:right center;
    background-repeat: no-repeat;
}

</style>

<script type="application/javascript">
    $('.loading').css('background-image', 'none');

    $('#ts3register').on('click', function () {
      $('.loading').css('background-image', 'url("{{ asset('web/img/spinner.gif') }}")');
      $.ajax({
        headers: function() {},
        url: "{{ route('teamspeak.getclients') }}",
        dataType: 'json',
        jsonp: false,
        contentType: "application/json",
      }).done(function (data) {
        $('.loading').css('background-image', 'none');
        if (data.id) {
          $('#forms').removeClass('has-error');
          $('#forms').addClass('has-success');
          $('#verify').removeClass('fa-times text-red');
          $('#verify').addClass('fa-check text-green');
          $('#ts3id').val(data.id);
        }
        else {
          $('#forms').removeClass('has-success');
          $('#forms').addClass('has-error');
          $('#verify').removeClass('fa-check text-green');
          $('#verify').addClass('fa-times text-red');
          $('#ts3id').val("Username not Found on TS");
        }
      });
    });
</script>
@endpush

