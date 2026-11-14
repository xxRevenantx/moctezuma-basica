@once
    @php
        $tinyKey = trim((string) config('services.tinymce.api_key', ''));
        $tinyKeyValida = $tinyKey !== '' && !in_array(strtolower($tinyKey), ['no-api-key', 's', 'null'], true);
    @endphp
    {{-- Tiny Cloud deshabilita la edición con claves vacías o de prueba.
         Si falta la clave, las fichas usan un textarea grande y editable. --}}
    @if ($tinyKeyValida)
        <script src="https://cdn.tiny.cloud/1/{{ rawurlencode($tinyKey) }}/tinymce/8/tinymce.min.js"
            referrerpolicy="origin" crossorigin="anonymous" defer></script>
    @endif
@endonce
