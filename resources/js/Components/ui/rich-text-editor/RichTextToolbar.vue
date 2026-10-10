<script setup lang="ts">
import type { Component } from 'vue'
import type { Editor } from '@tiptap/vue-3'
import { ref } from 'vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Separator } from '@/Components/ui/separator'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import {
    Bold,
    ChevronDown,
    Heading3,
    Heading4,
    ImagePlus,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Minus,
    Quote,
    Redo2,
    Strikethrough,
    Table as TableIcon,
    Undo2,
} from 'lucide-vue-next'
import { normalizeLinkUrl } from './utils'

interface ToolbarButton {
    label: string
    shortcut?: string
    icon: Component
    isActive?: () => boolean
    isDisabled?: () => boolean
    run: () => void
}

interface TableAction {
    label: string
    destructive?: boolean
    isDisabled: () => boolean
    run: () => void
}

const MOD = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.userAgent) ? '⌘' : 'Ctrl+'

const props = defineProps<{
    editor: Editor
    /** False until the subject is saved: uploads need its id. */
    canUploadImages: boolean
}>()

const emit = defineEmits<{
    /** Insert an image, or edit the selected one's alt text and caption. */
    image: []
}>()

const buttonGroups: ToolbarButton[][] = [
    [
        { label: 'Bold', shortcut: `${MOD}B`, icon: Bold, isActive: () => props.editor.isActive('bold'), run: () => chain().toggleBold().run() },
        { label: 'Italic', shortcut: `${MOD}I`, icon: Italic, isActive: () => props.editor.isActive('italic'), run: () => chain().toggleItalic().run() },
        { label: 'Strikethrough', icon: Strikethrough, isActive: () => props.editor.isActive('strike'), run: () => chain().toggleStrike().run() },
    ],
    [
        { label: 'Heading', icon: Heading3, isActive: () => props.editor.isActive('heading', { level: 3 }), run: () => chain().toggleHeading({ level: 3 }).run() },
        { label: 'Subheading', icon: Heading4, isActive: () => props.editor.isActive('heading', { level: 4 }), run: () => chain().toggleHeading({ level: 4 }).run() },
    ],
    [
        { label: 'Bullet list', icon: List, isActive: () => props.editor.isActive('bulletList'), run: () => chain().toggleBulletList().run() },
        { label: 'Numbered list', icon: ListOrdered, isActive: () => props.editor.isActive('orderedList'), run: () => chain().toggleOrderedList().run() },
        { label: 'Quote block', icon: Quote, isActive: () => props.editor.isActive('blockquote'), run: () => chain().toggleBlockquote().run() },
        { label: 'Divider', icon: Minus, run: () => chain().setHorizontalRule().run() },
    ],
]

const historyButtons: ToolbarButton[] = [
    { label: 'Undo', shortcut: `${MOD}Z`, icon: Undo2, isDisabled: () => !props.editor.can().undo(), run: () => chain().undo().run() },
    { label: 'Redo', shortcut: `${MOD}Shift+Z`, icon: Redo2, isDisabled: () => !props.editor.can().redo(), run: () => chain().redo().run() },
]

/** Row, column, header and cell actions only apply inside a table, so each is disabled when it can't run. */
const tableActionGroups: TableAction[][] = [
    [
        { label: 'Add row above', isDisabled: () => !props.editor.can().addRowBefore(), run: () => chain().addRowBefore().run() },
        { label: 'Add row below', isDisabled: () => !props.editor.can().addRowAfter(), run: () => chain().addRowAfter().run() },
        { label: 'Add column before', isDisabled: () => !props.editor.can().addColumnBefore(), run: () => chain().addColumnBefore().run() },
        { label: 'Add column after', isDisabled: () => !props.editor.can().addColumnAfter(), run: () => chain().addColumnAfter().run() },
    ],
    [
        { label: 'Delete row', isDisabled: () => !props.editor.can().deleteRow(), run: () => chain().deleteRow().run() },
        { label: 'Delete column', isDisabled: () => !props.editor.can().deleteColumn(), run: () => chain().deleteColumn().run() },
    ],
    [
        { label: 'Toggle header row', isDisabled: () => !props.editor.can().toggleHeaderRow(), run: () => chain().toggleHeaderRow().run() },
        { label: 'Merge cells', isDisabled: () => !props.editor.can().mergeCells(), run: () => chain().mergeCells().run() },
        { label: 'Split cell', isDisabled: () => !props.editor.can().splitCell(), run: () => chain().splitCell().run() },
    ],
    [
        { label: 'Delete table', destructive: true, isDisabled: () => !props.editor.can().deleteTable(), run: () => chain().deleteTable().run() },
    ],
]

const linkOpen = ref(false)
const linkUrl = ref('')
const linkError = ref('')

function chain() {
    return props.editor.chain().focus()
}

function insertTable(): void {
    chain().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()
}

function onLinkOpenChange(open: boolean): void {
    linkOpen.value = open

    if (open) {
        linkUrl.value = props.editor.getAttributes('link').href ?? ''
        linkError.value = ''
    }
}

/** With text selected (or the cursor in a link) the link is applied to it; otherwise the URL is inserted as linked text. */
function applyLink(): void {
    const href = normalizeLinkUrl(linkUrl.value)

    if (!href) {
        linkError.value = 'Enter a web address (https://…) or an email (mailto:…).'
        return
    }

    if (props.editor.state.selection.empty && !props.editor.isActive('link')) {
        chain().insertContent({ type: 'text', text: href, marks: [{ type: 'link', attrs: { href } }] }).run()
    } else {
        chain().extendMarkRange('link').setLink({ href }).run()
    }

    linkOpen.value = false
}

function removeLink(): void {
    chain().extendMarkRange('link').unsetLink().run()
    linkOpen.value = false
}
</script>

<template>
    <TooltipProvider :delay-duration="300">
        <!-- @mousedown.prevent on the buttons keeps focus and the selection in the editor while clicking -->
        <div
            role="toolbar"
            aria-label="Formatting"
            class="sticky top-0 z-10 flex flex-wrap items-center gap-0.5 rounded-t-md border-b border-input bg-muted p-1"
        >
            <template v-for="(group, groupIndex) in buttonGroups" :key="groupIndex">
                <Tooltip v-for="button in group" :key="button.label">
                    <TooltipTrigger as-child>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            :aria-label="button.label"
                            :aria-pressed="button.isActive ? button.isActive() : undefined"
                            :class="{ 'bg-card text-primary shadow-sm hover:bg-card': button.isActive?.() }"
                            @mousedown.prevent
                            @click="button.run"
                        >
                            <component :is="button.icon" class="size-4" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                        {{ button.label }}
                        <span v-if="button.shortcut" class="ml-1 opacity-70">{{ button.shortcut }}</span>
                    </TooltipContent>
                </Tooltip>
                <Separator orientation="vertical" class="mx-1 !h-5" />
            </template>

            <!--
                Link and Table: the Tooltip goes outside the Popover / DropdownMenu. All three share
                reka-ui's Popper, and a trigger anchors to the nearest one, so a Tooltip nested inside
                would take the anchor and leave the popover unpositioned (off-screen).
            -->
            <Tooltip>
                <TooltipTrigger as-child>
                    <span class="inline-flex">
                        <Popover :open="linkOpen" @update:open="onLinkOpenChange">
                            <PopoverTrigger as-child>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Link"
                                    :aria-pressed="editor.isActive('link')"
                                    :class="{ 'bg-card text-primary shadow-sm hover:bg-card': editor.isActive('link') }"
                                    @mousedown.prevent
                                >
                                    <LinkIcon class="size-4" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent align="start" class="w-80 space-y-3">
                                <label for="rich-text-link-url" class="text-sm font-medium">Link address</label>
                                <Input
                                    id="rich-text-link-url"
                                    v-model="linkUrl"
                                    placeholder="https://example.com/report"
                                    :aria-invalid="!!linkError"
                                    @keydown.enter.prevent="applyLink"
                                />
                                <p v-if="linkError" class="text-sm text-destructive">{{ linkError }}</p>
                                <div class="flex justify-end gap-2">
                                    <Button v-if="editor.isActive('link')" type="button" variant="ghost" size="sm" @click="removeLink">
                                        Remove link
                                    </Button>
                                    <Button type="button" size="sm" @click="applyLink">Save link</Button>
                                </div>
                            </PopoverContent>
                        </Popover>
                    </span>
                </TooltipTrigger>
                <TooltipContent>Link</TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <span class="inline-flex">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="gap-1 px-2"
                                    aria-label="Table"
                                    :class="{ 'bg-card text-primary shadow-sm hover:bg-card': editor.isActive('table') }"
                                    @mousedown.prevent
                                >
                                    <TableIcon class="size-4" />
                                    <ChevronDown class="size-3" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start" class="w-52">
                                <DropdownMenuItem :disabled="editor.isActive('table')" @select="insertTable">
                                    Insert table
                                </DropdownMenuItem>
                                <template v-for="(group, groupIndex) in tableActionGroups" :key="groupIndex">
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        v-for="action in group"
                                        :key="action.label"
                                        :disabled="action.isDisabled()"
                                        :class="{ 'text-destructive focus:text-destructive': action.destructive }"
                                        @select="action.run"
                                    >
                                        {{ action.label }}
                                    </DropdownMenuItem>
                                </template>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </span>
                </TooltipTrigger>
                <TooltipContent>Table</TooltipContent>
            </Tooltip>

            <!-- The tooltip sits on a span so it still explains why the button is disabled -->
            <Tooltip>
                <TooltipTrigger as-child>
                    <span class="inline-flex">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            :aria-label="editor.isActive('figure') ? 'Edit image' : 'Insert image'"
                            :aria-pressed="editor.isActive('figure')"
                            :disabled="!canUploadImages && !editor.isActive('figure')"
                            :class="{ 'bg-card text-primary shadow-sm hover:bg-card': editor.isActive('figure') }"
                            @mousedown.prevent
                            @click="emit('image')"
                        >
                            <ImagePlus class="size-4" />
                        </Button>
                    </span>
                </TooltipTrigger>
                <TooltipContent>
                    <template v-if="editor.isActive('figure')">Edit image</template>
                    <template v-else-if="canUploadImages">Insert image (or paste / drop one)</template>
                    <template v-else>Save the subject first to add images</template>
                </TooltipContent>
            </Tooltip>

            <Separator orientation="vertical" class="mx-1 !h-5" />

            <Tooltip v-for="button in historyButtons" :key="button.label">
                <TooltipTrigger as-child>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        :aria-label="button.label"
                        :disabled="button.isDisabled?.()"
                        @mousedown.prevent
                            @click="button.run"
                    >
                        <component :is="button.icon" class="size-4" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent>
                    {{ button.label }}
                    <span v-if="button.shortcut" class="ml-1 opacity-70">{{ button.shortcut }}</span>
                </TooltipContent>
            </Tooltip>
        </div>
    </TooltipProvider>
</template>
