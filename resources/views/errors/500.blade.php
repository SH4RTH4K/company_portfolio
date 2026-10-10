@extends('errors::minimal')

@section('title', __('Server Error'))
@section('code', '500')
@section('message', app()->getLocale() == 'bn' ? 'সার্ভার ত্রুটি' : 'Server Error')

