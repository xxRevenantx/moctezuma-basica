@props([
    'model',
    'id',
    'value' => null,
])

{{--
    Editor ligero exclusivo de Editar matrícula.
    No monta TinyMCE, iframes ni observadores globales durante la carga.
    Livewire mantiene el valor en la propiedad original y conserva el HTML simple
    (negritas, cursivas, listas) que admite ObservacionInscripcionService.
--}}
<div wire:key="observaciones-estables-{{ $id }}"
    x-data="{
        campo: @js($model),
        editorId: @js($id),
        formulario: null,
        onSubmit: null,
        longitud: 0,
        init() {
            this.formulario = this.$el.closest('form');
            this.onSubmit = () => this.sincronizar();
            this.formulario?.addEventListener('submit', this.onSubmit, true);
            document.execCommand('defaultParagraphSeparator', false, 'p');
            this.actualizarLongitud();
        },
        sincronizar() {
            if (!this.$refs.editor) return;
            this.actualizarLongitud();
            this.$wire.set(this.campo, this.$refs.editor.innerHTML, false);
        },
        actualizarLongitud() {
            this.longitud = (this.$refs.editor?.innerText ?? '').trim().length;
        },
        aplicarFormato(comando, valor = null) {
            this.$refs.editor?.focus();
            document.execCommand(comando, false, valor);
            this.sincronizar();
        },
        plantilla(texto) {
            if (!texto) return;
            this.aplicarFormato('insertHTML', '<p><strong>' + texto + ':</strong> </p>');
        },
        cargarContenido(html) {
            if (!this.$refs.editor) return;
            this.$refs.editor.innerHTML = html || '';
            this.sincronizar();
        },
        destroy() {
            this.formulario?.removeEventListener('submit', this.onSubmit, true);
        }
    }"
    x-on:reset-observaciones-editor.window="if ($event.detail.editor === editorId) cargarContenido($event.detail.contenido)"
    class="space-y-2">

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100 dark:border-neutral-700 dark:bg-neutral-900 dark:focus-within:border-sky-700 dark:focus-within:ring-sky-950/40">
        <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 px-2 py-2 dark:border-neutral-700 dark:bg-neutral-800/70"
            role="toolbar" aria-label="Formato de observaciones">
            <button type="button" x-on:mousedown.prevent="aplicarFormato('bold')"
                title="Negrita" aria-label="Negrita"
                class="rounded-lg px-3 py-1.5 text-sm font-bold text-slate-700 hover:bg-white dark:text-slate-200 dark:hover:bg-neutral-700">B</button>
            <button type="button" x-on:mousedown.prevent="aplicarFormato('italic')"
                title="Cursiva" aria-label="Cursiva"
                class="rounded-lg px-3 py-1.5 text-sm italic text-slate-700 hover:bg-white dark:text-slate-200 dark:hover:bg-neutral-700">I</button>
            <button type="button" x-on:mousedown.prevent="aplicarFormato('underline')"
                title="Subrayar" aria-label="Subrayar"
                class="rounded-lg px-3 py-1.5 text-sm underline text-slate-700 hover:bg-white dark:text-slate-200 dark:hover:bg-neutral-700">U</button>
            <span class="mx-1 h-5 w-px bg-slate-200 dark:bg-neutral-600" aria-hidden="true"></span>
            <button type="button" x-on:mousedown.prevent="aplicarFormato('insertUnorderedList')"
                class="rounded-lg px-3 py-1.5 text-sm text-slate-700 hover:bg-white dark:text-slate-200 dark:hover:bg-neutral-700">• Lista</button>
            <button type="button" x-on:mousedown.prevent="aplicarFormato('insertOrderedList')"
                class="rounded-lg px-3 py-1.5 text-sm text-slate-700 hover:bg-white dark:text-slate-200 dark:hover:bg-neutral-700">1. Lista</button>
            <span class="mx-1 h-5 w-px bg-slate-200 dark:bg-neutral-600" aria-hidden="true"></span>
            <button type="button" x-on:mousedown.prevent="aplicarFormato('justifyLeft')"
                aria-label="Alinear a la izquierda" title="Alinear a la izquierda"
                class="rounded-lg px-2.5 py-1.5 text-sm text-slate-700 hover:bg-white dark:text-slate-200 dark:hover:bg-neutral-700">⇤</button>
            <button type="button" x-on:mousedown.prevent="aplicarFormato('justifyCenter')"
                aria-label="Centrar" title="Centrar"
                class="rounded-lg px-2.5 py-1.5 text-sm text-slate-700 hover:bg-white dark:text-slate-200 dark:hover:bg-neutral-700">≡</button>
            <button type="button" x-on:mousedown.prevent="aplicarFormato('justifyRight')"
                aria-label="Alinear a la derecha" title="Alinear a la derecha"
                class="rounded-lg px-2.5 py-1.5 text-sm text-slate-700 hover:bg-white dark:text-slate-200 dark:hover:bg-neutral-700">⇥</button>
            <button type="button" x-on:mousedown.prevent="aplicarFormato('removeFormat')"
                class="rounded-lg px-3 py-1.5 text-sm text-slate-700 hover:bg-white dark:text-slate-200 dark:hover:bg-neutral-700">Borrar formato</button>
            <select aria-label="Plantillas de observación"
                x-on:change="plantilla($event.target.value); $event.target.value = ''"
                class="ml-auto max-w-[200px] rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                <option value="">Insertar plantilla…</option>
                <option value="Documentación pendiente">Documentación pendiente</option>
                <option value="Corrección de datos">Corrección de datos</option>
                <option value="Cambio de grupo">Cambio de grupo</option>
                <option value="Seguimiento académico">Seguimiento académico</option>
            </select>
        </div>

        <div wire:ignore>
            <div id="{{ $id }}" x-ref="editor" contenteditable="true" role="textbox" aria-multiline="true"
                aria-label="Observaciones y seguimiento" spellcheck="true"
                x-on:input.debounce.250ms="sincronizar()" x-on:blur="sincronizar()"
                class="min-h-[170px] max-h-[520px] overflow-y-auto px-4 py-3 text-sm leading-7 text-slate-800 outline-none dark:text-slate-100 [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:list-disc [&_ul]:pl-6 [&_p]:mb-2"
            >{!! app(\App\Services\ObservacionInscripcionService::class)->sanitizar($value) ?? '' !!}</div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
        <span class="text-slate-500 dark:text-slate-400">Las notas se conservan por ciclo escolar. Los cambios se guardan al pulsar «Guardar cambios».</span>
        <span :class="longitud > 5000 ? 'font-bold text-rose-600' : 'font-semibold text-slate-500 dark:text-slate-400'">
            <span x-text="longitud.toLocaleString('es-MX')"></span>/5,000 caracteres
        </span>
    </div>
</div>
