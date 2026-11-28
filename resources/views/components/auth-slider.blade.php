@props(['mode' => 'login'])

@php
    // Pastikan mode hanya 'login' atau 'register'
    $mode = $mode === 'register' ? 'register' : 'login';

    // Error & input lama hanya ditampilkan di form yang sedang aktif,
    // supaya error "email" milik login tidak ikut muncul di form register.
    $error = fn (string $field, string $form) => $mode === $form ? $errors->first($field) : null;
    $old   = fn (string $field, string $form) => $mode === $form ? old($field) : null;

    // Gaya input (dipakai ulang di semua field)
    $inputClass = 'mt-1 block w-full rounded-md border-0 bg-[#EEEEEE] px-4 py-3 text-sm text-gray-800 placeholder-gray-500 focus:ring-2 focus:ring-blue-400';

    // Gaya tombol utama
    $btnClass = 'mx-auto block w-full max-w-[260px] rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#1F3F78]';

    // Ikon logo labu (ukuran bisa diatur)
    $logo = fn (string $size = 'h-12 w-12') => '<span class="inline-flex '.$size.' items-center justify-center rounded-xl bg-blue-600"><svg class="h-1/2 w-1/2 text-white" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 3h6M10 3v6.5L5 19a1.5 1.5 0 001.3 2.2h11.4A1.5 1.5 0 0019 19l-5-9.5V3"/></svg></span>';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $mode === 'login' ? 'Login' : 'Registrasi' }} - {{ config('app.name', 'LABORA') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">

        {{-- KONTAINER UTAMA. State Alpine: mode = 'login' | 'register' --}}
        <div x-data="{
                mode: '{{ $mode }}',
                urls: { login: '{{ route('login') }}', register: '{{ route('register') }}' },
                go(m) {
                    this.mode = m;
                    history.replaceState(null, '', this.urls[m]);
                }
             }"
             class="relative min-h-screen overflow-hidden bg-[#1F3F78] md:h-screen">

            {{-- ============ FORM LOGIN (sisi kiri) ============ --}}
            <section :class="mode === 'login' ? 'block md:opacity-100' : 'hidden md:block md:invisible md:opacity-0'"
                     class="overflow-y-auto md:absolute md:inset-y-0 md:left-0 md:w-1/2 md:transition-all md:duration-700"
                     aria-label="Form login">
                <div class="flex min-h-screen items-center justify-center px-6 py-10 md:min-h-full md:px-12">
                    <div class="w-full max-w-md">

                        {{-- Logo (hanya mobile, karena panel putih disembunyikan) --}}
                        <div class="mb-6 flex items-center justify-center gap-2 md:hidden">
                            {!! $logo('h-9 w-9') !!}
                            <span class="text-2xl font-extrabold tracking-wide text-white">LABORA</span>
                        </div>

                        <h1 class="text-center text-4xl font-extrabold text-white md:text-5xl">Login</h1>
                        <p class="mt-1 text-center text-sm text-white">Masuk menggunakan akun yang telah terdaftar.</p>

                        {{-- Session status (mis. setelah reset password) --}}
                        @if ($mode === 'login' && session('status'))
                            <p class="mt-4 rounded-md bg-green-500/20 px-3 py-2 text-center text-sm text-green-200">{{ session('status') }}</p>
                        @endif

                        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                            @csrf

                            <div>
                                <label for="login_email" class="block text-xs font-semibold text-white">Email</label>
                                <input id="login_email" type="email" name="email" value="{{ $old('email', 'login') }}"
                                       placeholder="nama@kampus.ac.id" required autocomplete="username"
                                       @if ($mode === 'login') autofocus @endif
                                       class="{{ $inputClass }}">
                                @if ($message = $error('email', 'login'))
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @endif
                            </div>

                            <div>
                                <label for="login_password" class="block text-xs font-semibold text-white">Password</label>
                                <input id="login_password" type="password" name="password"
                                       placeholder="Masukkan password" required autocomplete="current-password"
                                       class="{{ $inputClass }}">
                                @if ($message = $error('password', 'login'))
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @endif
                            </div>

                            <div class="flex items-center justify-between text-sm text-white">
                                <label for="remember_me" class="inline-flex items-center">
                                    <input id="remember_me" type="checkbox" name="remember"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
                                    <span class="ms-2">Ingat saya</span>
                                </label>

                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="font-semibold hover:underline">Lupa kata sandi?</a>
                                @endif
                            </div>

                            <button type="submit" class="{{ $btnClass }}">Masuk</button>
                        </form>

                        {{-- Link ganti form (hanya mobile) --}}
                        <p class="mt-6 text-center text-sm text-blue-200 md:hidden">
                            Belum punya akun?
                            <a href="{{ route('register') }}" @click.prevent="go('register')" class="font-semibold text-white underline">Daftar</a>
                        </p>
                    </div>
                </div>
            </section>

            {{-- ============ FORM REGISTER (sisi kanan) ============ --}}
            <section :class="mode === 'register' ? 'block md:opacity-100' : 'hidden md:block md:invisible md:opacity-0'"
                     class="overflow-y-auto md:absolute md:inset-y-0 md:left-1/2 md:w-1/2 md:transition-all md:duration-700"
                     aria-label="Form registrasi">
                <div class="flex min-h-screen items-center justify-center px-6 py-10 md:min-h-full md:px-12">
                    <div class="w-full max-w-md">

                        <div class="mb-6 flex items-center justify-center gap-2 md:hidden">
                            {!! $logo('h-9 w-9') !!}
                            <span class="text-2xl font-extrabold tracking-wide text-white">LABORA</span>
                        </div>

                        <h1 class="text-center text-4xl font-extrabold text-white md:text-5xl">Create Account</h1>
                        <p class="mt-1 text-center text-sm text-white">Daftar sebagai mahasiswa untuk mengajukan peminjaman.</p>

                        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4">
                            @csrf

                            <div>
                                <label for="register_name" class="block text-xs font-semibold text-white">Nama lengkap</label>
                                <input id="register_name" type="text" name="name" value="{{ $old('name', 'register') }}"
                                       placeholder="Masukkan nama lengkap" required autocomplete="name"
                                       @if ($mode === 'register') autofocus @endif
                                       class="{{ $inputClass }}">
                                @if ($message = $error('name', 'register'))
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @endif
                            </div>

                            <div>
                                <label for="register_nim" class="block text-xs font-semibold text-white">NIM</label>
                                <input id="register_nim" type="text" name="nim" value="{{ $old('nim', 'register') }}"
                                       placeholder="Masukkan NIM" maxlength="10" required
                                       class="{{ $inputClass }}">
                                @if ($message = $error('nim', 'register'))
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @endif
                            </div>

                            <div>
                                <label for="register_email" class="block text-xs font-semibold text-white">Email</label>
                                <input id="register_email" type="email" name="email" value="{{ $old('email', 'register') }}"
                                       placeholder="nama@kampus.ac.id" required autocomplete="username"
                                       class="{{ $inputClass }}">
                                @if ($message = $error('email', 'register'))
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @endif
                            </div>

                            <div>
                                <label for="register_password" class="block text-xs font-semibold text-white">Password</label>
                                <input id="register_password" type="password" name="password"
                                       placeholder="Masukkan password" required autocomplete="new-password"
                                       class="{{ $inputClass }}">
                                @if ($message = $error('password', 'register'))
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @endif
                            </div>

                            <div>
                                <label for="register_password_confirmation" class="block text-xs font-semibold text-white">Konfirmasi password</label>
                                <input id="register_password_confirmation" type="password" name="password_confirmation"
                                       placeholder="Ulangi password" required autocomplete="new-password"
                                       class="{{ $inputClass }}">
                                @if ($message = $error('password_confirmation', 'register'))
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @endif
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="{{ $btnClass }}">Daftar</button>
                            </div>
                        </form>

                        <p class="mt-6 text-center text-sm text-blue-200 md:hidden">
                            Sudah punya akun?
                            <a href="{{ route('login') }}" @click.prevent="go('login')" class="font-semibold text-white underline">Masuk</a>
                        </p>
                    </div>
                </div>
            </section>

            {{-- ============ PANEL PUTIH YANG BERGESER (hanya desktop) ============ --}}
            <div :class="mode === 'login' ? 'translate-x-full rounded-l-[48px]' : 'translate-x-0 rounded-r-[48px]'"
                 class="absolute inset-y-0 left-0 z-10 hidden w-1/2 items-center justify-center bg-[#EBF3FF] px-8 shadow-xl transition-[transform,border-radius] duration-700 ease-in-out md:flex">

                {{-- Dua teks ditumpuk di tempat yang sama, lalu saling fade --}}
                <div class="grid w-full max-w-lg text-center">

                    {{-- Tampil saat mode REGISTER --}}
                    <div :class="mode === 'register' ? 'opacity-100' : 'invisible opacity-0'"
                         class="col-start-1 row-start-1 transition-all duration-500">
                        <h2 class="text-4xl font-extrabold leading-tight text-[#1F3F78] xl:text-6xl">Welcome to</h2>
                        <div class="mt-3 flex items-center justify-center gap-3">
                            {!! $logo('h-12 w-12 xl:h-14 xl:w-14') !!}
                            <span class="text-4xl font-extrabold tracking-wide text-[#1F3F78] xl:text-5xl">LABORA</span>
                        </div>
                        <p class="mt-6 text-lg text-gray-900 xl:text-xl">Peminjaman Ruang dan Alat Laboratorium</p>
                        <p class="mt-6 text-xs font-semibold text-blue-600">Sudah punya akun?</p>
                        <a href="{{ route('login') }}" @click.prevent="go('login')"
                           class="mt-3 inline-block w-full max-w-[260px] rounded-md border border-blue-600 px-4 py-3 text-sm font-semibold text-blue-600 transition hover:bg-blue-600 hover:text-white">
                            Masuk
                        </a>
                    </div>

                    {{-- Tampil saat mode LOGIN --}}
                    <div :class="mode === 'login' ? 'opacity-100' : 'invisible opacity-0'"
                         class="col-start-1 row-start-1 transition-all duration-500">
                        <h2 class="text-4xl font-extrabold leading-tight text-[#1F3F78] xl:text-6xl">Welcome Back to</h2>
                        <div class="mt-3 flex items-center justify-center gap-3">
                            {!! $logo('h-12 w-12 xl:h-14 xl:w-14') !!}
                            <span class="text-4xl font-extrabold tracking-wide text-[#1F3F78] xl:text-5xl">LABORA</span>
                        </div>
                        <p class="mt-6 text-lg text-gray-900 xl:text-xl">Peminjaman Ruang dan Alat Laboratorium</p>
                        <p class="mt-6 text-xs font-semibold text-blue-600">Belum punya akun?</p>
                        <a href="{{ route('register') }}" @click.prevent="go('register')"
                           class="mt-3 inline-block w-full max-w-[260px] rounded-md border border-blue-600 px-4 py-3 text-sm font-semibold text-blue-600 transition hover:bg-blue-600 hover:text-white">
                            Daftar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>