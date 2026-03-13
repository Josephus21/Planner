<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Wu Ventures </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])


    <style> 
/* container spacing */
.venture-list {
    margin-top: 20px;
}

/* each item */
.venture-item {
    font-size: 20px;
    font-weight: 500;
    transition: all 0.3s ease;
}

/* hover effect */
.venture-item:hover {
    transform: translateX(8px);
    color: #2563eb;
}

/* bullet dot */
.dot {
    width: 12px;
    height: 12px;
    background: #2563eb;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 10px rgba(37,99,235,0.6);
    transition: transform 0.3s ease;
}

/* dot animation on hover */
.venture-item:hover .dot {
    transform: scale(1.5);
}

/* text */
.venture-text {
    letter-spacing: 1px;
}

    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50">
    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">

            <!-- Left -->
            <div class="hidden lg:block">
                <h1 class="text-6xl font-bold text-blue-600 mb-4">Wu Ventures HRIS</h1>
                <p class="text-2xl text-slate-700 mb-8">Look Good. Print Bold. Stay Fresh</p>

                
            </div>

            <!-- Right -->
            <div class="w-full max-w-md mx-auto">
                <div class="bg-white shadow-2xl rounded-3xl p-8 border border-slate-200">
                    <h2 class="text-2xl font-bold text-slate-800 mb-6 text-center">Log In</h2>

                    @if (session('status'))
                        <div class="mb-4 rounded-lg bg-green-100 text-green-700 px-4 py-3 text-sm">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter your email"
                            >
                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter your password"
                            >
                            @error('password')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <label for="remember_me" class="inline-flex items-center">
                                <input id="remember_me" type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-slate-600">Remember me</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:underline">
                                    Forgot password?
                                </a>
                            @endif
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 transition"
                        >
                            Log In
                        </button>

                       
                    </form>
                </div>
            </div>

        </div>
    </div> 
</body>
</html>