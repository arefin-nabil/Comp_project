<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'MLM Platform') }}</title>
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #64748b;
            --success: #22c55e;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
        }
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--light); margin: 0; color: var(--dark); line-height: 1.6; }
        
        /* Navbar */
        nav { background: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        nav a { text-decoration: none; color: var(--dark); font-weight: 600; margin-left: 1.5rem; }
        nav .logo { font-size: 1.5rem; color: var(--primary); margin-left: 0; }
        
        /* Container */
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        
        /* Components */
        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        .btn { display: inline-block; padding: 0.6rem 1.2rem; border-radius: 4px; text-decoration: none; cursor: pointer; border: none; font-weight: 600; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        
        form div { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.3rem; font-weight: 600; }
        input, select { width: 100%; padding: 0.6rem; border: 1px solid #ddd; border-radius: 4px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #eee; }
        th { background: #f1f5f9; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .stat-item { padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0; }
        .stat-value { font-size: 1.5rem; font-weight: bold; color: var(--primary); }
    </style>
</head>
<body>

<nav>
    <a href="/" class="logo">MLM Platform</a>
    <div>
        @auth
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('wallet') }}">Wallet</a>
            <a href="{{ route('network') }}">Network</a>
            @if(auth()->user()->isShopper())
                <a href="{{ route('shopper.transfer') }}" style="color: var(--success)">Transfer</a>
            @endif
            <form action="{{ route('logout') }}" method="POST" style="display:inline; margin-left: 1.5rem;">
                @csrf
                <button type="submit" style="background:none; border:none; cursor:pointer; font-weight:600; color:var(--danger)">Logout</button>
            </form>
        @else
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('register') }}">Register</a>
        @endauth
    </div>
</nav>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @yield('content')
</div>

</body>
</html>
