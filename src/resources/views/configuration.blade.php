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

                    <div class="form-group">
                        <label for="teamspeak-configuration-token" class="col-md-4">Server Hostname</label>
                        <div class="col-md-7">
                            <div class="input-group input-group-sm">
                                @if (is_null(setting('teamspeak_hostname', true)))
                                <input type="text" class="form-control" id="teamspeak-configuration-hostname" name="teamspeak-configuration-hostname" />
                                @else
                                <input type="text" class="form-control" id="teamspeak-configuration-hostname" name="teamspeak-configuration-hostname" value="{{ setting('teamspeak_hostname', true) }}" />
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
                                @if (is_null(setting('teamspeak_server_port', true)))
                                    <input type="text" class="form-control" id="teamspeak-configuration-port" name="teamspeak-configuration-port" />
                                @else
                                    <input type="text" class="form-control" id="teamspeak-configuration-port" name="teamspeak-configuration-port" value="{{ setting('teamspeak_server_port', true) }}" />
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
                                @if (is_null(setting('teamspeak_server_query', true)))
                                    <input type="text" class="form-control" id="teamspeak-configuration-query" name="teamspeak-configuration-query" />
                                @else
                                    <input type="text" class="form-control" id="teamspeak-configuration-query" name="teamspeak-configuration-query" value="{{ setting('teamspeak_server_query', true) }}" />
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
                                @if (is_null(setting('teamspeak_username', true)))
                                    <input type="text" class="form-control" id="teamspeak-configuration-username" name="teamspeak-configuration-username" />
                                @else
                                    <input type="text" class="form-control" id="teamspeak-configuration-username" name="teamspeak-configuration-username" value="{{ setting('teamspeak_username', true) }}" />
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
                                @if (is_null(setting('teamspeak_password', true)))
                                    <input type="text" class="form-control" id="teamspeak-configuration-password" name="teamspeak-configuration-password" />
                                @else
                                    <input type="text" class="form-control" id="teamspeak-configuration-password" name="teamspeak-configuration-password" value="{{ setting('teamspeak_password', true) }}" />
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
                                @if (setting('teamspeak_tags', true) == '')
                                    <input type="checkbox" class="form-check-input" id="teamspeak-configuration-tags" name="teamspeak-configuration-tags" />
                                @else
                                    <input type="checkbox" class="form-check-input" id="teamspeak-configuration-tags" name="teamspeak-configuration-tags" checked />
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
                    @if($green_settings == '')
                        <a href="#" type="button" class="btn btn-success btn-md col-md-12 disabled" role="button">Update Teamspeak server groups</a>
                    @else
                        <a href="{{ route('teamspeak.command.run', ['commandName' => 'teamspeak:groups:update']) }}" type="button" class="btn btn-success btn-md col-md-12" role="button">Update Teamspeak server groups</a>
                    @endif
                    <span class="help-block">
                        This will update known Teamspeak server groups.
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
                        <img src="https://poser.pugx.org/warlof/seat-teamspeak/v/stable" alt="Teamspeak version" />
                    </a>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script type="application/javascript">
        $('#hostname-eraser').click(function(){
            $('#teamspeak-configuration-hostname').val('');
        });

        $('#port-eraser').click(function(){
            $('#teamspeak-configuration-port').val('');
        });

        $('#query-eraser').click(function(){
            $('#teamspeak-configuration-query').val('');
        });

        $('#username-eraser').click(function(){
            $('#teamspeak-configuration-username').val('');
        });

        $('#password-eraser').click(function(){
            $('#teamspeak-configuration-password').val('');
        });

        $('[data-toggle="tooltip"]').tooltip();
    </script>
@stop
