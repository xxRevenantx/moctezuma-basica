const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const code = fs.readFileSync(path.join(__dirname,'../public/js/ficha-editor-pro303.js'),'utf8');
let dataName, factory, alpineInit;
const events = [];
const source = {
    URL, Date, console, clearTimeout,
    document: {
        addEventListener(name, fn) { if(name === 'alpine:init') alpineInit = fn; },
        execCommand() { return true; },
    },
    window: {
        location: { href: 'http://moctezuma-basica.test/nivel/preescolar/fichas/58/editar', assign(path) { this.next = path; } },
        addEventListener(name, fn) { events.push(name); }, removeEventListener() {},
        open() { return { location: { replace(url) { this.url = url; } }, close() {} }; },
    },
    setTimeout() { return 1; },
};
vm.runInNewContext(code,source);
assert.equal(typeof alpineInit,'function','Register Alpine at init when Alpine not available in head');
source.window.Alpine = { data(name, value) { dataName = name; factory = value; } };
alpineInit();
assert.equal(dataName,'fichaEditorPro303');
assert.equal(typeof factory,'function');
async function create(serverResponse = {guardado:true,mensaje:null}) {
    const root={ dataset: {urlPdf:'http://moctezuma-basica.test/a.pdf',urlWord:'http://moctezuma-basica.test/a.docx',urlRegreso:'/fichas',soloLectura:'0'} };
    const refs={area:{value:'<p>Inicial</p>',hidden:false,style:{}}, respaldo:{hidden:true,style:{}},textoRespaldo:{innerHTML:'',focus(){}}};
    const wire={descripcion:'<p>Inicial</p>',campo:'lenguajes',called:[],async call(name,...args) {
        this.called.push([name,...args]);
        if (name === 'guardarEditor') return serverResponse;
        if (name === 'seleccionarCampo') {this.campo=args[0];this.descripcion='<p>Nuevo campo</p>';}
        return null;
    }};
    const instance = Object.assign(factory(),{$root:root,$refs:refs,$wire:wire,$nextTick:fn=>fn()});
    instance.iniciar();
    return {instance,root,refs,wire};
}
(async()=>{
  {
    const {instance,refs,wire}=await create();
    assert.equal(instance.respaldoActivo,true);
    assert.equal(refs.area.style.display,'none');
    assert.equal(refs.respaldo.style.display,'block');
    refs.textoRespaldo.innerHTML='<p><b>Hola</b></p>';
    instance.cambioLocal();
    assert.equal(await instance.guardarPendiente(),true);
    assert.equal(instance.estado,'guardado');
    assert.equal(wire.called[0][0],'guardarEditor');
    assert.equal(wire.called[0][1],'<p><b>Hola</b></p>');
    await instance.cambiarCampo('lenguajes');
    await instance.cambiarCampo('recomendaciones');
    assert.equal(wire.campo,'recomendaciones');
    assert.equal(refs.textoRespaldo.innerHTML,'<p>Nuevo campo</p>');
    instance.destroy();
  }
  {
    const {instance,refs}=await create({guardado:false,mensaje:'Periodo cerrado'});
    refs.textoRespaldo.innerHTML='<p>No válido</p>';
    instance.cambioLocal();
    assert.equal(await instance.guardarPendiente(),false);
    assert.equal(instance.estado,'error');
    assert.equal(instance.mensajeError,'Periodo cerrado');
    await instance.regresar();
    assert.equal(source.window.location.next,undefined);
    instance.destroy();
  }
  {
    const {instance,refs}=await create();
    refs.textoRespaldo.innerHTML='<p>Nuevo</p>';
    instance.cambioLocal();
    await instance.regresar();
    assert.equal(source.window.location.next,'/fichas');
    instance.destroy();
  }
  {
    let immediateName;
    const eager={window:{Alpine:{data(name){ immediateName=name; }}},document:{addEventListener(){throw new Error('should not need late listener')}}};
    vm.runInNewContext(code,eager);
    assert.equal(immediateName,'fichaEditorPro303','register eagerly if Livewire loaded before script');
  }
  console.log('PASS: registration early/late, rich backup, PHP save ack, server errors, field changes, safe return');
})().catch(e=>{console.error(e);process.exitCode=1});
