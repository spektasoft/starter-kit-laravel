@extends('errors.minimal')

@php
    $defaultMessage = __('Server Error');
    $exceptionMessage =
        config('app.debug') && isset($exception) ? ($exception->getMessage() ?: $defaultMessage) : $defaultMessage;
@endphp

@section('title')
    {{ $exceptionMessage }}
@endsection

@section('code')
    @isset($exception)
        {{ $exception->getStatusCode() }}
    @else
        {{ __('Server Error') }}
    @endisset
@endsection

@section('message')
    {{ $exceptionMessage }}
@endsection
