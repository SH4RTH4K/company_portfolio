@extends('errors::minimal')

@section('title', __('Too Many Requests'))
@section('code', '429')
@section('message', app()->getLocale() == 'bn' ? 'অতিরিক্ত অনুরোধ' : 'Too Many Requests')

