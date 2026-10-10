export { default as RichTextEditor } from './RichTextEditor.vue'

// RichTextContent isn't exported here: import it from './RichTextContent.vue' directly, so public
// pages that only display full details don't load the editor (TipTap).
