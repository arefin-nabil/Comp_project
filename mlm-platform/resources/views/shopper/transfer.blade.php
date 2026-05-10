@extends('layouts.app')

@section('content')
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h2>Shopper Transfer</h2>
    <p>Transfer BDT to a customer to complete a purchase and trigger MLM rewards.</p>
    
    <form action="{{ route('shopper.transfer.process') }}" method="POST">
        @csrf
        <div>
            <label>Customer Phone Number</label>
            <input type="text" name="phone" required placeholder="01XXXXXXXXX" id="customer_phone">
            <button type="button" id="check_customer" class="btn" style="margin-top: 0.5rem; background: #e2e8f0;">Check Customer</button>
            <div id="customer_info" style="margin-top: 0.5rem; font-weight: bold; color: var(--primary)"></div>
        </div>

        <div>
            <label>Transfer Amount (BDT)</label>
            <input type="number" name="amount" required min="1" step="0.01">
        </div>

        <input type="hidden" name="idempotency_key" value="{{ Str::uuid() }}">
        
        <div style="background: #f8fafc; padding: 1rem; border-radius: 4px; font-size: 0.9rem;">
            <strong>Note:</strong> 
            40% of this amount will go to Customer as Cashback. <br>
            60% will be converted to Points for the Customer. <br>
            Team Income will be distributed to 10 levels.
        </div>

        <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 1.5rem;">Process Transfer</button>
    </form>
</div>

<script>
    document.getElementById('check_customer').addEventListener('click', async function() {
        const phone = document.getElementById('customer_phone').value;
        const infoDiv = document.getElementById('customer_info');
        
        if (!phone) return alert('Enter phone number');
        
        infoDiv.innerText = 'Checking...';
        
        try {
            const response = await fetch(`/shopper/check-customer?phone=${phone}`);
            const data = await response.json();
            
            if (data.success) {
                infoDiv.innerText = `Customer Found: ${data.data.full_name}`;
                infoDiv.style.color = 'var(--success)';
            } else {
                infoDiv.innerText = 'Customer not found.';
                infoDiv.style.color = 'var(--danger)';
            }
        } catch (e) {
            infoDiv.innerText = 'Error checking customer.';
        }
    });
</script>
@endsection
