<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem Akademik</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 flex items-center justify-center min-h-screen text-gray-800 font-sans">

    <div class="w-full max-w-md bg-white shadow-xl rounded-xl border border-gray-200" x-data="{ isLoading: false }">
        <div class="p-8">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Masuk ke Portal</h2>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg mb-6" role="alert">
                <span class="block sm:inline">{{ $errors->first() }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" @submit="isLoading = true">
                @csrf

                <div class="mb-5">
                    <label class="block text-sm font-semibold mb-2 text-gray-700" for="login_id">
                        Username / Email / NIP
                    </label>
                    <input type="text" name="login_id" id="login_id" class="appearance-none border border-gray-300 rounded-lg w-full py-2.5 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" required autofocus />
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-2 text-gray-700" for="password">
                        Password
                    </label>
                    <input type="password" name="password" id="password" class="appearance-none border border-gray-300 rounded-lg w-full py-2.5 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" required />
                </div>

                <div class="flex items-center justify-between mb-8">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="remember" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 transition duration-150 ease-in-out" />
                        <span class="ml-2 text-sm text-gray-600 font-medium">Ingat Saya</span>
                    </label>
                </div>

                <div class="flex flex-row gap-3 mt-2">
                    <button type="reset" class="w-1/3 bg-transparent hover:bg-red-50 text-red-600 font-semibold py-2.5 px-4 border border-red-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors duration-200">
                        Reset
                    </button>

                    <button type="submit" class="w-2/3 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 flex items-center justify-center" x-bind:disabled="isLoading" x-bind:class="{ 'opacity-75 cursor-not-allowed': isLoading }">
                        <svg x-show="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display: none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span x-show="!isLoading">Masuk</span>
                        <span x-show="isLoading" style="display: none;">Memproses...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>