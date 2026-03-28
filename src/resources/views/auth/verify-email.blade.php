@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/email.css') }}">
@endsection

@section('content')
<div class="verify__content">
    <div class="verify__heading">
        <h1>メール認証画面</h1>
        <p class="verify-text">登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。</p>
        <div class="verify-button-box">
            <a class="verify-button" href="http://localhost:8025/">認証はこちら</a>
        </div>
        <div class="verify-form-box">
            <form action="{{ url('/email/verification-notification') }}" method="POST">
            @csrf
            <button class="resend" href="">認証メールを再送する</button>
            </form>
        </div>
    </div>
</div>
@endsection