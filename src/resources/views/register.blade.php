@extends('web::layouts.grids.6-6')

@section('title', trans('teamspeak::register'))
@section('page_header', trans('teamspeak::register'))

@section('left')
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Register my Teamspeak User</h3>
        </div>
        <div class="panel-body">
            <p>Log into the Teamspeak server with your nickname set to the EXACT SAME as your Main character's name.</p>
            <p>TS Name should be: <b>{{ setting('main_character_name') }}</b><p>
            <input type="text" id="ts3id" name="ts3id" value="" maxlength="29" size="29" disabled="true" class="loading">
            <input type="button" id="ts3register" name="ts3register" value="Click to find your name and register">
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
</style>

<script type="application/javascript">
    $('.loading').css('background-image', 'none');

    $('#ts3register').on('click', function () {
      $('.loading').css('background-image', 'url("http://loadinggif.com/images/image-selection/3.gif")');
      $.ajax({
        headers: function() {},
        url: 'https://devcc.cripplecreekcorp.com/teamspeak/getuserid',
        dataType: 'json',
        jsonp: false,
        contentType: "application/json",
      }).done(function (data) {
        $('.loading').css('background-image', 'none');
        if (data.id) {
          $('#ts3id').val(data.id);
        }
        else {
          $('#ts3id').val("Username not Found on TS");
        }
      });
    });
</script>
@endpush

