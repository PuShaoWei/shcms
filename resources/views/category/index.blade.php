@extends('layouts.app')
@section('header')
    <style>
        #articles{
            line-height: 2em;
            height: 400px;
            overflow: hidden;
        }
    </style>
@endsection
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">分类</div>

                    <div class="panel-body">
                        <script type="application/html" id="articles-tmpl">
                            <li>
                                <a href="{url}">{title}</a>
                            </li>
                        </script>
                        <ul id="articles" class="list-inline">
                            @foreach($categories as $category)
                                <li>
                                    <a href="{{ $category -> showUrl() }}">{{ $category['title'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('footer')
@endsection