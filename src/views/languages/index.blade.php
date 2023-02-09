@extends($extends)

@section('content')

    @include($viewNamespace . '::languages.scripts')
    @include($viewNamespace . '::languages.styles')

    <div class="row">
        <div class="col-md-12">
            <!-- begin panel -->
            <div class="panel panel-inverse">
                <div class="panel-heading">
                    <div class="panel-heading-btn">
                        <a href="{{ route('admin.languages.create')  }}"
                           class="btn btn-xs btn-success"><i class="fa fa-plus"></i> {{ Arr::get($uiTranslations, 'create') }}</a>
                    </div>

                    <h4 class="panel-title">
                        {{ Arr::get($uiTranslations, 'languages') }}
                    </h4>
                </div>
                <div class="panel-body">
                    @include($viewNamespace.'::partials.messages')

                    <table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered"
                           id="languages-index-datatable">
                        <thead>
                        <tr>
                            <th>{{ Arr::get($uiTranslations, 'title') }}</th>
                            <th>{{ Arr::get($uiTranslations, 'title_localized') }}</th>
                            <th>{{ Arr::get($uiTranslations, 'iso_code') }}</th>
                            <th>{{ Arr::get($uiTranslations, 'fallback') }}?</th>
                            <th>{{ Arr::get($uiTranslations, 'visible') }}?</th>
                            <th>{{ Arr::get($uiTranslations, 'actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($languages as $language)
                            <tr>
                                <td>{{ $language->title }}</td>
                                <td>{{ $language->title_localized }}</td>
                                <td>{{ $language->iso_code }}</td>
                                <td>
                                    {{ $language->is_fallback ? Arr::get($uiTranslations, 'yes') : Arr::get($uiTranslations, 'no') }}
                                </td>
                                <td>
                                    {{ $language->is_visible ? Arr::get($uiTranslations, 'yes') : Arr::get($uiTranslations, 'no') }}
                                </td>
                                <td width="10%" class="text-center">
                                    @include( $viewNamespace . '::partials.edit_button', [
                                        'route' => 'admin.languages.edit',
                                        'row'   => $language
                                    ])

                                    @include($viewNamespace . '::partials.destroy_button', [
                                        'route'         => 'admin.languages.destroy',
                                        'data_id'       => $language->iso_code,
                                        'row'           => $language,
                                        'title'         => Arr::get($uiTranslations, 'are_you_sure'),
                                        'text'          => Arr::get($uiTranslations, 'language_will_be_deleted'),
                                        'confirm'       => Arr::get($uiTranslations, 'accept'),
                                        'success_title' => Arr::get($uiTranslations, 'deleted'),
                                        'success_text'  => Arr::get($uiTranslations, 'language_deleted'),
                                    ])
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
