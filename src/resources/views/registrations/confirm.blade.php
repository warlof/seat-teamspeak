@extends('seat-connector::identities.list')

@section('identity-modal')
  @include('seat-connector-teamspeak::registrations.includes.modal')
@endsection

@push('javascript')
  <script>
    $(document).ready(function() {
      $('#confirm-ts-registration').modal('toggle');
    });
  </script>
@endpush
