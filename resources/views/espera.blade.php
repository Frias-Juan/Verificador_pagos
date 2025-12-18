<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Pendiente</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-white p-10 rounded-xl shadow-lg text-center border border-gray-200">
        
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-6">
            <svg class="h-10 w-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-4">Registro en Revisión</h1>
        
        <p class="text-gray-600 mb-6">
            Hola, {{ auth()->user()->name }}. Tu cuenta ha sido creada exitosamente, pero debe ser aprobada por un administrador.
        </p>

        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-8 text-left">
            <p class="text-sm text-blue-700">
                Estamos validando tu información.
            </p>
        </div>

        <form action="{{ route('filament.admin.auth.logout') }}" method="POST">
            @csrf
            <button type="submit" class="w-full bg-gray-800 text-white py-2 px-4 rounded-lg hover:bg-gray-900 transition duration-200 text-sm font-medium">
                Cerrar sesión y volver al inicio
            </button>
        </form>
    </div>
</body>
</html>