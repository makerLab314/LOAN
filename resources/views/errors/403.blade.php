@extends('errors.layout')
@section('code', '403')
@section('title', __('Kein Zugriff'))
@section('message', (isset($exception) && $exception->getMessage() && $exception->getMessage() !== 'This action is unauthorized.') ? $exception->getMessage() : __('Dafür fehlen dir die Rechte. Wende dich an die Administration.'))
