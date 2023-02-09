@extends($extends)

@section($section)
    <div class="row">
        <div class="col-md-12">
            <!-- begin panel -->
            <div class="panel panel-inverse">
                <div class="panel-heading">
                    <div class="panel-heading-btn">
                        <a href="{{ route('admin.languages.index') }}" class="btn btn-xs btn-success"><i class="fa fa-undo"></i> {{ Arr::get($uiTranslations, 'back_to_list') }}</a>
                    </div>

                    <h4 class="panel-title">
                        {{ Arr::get($uiTranslations, 'create_language') }}
                    </h4>
                </div>
                <div class="panel-body">

                    @include($viewNamespace.'::partials.messages')

                    {!! Form::open(['route' => [ 'admin.languages.store'], 'class' => 'form-horizontal']) !!}

                    @include($viewNamespace.'::languages.form')

                    <div class="form-group">
                        <div class="col-md-8 col-md-offset-4">
                            <button type="submit" class="btn btn-primary m-r-5 pull-right"><i class="fa fa-save"></i> {{ Arr::get($uiTranslations, 'save') }}</button>
                        </div>
                    </div>

                    {!! Form::close()!!}

                </div>
            </div>
            <!-- end panel -->
        </div>
        <!-- end col-12 -->
    </div>
    <!-- end row -->
@endsection
