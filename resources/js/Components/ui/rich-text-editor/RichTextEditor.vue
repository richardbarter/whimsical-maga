<script setup lang="ts">
import { EditorContent, useEditor } from '@tiptap/vue-3'
import { computed, reactive, watch } from 'vue'
import { createRichTextExtensions } from './extensions'
import type { FigureAttributes } from './figure'
import { normalizePastedHeadings, RICH_TEXT_CLASSES } from './utils'
import ImageDialog from './ImageDialog.vue'
import RichTextToolbar from './RichTextToolbar.vue'

const props = withDefaults(defineProps<{
    /** Accessible name for the editing area, e.g. the field's label. */
    label?: string
    invalid?: boolean
    /** Where images are uploaded; null (the default) turns image uploads off. */
    imageUploadUrl?: string | null
    /** URL that uploaded images start with; images from anywhere else are dropped. */
    imageUrlPrefix?: string | null
}>(), {
    label: 'Rich text',
    invalid: false,
    imageUploadUrl: null,
    imageUrlPrefix: null,
})

/** HTML; an empty editor is '' so the server stores null. */
const model = defineModel<string>({ default: '' })

const imageDialog = reactive({
    open: false,
    /** A pasted or dropped image file to start from. */
    file: null as File | null,
    /** The selected figure, when editing its alt text and caption. */
    figure: null as FigureAttributes | null,
})

const canUploadImages = computed(() => props.imageUploadUrl !== null)

// useEditor creates the editor on mount and destroys it on unmount
const editor = useEditor({
    content: model.value,
    extensions: createRichTextExtensions({ imageUrlPrefix: props.imageUrlPrefix }),
    editorProps: {
        attributes: {
            'class': `${RICH_TEXT_CLASSES} min-h-[300px] px-4 py-3 focus:outline-none`,
            'role': 'textbox',
            'aria-multiline': 'true',
            'aria-label': props.label,
        },
        transformPastedHTML: normalizePastedHeadings,
        handlePaste: (_view, event) => {
            // Word also puts a picture of the copied text on the clipboard: paste the text, not the picture
            if (event.clipboardData?.getData('text/html')) {
                return false
            }

            return openDialogForFile(imageFileIn(event.clipboardData))
        },
        handleDrop: (view, event, _slice, moved) => {
            const file = moved ? null : imageFileIn(event.dataTransfer)
            const position = view.posAtCoords({ left: event.clientX, top: event.clientY })

            if (file && canUploadImages.value && position) {
                editor.value?.commands.setTextSelection(position.pos)
            }

            return openDialogForFile(file)
        },
    },
    onUpdate: ({ editor: instance }) => {
        model.value = instance.isEmpty ? '' : instance.getHTML()
    },
})

function imageFileIn(transfer: DataTransfer | null): File | null {
    return Array.from(transfer?.files ?? []).find((file) => file.type.startsWith('image/')) ?? null
}

/** A pasted or dropped image goes through the dialog, so it's uploaded and always gets alt text. */
function openDialogForFile(file: File | null): boolean {
    if (!file || !canUploadImages.value) {
        return false
    }

    imageDialog.file = file
    imageDialog.figure = null
    imageDialog.open = true

    return true
}

/** Edits the selected image's text, or inserts a new image. */
function openImageDialog(): void {
    const instance = editor.value

    imageDialog.file = null
    imageDialog.figure = instance?.isActive('figure') ? (instance.getAttributes('figure') as FigureAttributes) : null
    imageDialog.open = true
}

function insertImage(attributes: FigureAttributes): void {
    editor.value?.chain().focus().insertFigure(attributes).run()
}

function updateImage(attributes: Pick<FigureAttributes, 'alt' | 'caption'>): void {
    editor.value?.chain().focus().updateFigure(attributes).run()
}

/** Only replace the content when it changed from outside (e.g. a form reset), so the cursor doesn't jump. */
watch(model, (value) => {
    const instance = editor.value

    if (!instance || value === (instance.isEmpty ? '' : instance.getHTML())) {
        return
    }

    instance.commands.setContent(value || '', { emitUpdate: false })
})
</script>

<template>
    <div
        class="rich-text-editor rounded-md border bg-card shadow-sm focus-within:ring-1 focus-within:ring-ring"
        :class="invalid ? 'border-destructive' : 'border-input'"
    >
        <RichTextToolbar
            v-if="editor"
            :editor="editor"
            :can-upload-images="canUploadImages"
            @image="openImageDialog"
        />
        <EditorContent :editor="editor" />
        <ImageDialog
            v-model:open="imageDialog.open"
            :upload-url="imageUploadUrl"
            :file="imageDialog.file"
            :figure="imageDialog.figure"
            @insert="insertImage"
            @update="updateImage"
        />
    </div>
</template>

<style scoped>
/* Cell borders and link styles are shared with the public page (.rich-text in app.css); these are editor-only */
.rich-text-editor :deep(.ProseMirror table) {
    table-layout: fixed;
}

.rich-text-editor :deep(.ProseMirror th),
.rich-text-editor :deep(.ProseMirror td) {
    position: relative;
}

.rich-text-editor :deep(.ProseMirror figure) {
    cursor: pointer;
}

.rich-text-editor :deep(.ProseMirror figure.ProseMirror-selectednode img) {
    outline: 3px solid var(--primary);
    outline-offset: 2px;
}

.rich-text-editor :deep(.ProseMirror .selectedCell)::after {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: color-mix(in oklch, var(--primary) 18%, transparent);
}
</style>
