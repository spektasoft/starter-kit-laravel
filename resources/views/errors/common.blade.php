@extends('errors.minimal')

@php
    $exceptionMessage = isset($exception) ? ($exception->getMessage() ?: __('Server Error')) : __('Server Error');
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
