/**
 * Adaptador local Tiptap 3 para Fichas Preescolar.
 * Bundled por Vite (solo se descarga cuando se abre la ficha).
 * Nunca guarda instancias ProseMirror en estado reactivo de Alpine.
 */
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { TableKit } from '@tiptap/extension-table';
import TextAlign from '@tiptap/extension-text-align';

export function crearEditorFicha({ element, html, editable, onChange }) {
    if (!element) throw new Error('No existe el contenedor Tiptap.');

    return new Editor({
        element,
        extensions: [
            StarterKit,
            TableKit,
            TextAlign.configure({ types: ['heading', 'paragraph'] }),
        ],
        content: html || '',
        editable: !!editable,
        editorProps: {
            attributes: {
                role: 'textbox',
                'aria-label': 'Descripción pedagógica enriquecida',
                'aria-multiline': 'true',
                spellcheck: 'true',
            },
        },
        onUpdate: () => onChange(),
    });
}
