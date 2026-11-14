const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const jsPath = path.join(__dirname, '../../resources/js/ficha-editor-pro.js');
const codigo = fs.readFileSync(jsPath, 'utf8');
let fabrica;
const temporizadores = [];
const location = { href: 'http://moctezuma-basica.test/nivel/preescolar/fichas/9/editar', assign(value) { this.destino = value; } };
const context = {
    URL, Date, console,
    document: { addEventListener(event, callback) { if (event === 'alpine:init') callback(); }, execCommand() { return true; } },
    Alpine: { data(name, callback) { assert.equal(name, 'fichaEditorPro'); fabrica = callback; } },
    window: { location, addEventListener() {}, removeEventListener() {}, open() { return { location: { replace(url) { location.word = url; } }, close() {} }; } },
    setTimeout(callback) { const id = temporizadores.length; temporizadores.push(callback); return id; },
    clearTimeout(id) { temporizadores[id] = () => {}; },
};
vm.runInNewContext(codigo, context, { filename: jsPath });
assert.equal(typeof fabrica, 'function', 'El controlador Alpine está registrado');

function crear(guardar = async function() { this.estadoGuardado = 'guardado'; }) {
    const area = { value: '<p>Inicial</p>', hidden: false };
    const respaldo = { hidden: true };
    const textoRespaldo = { innerHTML: '', focus() {} };
    const root = { dataset: { urlPdf: '/fichas/preescolar/alumno/9/pdf', urlWord: '/fichas/preescolar/alumno/9/word', urlRegreso: '/nivel/preescolar/fichas', soloLectura: '0' } };
    const wire = {
        descripcion: '<p>Inicial</p>', estadoGuardado: 'guardado', campo: 'lenguajes',
        async set(prop, value) { this[prop] = value; },
        async call(accion, ...args) {
            if (accion === 'guardarBorrador') return guardar.call(this);
            if (accion === 'seleccionarCampo') { this.campo = args[0]; this.descripcion = '<p>Otro campo</p>'; }
            if (accion === 'aceptarSugerenciaIA') { this.descripcion = '<p>Propuesta IA</p>'; }
        },
    };
    const ctrl = Object.assign(fabrica(), {
        $root: root, $refs: { area, respaldo, textoRespaldo }, $wire: wire,
        $nextTick(fn) { fn(); },
    });
    ctrl.iniciar();
    return { ctrl, wire, area, respaldo, textoRespaldo };
}

(async () => {
    {
        const { ctrl, area, respaldo, textoRespaldo } = crear();
        assert.equal(ctrl.respaldoActivo, true, 'Tiene respaldo enriquecido si TinyMCE no carga');
        assert.equal(area.hidden, true);
        assert.equal(respaldo.hidden, false);
        textoRespaldo.innerHTML = '<p><strong>Nuevo</strong></p>';
        ctrl.cambioLocal();
        assert.equal(ctrl.estado, 'pendiente');
        assert.equal(await ctrl.guardarPendiente(), true);
        assert.equal(ctrl.estado, 'guardado');
        assert.equal(ctrl.$wire.descripcion, '<p><strong>Nuevo</strong></p>');
        await ctrl.cambiarCampo('recomendaciones');
        assert.equal(ctrl.$wire.campo, 'recomendaciones');
        assert.equal(textoRespaldo.innerHTML, '<p>Otro campo</p>');
        ctrl.destroy();
    }
    {
        const { ctrl, wire, textoRespaldo } = crear(async function() {
            // Falla una validación PHP antes de que el servidor cambie el estado.
        });
        textoRespaldo.innerHTML = '<p>No válido</p>';
        ctrl.cambioLocal();
        assert.equal(await ctrl.guardarPendiente(), false, 'No confundir fallos con guardados');
        assert.equal(wire.estadoGuardado, 'pendiente');
        await ctrl.regresar();
        assert.equal(location.destino, undefined, 'Nunca salir con cambios sin guardar');
        ctrl.destroy();
    }
    {
        let resolver;
        const { ctrl, textoRespaldo } = crear(async function() {
            await new Promise(resolve => { resolver = resolve; });
            this.estadoGuardado = 'guardado';
        });
        textoRespaldo.innerHTML = '<p>Primero</p>';
        ctrl.cambioLocal();
        const primera = ctrl.guardarPendiente();
        for (let i = 0; i < 10 && typeof resolver !== 'function'; i++) await new Promise(resolve => setImmediate(resolve));
        assert.equal(typeof resolver, 'function', 'Se inició el guardado en vuelo');
        textoRespaldo.innerHTML = '<p>Segundo</p>';
        ctrl.cambioLocal();
        resolver();
        assert.equal(await primera, false, 'Una nueva edición no se marca como guardada');
        assert.equal(ctrl.sucio, true, 'Queda pendiente la versión posterior');
        ctrl.destroy();
    }
    {
        const { ctrl } = crear();
        await ctrl.abrirPDF();
        assert.equal(ctrl.vistaPrevia, true);
        assert.equal(new URL(ctrl.urlPdf).searchParams.has('_vista'), true);
        await ctrl.abrirWord();
        assert.equal(location.word, '/fichas/preescolar/alumno/9/word');
        await ctrl.regresar();
        assert.equal(location.destino, '/nivel/preescolar/fichas');
        ctrl.destroy();
    }
    console.log('OK: respaldo enriquecido, guardado, cambio de campo, errores, escrituras concurrentes, vista PDF y regreso.');
})().catch(error => { console.error(error); process.exitCode = 1; });
