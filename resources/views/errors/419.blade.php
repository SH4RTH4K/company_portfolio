@extends('errors::minimal')

@section('title', __('Page Expired'))
@section('code', '419')
@section('message', app()->getLocale() == 'bn' ? 'পৃষ্ঠার মেয়াদ শেষ' : 'Page Expired')

