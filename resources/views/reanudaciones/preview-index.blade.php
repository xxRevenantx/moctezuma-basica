<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vista previa de reanudaciones</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 p-6 text-slate-900 dark:bg-neutral-950 dark:text-white">
    <main class="mx-auto max-w-3xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl dark:border-neutral-800 dark:bg-neutral-900">
        <div class="h-2 bg-gradient-to-r from-[#006492] via-blue-600 to-[#88AC2E]"></div>
        <div class="p-6 sm:p-8">
            <p class="text-xs font-black uppercase tracking-[.2em] text-[#006492] dark:text-sky-300">Vista previa por nivel</p>
            <h1 class="mt-1 text-2xl font-black">Reanudaciones de labores</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                La selección contiene varios niveles. Abre cada PDF para revisar el formato correspondiente antes de generar el historial.
            </p>

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                @foreach ($niveles as $nivel)
                    <a href="{{ $nivel['url'] }}" target="_blank"
                        class="group rounded-2xl border border-slate-200 p-4 transition hover:-translate-y-0.5 hover:border-blue-300 hover:bg-blue-50 hover:shadow-md dark:border-neutral-800 dark:hover:border-blue-800 dark:hover:bg-blue-950/30">
                        <p class="font-black group-hover:text-blue-700 dark:group-hover:text-blue-300">{{ $nivel['nombre'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $nivel['cantidad'] }} oficio(s)</p>
                        <p class="mt-3 text-xs font-black text-blue-700 dark:text-blue-300">Abrir PDF →</p>
                    </a>
                @endforeach
            </div>
        </div>
    </main>
</body>
</html>
