@extends('layouts.app')

@section('content')
<div class="card" style="max-width: 500px; margin: 2rem auto;">
    <h2 style="text-align: center">Join Our Network</h2>
    <form action="{{ route('register.process') }}" method="POST">
        @csrf
        <div>
            <label>Full Name</label>
            <input type="text" name="full_name" required value="{{ old('full_name') }}">
        </div>
        <div>
            <label>Phone Number</label>
            <input type="text" name="phone" required placeholder="01XXXXXXXXX" value="{{ old('phone') }}">
        </div>
        <div>
            <label>Referrer ID (Required)</label>
            <input type="text" name="referrer_id" required value="{{ request('ref') ?? old('referrer_id') }}">
            <small>Ask your upline for their Referral ID.</small>
        </div>
        <div>
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div>
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" required>
        </div>
        
        <div style="font-size: 0.85rem; color: var(--secondary); margin-bottom: 1.5rem;">
            By registering, you agree to pay the membership fee of 100 BDT upon first login to activate your account.
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%">Create Account</button>
    </form>
    <p style="text-align: center; margin-top: 1rem;">
        Already a member? <a href="{{ route('login') }}">Login Here</a>
    </p>
</div>
@endsection
