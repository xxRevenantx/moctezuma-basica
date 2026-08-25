@if (!empty($membrete['ruta_absoluta']) && is_file($membrete['ruta_absoluta']))
    <img
        src="{{ $membrete['ruta_absoluta'] }}"
        style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1000;"
    >
@endif
