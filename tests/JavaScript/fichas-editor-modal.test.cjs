/** Pruebas del controlador inline: node tests/JavaScript/fichas-editor-modal.test.cjs */
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/livewire/accion/ficha.blade.php'), 'utf8');
const codigo = view.match(/@script\s*<script>([\s\S]*?)<\/script>\s*@endscript/)[1];

function preparar({ tiny = false } = {}) {
    const registros = [];
    const listeners = new Map();
    const state = { descripcion: '', estadoGuardado: 'guardado', campo: 'lenguajes', modalAbierto: false };
    const textarea = { value: '', readOnly: false, oninput: null, onblur: null };
    const status = { textContent: '', hidden: true };
    const err = { textContent: '', hidden: true };
    const root = { querySelector: selector => ({
        '[data-ficha-editor]': textarea,
        '[data-ficha-editor-mensaje]': status,
        '[data-ficha-editor-error]': err,
    })[selector] };
    let activeTiny = null;
    const tinymce = tiny ? {
        get: () => activeTiny,
        init: async options => {
            const callbackMap = new Map();
            const inst = {
                getContent: () => textarea.value,
                setContent: value => { textarea.value = value; },
                remove: () => { activeTiny = null; },
                mode: { get: () => 'design' },
                on: (eventNames, handler) => eventNames.split(' ').forEach(name => callbackMap.set(name, handler)),
                fire: name => callbackMap.get(name)?.(),
            };
            options.setup(inst);
            activeTiny = inst;
            return [inst];
        },
    } : null;
    const wire = {
        $el: root,
        get: key => state[key],
        set: (key, value) => { state[key] = value; },
        on: (name, fn) => { listeners.set(name, fn); },
        call: async (name, ...args) => {
            if (name === 'guardarBorrador') {
                await new Promise(done => setTimeout(done, 15));
                registros.push(['guardar', state.descripcion]);
                state.estadoGuardado = 'guardado';
            } else {
                registros.push([name, ...args]);
                if (name === 'seleccionarCampo') {
                    state.campo = args[0];
                    state.descripcion = '<p>Nuevo campo</p>';
                    state.estadoGuardado = 'guardado';
                    listeners.get('actualizar-editor-ficha')({ contenido: state.descripcion });
                }
                if (name === 'cerrarModal') state.modalAbierto = false;
            }
        },
    };
    const sandbox = {
        $wire: wire,
        window: {
            ...(tinymce ? { tinymce } : {}),
            location: { href: 'http://moctezuma-basica.test/nivel/preescolar/fichas' },
            dispatchEvent: e => registros.push(['evento', e.type]),
        },
        document: { documentElement: { classList: { contains: () => false } }, addEventListener: () => {} },
        CustomEvent: class CustomEvent { constructor(type, opts) { this.type = type; this.detail = opts.detail; } },
        URL, Date, Promise, console, setTimeout, clearTimeout,
        requestAnimationFrame: fn => setTimeout(fn, 0),
    };
    vm.runInNewContext(codigo, sandbox);
    return { textarea, state, status, err, registros, listeners, tiny: () => activeTiny };
}

const esperar = ms => new Promise(done => setTimeout(done, ms));
const emitir = async (ctx, name, data={}) => { ctx.listeners.get(name)(data); await esperar(10); };

(async () => {
    const c = preparar();
    await emitir(c, 'abrir-modal-ficha', { contenido: '<p>Inicial</p>' });
    assert.equal(c.textarea.value, '<p>Inicial</p>');
    assert.equal(c.status.hidden, false, 'Debe mostrarse respaldo sin TinyMCE');
    c.textarea.value = 'Primer cambio';
    c.textarea.oninput();
    await esperar(5);
    // Primer guardado en curso por blur, segundo cambio mientras la petición está viva.
    c.textarea.onblur();
    await esperar(2);
    c.textarea.value = 'Cambio nuevo';
    c.textarea.oninput();
    await esperar(60);
    assert.equal(c.registros.filter(r => r[0] === 'guardar').at(-1)[1], 'Cambio nuevo');
    await emitir(c, 'ficha-campo', { clave: 'saberes' });
    await esperar(20);
    assert.equal(c.state.campo, 'saberes');
    assert.equal(c.textarea.value, '<p>Nuevo campo</p>');
    await emitir(c, 'ficha-cerrar');
    assert.equal(c.state.modalAbierto, false);

    const t = preparar({ tiny: true });
    await emitir(t, 'abrir-modal-ficha', { contenido: 'Con formato' });
    assert.ok(t.tiny(), 'Debe montar TinyMCE cuando está disponible');
    t.textarea.value = '<p>Texto con formato</p>';
    t.tiny().fire('input');
    await emitir(t, 'ficha-guardar-y-cerrar');
    await esperar(45);
    assert.ok(t.registros.some(r => r[0] === 'guardar' && r[1] === '<p>Texto con formato</p>'));
    assert.ok(t.registros.some(r => r[0] === 'cerrarModal'));
    console.log('OK: apertura, respaldo, editor rico, cola de autoguardado, cambio de campo y cierre.');
})().catch(e => { console.error(e); process.exitCode = 1; });
