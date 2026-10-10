@extends('errors::minimal')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message', app()->getLocale() == 'bn' ? 'প্রবেশ নিষেধ' : 'Forbidden')

