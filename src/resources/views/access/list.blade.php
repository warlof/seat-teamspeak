@extends('web::layouts.grids.3-9')

@section('title', trans('teamspeak::seat.management'))
@section('page_header', trans('teamspeak::seat.management'))

@section('left')

  @include('teamspeak::access.includes.mapping-creation')

@stop

@section('right')

  @include('teamspeak::access.includes.mapping-table')

@stop

@push('javascript')
  <script type="application/javascript">
      function getCorporationTitle() {
          $('#teamspeak-title-id').empty();

          $.ajax('{{ route('teamspeak.api.acl.titles') }}', {
              data: {
                  corporation_id: $('#teamspeak-corporation-id').val()
              },
              dataType: 'json',
              method: 'GET',
              success: function (data) {
                  for (var i = 0; i < data.length; i++) {
                      $('#teamspeak-title-id').append($('<option></option>').attr('value', data[i].id).text(data[i].name));
                  }
              }
          });
      }

      $('#teamspeak-type').change(function () {
          $.each(['teamspeak-user-id', 'teamspeak-role-id', 'teamspeak-corporation-id', 'teamspeak-title-id', 'teamspeak-alliance-id'], function (key, value) {
              if (value === ('teamspeak-' + $('#teamspeak-type').val() + '-id')) {
                  $(('#' + value)).prop('disabled', false);
              } else {
                  $(('#' + value)).prop('disabled', true);
              }
          });

          if ($('#teamspeak-type').val() === 'title') {
              $('#teamspeak-corporation-id, #teamspeak-title-id').prop('disabled', false);
          }
      }).select2();

      $('#teamspeak-corporation-id').change(function () {
          getCorporationTitle();
      });

      $('#teamspeak-user-id, #teamspeak-role-id, #teamspeak-corporation-id, #teamspeak-title-id, #teamspeak-alliance-id, #teamspeak-channel-id, #teamspeak-group-id').select2();

      $('#teamspeak-tabs').find('a').click(function (e) {
          e.preventDefault();
          $(this).tab('show');
      });

      $(document).ready(function () {
          getCorporationTitle();
      });
  </script>
@endpush
