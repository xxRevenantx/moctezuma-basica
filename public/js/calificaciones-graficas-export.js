/*
 * Exportación PNG de las gráficas académicas.
 * No depende de ApexCharts ni de capturas del DOM: dibuja las etiquetas completas
 * y conserva todos los registros que la gráfica muestra para el grupo.
 */
(function () {
    'use strict';

    const ANCHO = 1480;
    const ESCALA = 2;
    const MARGEN = 64;
    const FUENTE = 'Inter, "Segoe UI", Arial, sans-serif';
    const TIPOS = ['alumnos', 'materias', 'global'];
    const ETIQUETAS = {
        alumnos: ['Promedio por alumno', 'Promedio individual de cada estudiante.', 'Estudiante'],
        materias: ['Promedio por materia', 'Comparativo del rendimiento por asignatura.', 'Asignatura'],
        global: ['Rendimiento global', 'Promedio general del grupo seleccionado.', ''],
    };

    function texto(valor) {
        return String(valor ?? '').replace(/\s+/g, ' ').trim();
    }

    function numero(valor) {
        const n = Number(valor);
        return Number.isFinite(n) ? Math.min(10, Math.max(0, n)) : 0;
    }

    function decimal(valor) {
        return numero(valor).toFixed(1);
    }

    function rectRedondeado(ctx, x, y, ancho, alto, radio, relleno, borde) {
        ctx.beginPath();
        ctx.moveTo(x + radio, y);
        ctx.lineTo(x + ancho - radio, y);
        ctx.quadraticCurveTo(x + ancho, y, x + ancho, y + radio);
        ctx.lineTo(x + ancho, y + alto - radio);
        ctx.quadraticCurveTo(x + ancho, y + alto, x + ancho - radio, y + alto);
        ctx.lineTo(x + radio, y + alto);
        ctx.quadraticCurveTo(x, y + alto, x, y + alto - radio);
        ctx.lineTo(x, y + radio);
        ctx.quadraticCurveTo(x, y, x + radio, y);
        ctx.closePath();
        if (relleno) {
            ctx.fillStyle = relleno;
            ctx.fill();
        }
        if (borde) {
            ctx.strokeStyle = borde;
            ctx.lineWidth = 1.5;
            ctx.stroke();
        }
    }

    function escribir(ctx, contenido, x, y, tamanio, color, peso = '400') {
        ctx.font = `${peso} ${tamanio}px ${FUENTE}`;
        ctx.fillStyle = color;
        ctx.fillText(contenido, x, y);
    }

    function lineas(ctx, contenido, anchoMaximo) {
        const palabras = texto(contenido).split(' ');
        const resultado = [];
        let linea = '';
        for (const palabra of palabras) {
            const prueba = linea ? `${linea} ${palabra}` : palabra;
            if (linea && ctx.measureText(prueba).width > anchoMaximo) {
                resultado.push(linea);
                linea = palabra;
            } else {
                linea = prueba;
            }
        }
        if (linea) resultado.push(linea);
        // En caso de una palabra extraordinariamente larga (sin espacios),
        // fragmentarla para evitar que quede fuera de la imagen.
        const final = [];
        for (const item of resultado) {
            if (ctx.measureText(item).width <= anchoMaximo) {
                final.push(item);
                continue;
            }
            let trozo = '';
            for (const letra of item) {
                if (trozo && ctx.measureText(trozo + letra).width > anchoMaximo) {
                    final.push(trozo);
                    trozo = letra;
                } else {
                    trozo += letra;
                }
            }
            if (trozo) final.push(trozo);
        }
        return final.length ? final : ['Sin nombre'];
    }

    function prepararBarras(ctx, datos, tipo) {
        const bloque = datos?.[tipo] || {};
        const completas = Array.isArray(bloque.labels_completas) ? bloque.labels_completas : bloque.labels;
        const nombres = Array.isArray(completas) ? completas : [];
        const valores = Array.isArray(bloque.series) ? bloque.series : [];
        ctx.font = `500 18px ${FUENTE}`;
        const filas = valores.map((valor, i) => {
            const etiqueta = lineas(ctx, nombres[i] || `Registro ${i + 1}`, 485);
            return {
                etiqueta,
                valor: numero(valor),
                alto: Math.max(55, 18 + etiqueta.length * 24),
            };
        });
        return {
            tipo,
            filas,
            alto: 186 + filas.reduce((suma, fila) => suma + fila.alto, 0) + 46,
        };
    }

    function encabezado(ctx, tipo, y, ancho, alto) {
        rectRedondeado(ctx, MARGEN, y, ancho, alto, 26, '#ffffff', '#e2e8f0');
        rectRedondeado(ctx, MARGEN + 28, y + 29, 7, 52, 3, '#0284c7');
        escribir(ctx, ETIQUETAS[tipo][0], MARGEN + 55, y + 58, 27, '#0f172a', '800');
        escribir(ctx, ETIQUETAS[tipo][1], MARGEN + 55, y + 90, 17, '#64748b');
    }

    function dibujarBarras(ctx, datosSeccion, y) {
        const ancho = ANCHO - MARGEN * 2;
        encabezado(ctx, datosSeccion.tipo, y, ancho, datosSeccion.alto);
        const inicioX = 655;
        const finX = 1257;
        const graficaAncho = finX - inicioX;
        const inicioY = y + 158;
        escribir(ctx, ETIQUETAS[datosSeccion.tipo][2], MARGEN + 35, inicioY - 15, 15, '#64748b', '700');
        escribir(ctx, 'PROMEDIO', inicioX, inicioY - 15, 14, '#64748b', '700');

        let cursor = inicioY;
        for (let marca = 0; marca <= 10; marca += 2) {
            const x = inicioX + graficaAncho * marca / 10;
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(x, inicioY - 4);
            ctx.lineTo(x, inicioY + datosSeccion.filas.reduce((total, fila) => total + fila.alto, 0) - 6);
            ctx.stroke();
            escribir(ctx, String(marca), x - 7, inicioY - 33, 15, '#64748b', '600');
        }

        datosSeccion.filas.forEach((fila, indice) => {
            if (indice % 2 === 1) {
                rectRedondeado(ctx, MARGEN + 22, cursor + 2, ancho - 44, fila.alto - 4, 10, '#f8fafc');
            }
            fila.etiqueta.forEach((frase, indiceLinea) => {
                escribir(ctx, frase, MARGEN + 35, cursor + 25 + (indiceLinea * 23), 18, '#334155', '500');
            });
            rectRedondeado(ctx, inicioX, cursor + (fila.alto - 25) / 2, graficaAncho, 25, 9, '#e0f2fe');
            if (fila.valor > 0) {
                rectRedondeado(ctx, inicioX, cursor + (fila.alto - 25) / 2, graficaAncho * fila.valor / 10, 25, 9, '#139cf0');
            }
            escribir(ctx, decimal(fila.valor), finX + 18, cursor + fila.alto / 2 + 8, 20, '#0369a1', '800');
            cursor += fila.alto;
        });
    }

    function dibujarGlobal(ctx, datos, y) {
        const alto = 475;
        const ancho = ANCHO - MARGEN * 2;
        encabezado(ctx, 'global', y, ancho, alto);
        const global = datos?.global || {};
        const promedio = numero(global.promedio);
        const porcentaje = Math.min(100, Math.max(0, Number(global.porcentaje) || 0));
        const centroX = 404;
        const centroY = y + 281;
        const radio = 126;
        ctx.lineWidth = 40;
        ctx.strokeStyle = '#e2e8f0';
        ctx.beginPath();
        ctx.arc(centroX, centroY, radio, 0, Math.PI * 2);
        ctx.stroke();
        if (porcentaje > 0) {
            ctx.strokeStyle = '#139cf0';
            ctx.lineCap = 'round';
            ctx.beginPath();
            ctx.arc(centroX, centroY, radio, -Math.PI / 2, -Math.PI / 2 + porcentaje / 100 * Math.PI * 2);
            ctx.stroke();
            ctx.lineCap = 'butt';
        }
        ctx.textAlign = 'center';
        escribir(ctx, 'Promedio global', centroX, centroY - 10, 20, '#0284c7', '700');
        escribir(ctx, promedio.toFixed(1), centroX, centroY + 48, 55, '#0f172a', '900');
        ctx.textAlign = 'left';
        const tarjetas = [
            ['APROBACIÓN', `${Number(global.porcentaje_aprobacion || 0)}%`, '#059669'],
            ['ALUMNOS APROBADOS', String(Number(global.aprobadas || 0)), '#0284c7'],
            ['ALUMNOS REPROBADOS', String(Number(global.reprobadas || 0)), '#e11d48'],
        ];
        tarjetas.forEach(([etiqueta, valor, color], indice) => {
            const top = y + 129 + indice * 98;
            rectRedondeado(ctx, 744, top, 622, 84, 16, '#f8fafc', '#e2e8f0');
            escribir(ctx, etiqueta, 771, top + 30, 16, '#64748b', '700');
            escribir(ctx, valor, 771, top + 65, 29, color, '900');
        });
        return alto;
    }

    function obtenerCanvas(datos, tipo) {
        if (tipo !== 'todas' && !TIPOS.includes(tipo)) throw new Error('Tipo de gráfica no reconocido.');
        if (!datos || Number(datos?.global?.total_numericas || 0) <= 0) {
            throw new Error('No hay calificaciones numéricas para copiar.');
        }
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('El navegador no permite crear imágenes PNG.');

        const tipos = tipo === 'todas' ? TIPOS : [tipo];
        // Se permiten las tres juntas además de las individuales.
        const secciones = tipos.map(item => item === 'global'
            ? { tipo: item, alto: 475 }
            : prepararBarras(ctx, datos, item));
        const separacion = 30;
        const alto = 187 + secciones.reduce((total, item) => total + item.alto, 0) +
            Math.max(0, secciones.length - 1) * separacion + 62;
        if (alto * ESCALA > 32000 || ANCHO * ESCALA * alto * ESCALA > 120000000) {
            throw new Error('Hay demasiadas filas para una sola imagen. Filtra el grupo y vuelve a intentarlo.');
        }
        canvas.width = ANCHO * ESCALA;
        canvas.height = Math.ceil(alto * ESCALA);
        ctx.scale(ESCALA, ESCALA);
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(0, 0, ANCHO, alto);
        rectRedondeado(ctx, MARGEN, 38, 9, 97, 4, '#0284c7');
        escribir(ctx, 'CENTRO UNIVERSITARIO MOCTEZUMA', MARGEN + 29, 75, 20, '#0369a1', '800');
        escribir(ctx, tipo === 'todas' ? 'Gráficas de calificaciones' : ETIQUETAS[tipo][0], MARGEN + 29, 116, 35, '#0f172a', '900');
        escribir(ctx, 'Reporte gráfico • Escala de calificaciones de 0 a 10', MARGEN + 29, 149, 17, '#64748b');
        let y = 187;
        secciones.forEach((seccion, indice) => {
            if (seccion.tipo === 'global') dibujarGlobal(ctx, datos, y);
            else dibujarBarras(ctx, seccion, y);
            y += seccion.alto + (indice < secciones.length - 1 ? separacion : 0);
        });
        escribir(ctx, 'Fuente: calificaciones del grupo y periodo seleccionados en Moctezuma Básica.', MARGEN + 5, alto - 28, 16, '#64748b');
        return canvas;
    }

    function blobPng(canvas) {
        return new Promise((resolver, rechazar) => {
            canvas.toBlob(blob => blob ? resolver(blob) : rechazar(new Error('No se pudo crear la imagen PNG.')), 'image/png');
        });
    }

    function nombreArchivo(tipo) {
        return `moctezuma-graficas-calificaciones-${tipo}.png`;
    }

    async function descargarCanvas(canvas, tipo) {
        const blob = await blobPng(canvas);
        const url = URL.createObjectURL(blob);
        const enlace = document.createElement('a');
        enlace.href = url;
        enlace.download = nombreArchivo(tipo);
        document.body.appendChild(enlace);
        enlace.click();
        enlace.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1500);
    }

    async function copiar(datos, tipo) {
        const canvas = obtenerCanvas(datos, tipo);
        // Chromium restringe la escritura de imágenes al portapapeles a orígenes
        // seguros (HTTPS o localhost). Los dominios .test de Laragon suelen ser HTTP.
        if (!window.isSecureContext || !navigator.clipboard?.write || typeof window.ClipboardItem === 'undefined') {
            await descargarCanvas(canvas, tipo);
            return {
                exito: true,
                mensaje: 'La copia de imágenes requiere HTTPS o localhost. Se descargó el PNG completo para insertarlo en Word o PowerPoint.',
                alternativa: true,
            };
        }
        try {
            // Entregar la promesa al ClipboardItem inmediatamente mantiene el
            // gesto de usuario mientras el navegador termina de codificar el PNG.
            const item = new window.ClipboardItem({ 'image/png': blobPng(canvas) });
            await navigator.clipboard.write([item]);
            return { exito: true, mensaje: 'Imagen completa copiada. Puedes pegarla con Ctrl + V.', alternativa: false };
        } catch (error) {
            await descargarCanvas(canvas, tipo);
            return {
                exito: true,
                mensaje: 'El navegador bloqueó el portapapeles; se descargó el PNG completo como alternativa.',
                alternativa: true,
            };
        }
    }

    async function descargar(datos, tipo) {
        await descargarCanvas(obtenerCanvas(datos, tipo), tipo);
        return { exito: true, mensaje: 'Imagen PNG descargada correctamente.', alternativa: false };
    }

    // Exponer solo la API pública utilizada por la vista Alpine/Livewire.
    window.MoctezumaGraficasExporter = { copiar, descargar, obtenerCanvas };
})();
