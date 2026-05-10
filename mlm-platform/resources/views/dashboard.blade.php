@extends('layouts.app')

@section('content')
<div class="card">
    <h2>Welcome, {{ $user->full_name }}!</h2>
    <p>Referral ID: <strong>{{ $user->referral_id }}</strong></p>
    <div class="stats-grid">
        <div class="stat-item">
            <div class="label">Wallet Balance</div>
            <div class="stat-value">{{ $user->wallet->balance }} BDT</div>
        </div>
        <div class="stat-item">
            <div class="label">Points Balance</div>
            <div class="stat-value">{{ $user->wallet->points_balance }}</div>
        </div>
        <div class="stat-item">
            <div class="label">Active Clubs</div>
            <div class="stat-value">{{ $user->clubs->where('status', 'active')->count() }}</div>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="card">
        <h3>Account Status</h3>
        <p>Type: <strong>{{ $user->hasRole('shopper') ? 'Shopper' : 'Customer' }}</strong></p>
        <p>Status: <span style="color: var(--success)">{{ ucfirst($user->status) }}</span></p>
        <p>Registered: {{ $user->created_at->format('M d, Y') }}</p>
    </div>
    
    <div class="card">
        <h3>Quick Actions</h3>
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <a href="{{ route('wallet') }}" class="btn btn-primary">Add/Withdraw Funds</a>
            <a href="{{ route('network') }}" class="btn btn-success">View My Team</a>
        </div>
    </div>
</div>
@endsection
