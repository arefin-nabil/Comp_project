@extends('layouts.app')

@section('content')
<div class="card">
    <h2>My Network</h2>
    <p>Your Referral ID: <strong>{{ $user->referral_id }}</strong></p>
</div>

<div class="stats-grid">
    <div class="card">
        <h3>Downline Summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Level</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($levelCounts as $level => $count)
                <tr>
                    <td>Level {{ $level }}</td>
                    <td><strong>{{ $count }}</strong> members</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card" style="grid-column: span 2;">
        <h3>Direct Referrals (Level 1)</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Joined</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($directReferrals as $ref)
                <tr>
                    <td>{{ $ref->full_name }}</td>
                    <td>{{ $ref->phone }}</td>
                    <td>{{ $ref->created_at->format('d M Y') }}</td>
                    <td>{{ ucfirst($ref->status) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center">No direct referrals yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
