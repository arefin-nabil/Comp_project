@extends('layouts.app')

@section('content')
<div class="card" style="max-width: 400px; margin: 4rem auto;">
    <h2 style="text-align: center">User Login</h2>
    <form action="{{ route('login.process') }}" method="POST">
        @csrf
        <div>
            <label>Phone Number</label>
            <input type="text" name="phone" required placeholder="01XXXXXXXXX">
        </div>
        <div>
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%">Login</button>
    </form>
    <p style="text-align: center; margin-top: 1rem;">
        Don't have an account? <a href="{{ route('register') }}">Register Here</a>
    </p>
</div>
@endsection
