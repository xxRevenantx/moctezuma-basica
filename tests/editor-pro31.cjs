const fs=require('node:fs');
const vm=require('node:vm');
const assert=require('node:assert/strict');
const file=fs.readFileSync(__dirname+'/../resources/js/ficha-editor-pro31.js','utf8');
let controllerFactory;
const context={
    window:{Alpine:{data(name,factory){assert.equal(name,'fichaEditorPro31');controllerFactory=factory;}},addEventListener(){}},
    document:{addEventListener(){}},
    setTimeout: (fn)=>{ context.pending=fn;return 23; },
    clearTimeout() {},
    console,
    URL,
};
vm.runInNewContext(file,context);
assert.equal(typeof controllerFactory,'function');
const ctx=controllerFactory();
const root={dataset:{urlPdf:'/pdf',urlWord:'/word',urlRegreso:'/list'}};
ctx.$root=root;
ctx.$refs={editorRespaldo:{value:'<p>Hola</p>'}};
ctx.editorAlterno=true;
ctx.soloLectura=false;
const received=[];
ctx.$wire={call: async (name,html)=> {received.push({name,html});return {guardado:true};},campo:'lenguajes',descripcion:'<p>Otro</p>'};
(async()=>{
  ctx.cambioLocal();
  assert.equal(ctx.estado,'pendiente');
  assert.equal(ctx.version,1);
  assert.equal(await ctx.guardarPendiente(),true);
  assert.equal(ctx.estado,'guardado');
  assert.equal(ctx.sucio,false);
  assert.equal(received[0].name,'guardarEditor');
  assert.equal(received[0].html,'<p>Hola</p>');
  ctx.$refs.editorRespaldo.value='<p>Cambio</p>';
  ctx.cambioLocal();
  ctx.$wire.call=async()=>({guardado:false,mensaje:'Periodo cerrado'});
  assert.equal(await ctx.guardarPendiente(),false);
  assert.equal(ctx.estado,'error');
  assert.match(ctx.mensajeError,/Periodo cerrado/);
  assert.equal(ctx.sucio,true);
  ctx.$wire.call=async()=>({guardado:true});
  assert.equal(await ctx.guardarPendiente(),true);
  assert.equal(ctx.estado,'guardado');
  ctx.aplicarContenido('<p>Nueva sección</p>');
  assert.equal(ctx.$refs.editorRespaldo.value,'<p>Nueva sección</p>');
  assert.equal(ctx.sucio,false);
  console.log('OK autoguardado, error, reintento, carga de campo');
})().catch(err=>{console.error(err);process.exitCode=1});
