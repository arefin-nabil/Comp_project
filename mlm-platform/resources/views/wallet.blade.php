@extends('layouts.app')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>My Wallet</h2>
        <div style="text-align: right">
            <div style="font-size: 0.9rem; color: var(--secondary)">Available Balance</div>
            <div style="font-size: 1.8rem; font-weight: bold; color: var(--primary)">{{ $user->wallet->balance }} BDT</div>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="card">
        <h3>Withdraw Funds</h3>
        <form action="{{ route('withdrawal.request') }}" method="POST">
            @csrf
            <div>
                <label>Amount (BDT)</label>
                <input type="number" name="amount" required min="500" placeholder="Min 500">
            </div>
            <div>
                <label>Method</label>
                <select name="method" required>
                    <option value="bkash">bKash</option>
                    <option value="nagad">Nagad</option>
                    <option value="rocket">Rocket</option>
                    <option value="bank">Bank Transfer</option>
                </select>
            </div>
            <div>
                <label>Account/Number Details</label>
                <input type="text" name="account_details" required placeholder="Phone or Bank details">
            </div>
            <input type="hidden" name="idempotency_key" value="{{ Str::uuid() }}">
            <button type="submit" class="btn btn-primary" style="width: 100%">Request Withdrawal</button>
        </form>
    </div>

    <div class="card" style="grid-column: span 2;">
        <h3>Transaction History</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Category</th>
                    <th>Balance After</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $txn)
                <tr>
                    <td>{{ $txn->created_at->format('d/m/y H:i') }}</td>
                    <td>
                        <span style="color: {{ $txn->type == 'credit' ? 'var(--success)' : 'var(--danger)' }}">
                            {{ strtoupper($txn->type) }}
                        </span>
                    </td>
                    <td>{{ $txn->amount }}</td>
                    <td>{{ ucfirst($txn->category) }}</td>
                    <td>{{ $txn->balance_after }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top: 1rem;">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
