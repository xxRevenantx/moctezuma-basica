/** Regresión del error Alpine 'Invalid or unexpected token' al abrir el modal. */
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');

const template = fs.readFileSync(path.join(__dirname, '../../resources/views/livewire/accion/ficha.blade.php'), 'utf8');
assert.ok(!template.includes('@js('), 'No dejar @js() en expresiones de Alpine');

const expressions = Array.from(template.matchAll(/x-on:click(?:\.self)?="([^"]+)"/g), match => match[1]);
assert.ok(expressions.length >= 9, 'Se deben revisar los botones del módulo');
for (const expression of expressions) {
    assert.ok(!expression.includes('@js('), `Directiva Blade dentro de Alpine: ${expression}`);
    // Comprueba la sintaxis sin ejecutar funciones del navegador.
    assert.doesNotThrow(() => new Function('$el', '$dispatch', 'urlPrevia', 'vistaPrevia', expression));
}

function ejecutar(needle, data = {}) {
    const expression = expressions.find(expr => expr.includes(needle));
    assert.ok(expression, `Falta expresión ${needle}`);
    const events = [];
    const context = {
        $el: { dataset: data },
        $dispatch: (name, detail) => events.push({ name, detail }),
        urlPrevia: '',
        vistaPrevia: false,
    };
    vm.runInNewContext(expression, context);
    return { context, events };
}

const url = `http://moctezuma-basica.test/ficha?nombre=O'Connor&campo=lenguajes`;
const vista = ejecutar('urlPrevia = $el.dataset.pdfUrl', { pdfUrl: url });
assert.equal(vista.context.urlPrevia, url);
assert.equal(vista.context.vistaPrevia, true);

const campo = ejecutar("$dispatch('ficha-campo'", { campo: 'etica_naturaleza_sociedades' });
assert.equal(campo.events[0].name, 'ficha-campo');
assert.equal(campo.events[0].detail.clave, 'etica_naturaleza_sociedades');

const pdf = ejecutar("$dispatch('ficha-vista-pdf'", { pdfUrl: url });
assert.equal(pdf.events[0].name, 'ficha-vista-pdf');
assert.equal(pdf.events[0].detail.url, url);

assert.equal((template.match(/data-pdf-url="\{\{/g) || []).length, 4);
assert.ok(template.includes('data-campo="{{ $clave }}"'));
console.log('OK: expresiones Alpine, URL PDF, cambio de campo y datos sin @js().');
