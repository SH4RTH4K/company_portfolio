@extends('errors::minimal')

@section('title', __('Not Found'))
@section('code', '404')
@section('message', app()->getLocale() == 'bn' ? 'পৃষ্ঠাটি পাওয়া যায়নি' : 'Page Not Found')

