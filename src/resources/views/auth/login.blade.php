@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="login-form__content">
    <div class="login-form__heading">
        <h1>{{ isset($guard) ? "管理者ログイン" : "ログイン" }}</h1>
    </div>
    <form class="form__content" action="{{ isset($guard) ? route('admin.login') : route('login') }}" method="post" novalidate>
        @csrf
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">メールアドレス</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="email" name="email" value="{{ old('email') }}" />
                </div>
                <div class="form__error">
                    @error('email')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">パスワード</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="password" name="password" />
                </div>
                <div class="form__error">
                    @error('password')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form__button">
            <button class="form__button-submit" type="submit">{{ isset($guard) ? "管理者ログインする" : "ログインする" }}</button>
        </div>
    </form>
    <div class="{{ isset($guard) ? "register__link-hidden" : "register__link" }}">
     <a class="register__button-submit" href="/register">会員登録はこちら</a>
    </div>
</div>



@endsection

