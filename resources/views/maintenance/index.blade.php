@extends('master')

@section('content')
    <div class="row">
        <div class="col-md-12">
            Supported by <a href="https://api.gnavi.co.jp/api/scope/" target="_blank">ぐるなびWebService</a>
            <p style="text-align:center;color:#ff0000;">maintenance page</p>
            <div class="tab_wrap">
                <input id="tab1" type="radio" name="tab_btn" checked>
                <input id="tab2" type="radio" name="tab_btn">
                <input id="tab3" type="radio" name="tab_btn">

                <div class="tab_area">
                    <label class="tab1_label fa fa-beer" for="tab1">&nbsp;new</label>
                    <label class="tab2_label fa fa-edit" for="tab2">&nbsp;area</label>
                    <label class="tab3_label fa fa-user" for="tab3">&nbsp;type</label>
                </div>
                <div class="panel_area">
                    <div id="panel1" class="tab_panel">
                        <maintenance v-bind:view-data="{{ $viewData }}" v-bind:tab-check="{{ $tabCheckData['shop'] }}"></maintenance>
                        <div class="page_change_area">
                            @if ($pageCount > 1)
                                @for ($i=1;$i<=$pageCount;$i++)
                                    @if ($i == $pageOffset)
                                        <a class="active_page_btn" href="javascript:void(0)">{{ $i }}</a>
                                    @else
                                        <a class="page_change_btn" href="/pagemainte/{{ $i }}">{{ $i }}</a>
                                    @endif
                                @endfor
                            @endif
                        </div>
                    </div>
                    <div id="panel2" class="tab_panel">
                        <maintenance v-bind:area-data="{{ $areaViewData }}" v-bind:tab-check="{{ $tabCheckData['area'] }}" v-bind:area-count="{{ $areaCount }}"></maintenance>
                    </div>
                    <div id="panel3" class="tab_panel">
                        <maintenance v-bind:category-data="{{ $categoryViewData }}" v-bind:tab-check="{{ $tabCheckData['category'] }}" v-bind:category-count="{{ $categoryCount }}"></maintenance>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
