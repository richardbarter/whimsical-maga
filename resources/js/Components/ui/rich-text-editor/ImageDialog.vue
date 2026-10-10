<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { isAxiosError } from 'axios'
import { Button } from '@/Components/ui/button'
import { FormField } from '@/Components/ui/form-field'
import { Input } from '@/Components/ui/input'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import { useImagePreview } from '@/composables/useImagePreview'
import type { FigureAttributes } from './figure'

interface UploadedImage {
    id: number
    url: string
    width: number | null
    height: number | null
}

type FieldErrors = Partial<Record<'image' | 'alt_text' | 'caption' | 'credit' | 'general', string>>

const MAX_BYTES = 10 * 1024 * 1024
const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp']

const open = defineModel<boolean>('open', { required: true })

const props = defineProps<{
    /** Where to upload; null when uploads aren't available (e.g. before the subject is saved). */
    uploadUrl: string | null
    /** A file pasted or dropped into the editor, to start from. */
    file: File | null
    /** The selected figure when editing its text; null when inserting a new image. */
    figure: FigureAttributes | null
}>()

const emit = defineEmits<{
    insert: [attributes: FigureAttributes]
    update: [attributes: Pick<FigureAttributes, 'alt' | 'caption'>]
}>()

const chosenFile = ref<File | null>(null)
const altText = ref('')
const caption = ref('')
const credit = ref('')
const uploading = ref(false)
const errors = ref<FieldErrors>({})
const { previewUrl, setFile } = useImagePreview()

const isEditing = computed(() => props.figure !== null)
const shownImage = computed(() => (isEditing.value ? props.figure?.src : previewUrl.value) ?? null)

function reset(): void {
    errors.value = {}
    uploading.value = false
    altText.value = props.figure?.alt ?? ''
    caption.value = props.figure?.caption ?? ''
    credit.value = ''
    chooseFile(props.file)
}

function chooseFile(file: File | null): void {
    chosenFile.value = file
    setFile(file)
    errors.value.image = file ? fileProblem(file) : undefined
}

function onFileChange(event: Event): void {
    chooseFile((event.target as HTMLInputElement).files?.[0] ?? null)
}

/** Checked here too so a wrong file fails at once, not after a 10 MB upload. */
function fileProblem(file: File): string | undefined {
    if (!ACCEPTED_TYPES.includes(file.type)) {
        return 'The image must be a JPG, PNG or WebP file.'
    }

    return file.size > MAX_BYTES ? 'The image must be 10 MB or smaller.' : undefined
}

/** "Caption — Credit", or whichever of the two is given. */
function captionWithCredit(): string {
    return [caption.value, credit.value].map((part) => part.trim()).filter(Boolean).join(' — ')
}

async function submit(): Promise<void> {
    errors.value = {}

    if (!altText.value.trim()) {
        errors.value.alt_text = "Describe the image for people who can't see it."
    }

    if (isEditing.value) {
        if (!errors.value.alt_text) {
            emit('update', { alt: altText.value.trim(), caption: caption.value.trim() })
            open.value = false
        }
        return
    }

    if (!chosenFile.value) {
        errors.value.image = 'Choose an image to upload.'
    } else {
        errors.value.image = fileProblem(chosenFile.value)
    }

    if (errors.value.alt_text || errors.value.image || !props.uploadUrl) {
        return
    }

    uploading.value = true

    try {
        const body = new FormData()
        body.append('image', chosenFile.value as File)
        body.append('alt_text', altText.value.trim())
        body.append('caption', caption.value.trim())
        body.append('credit', credit.value.trim())

        const { data } = await window.axios.post<UploadedImage>(props.uploadUrl, body)

        emit('insert', {
            src: data.url,
            alt: altText.value.trim(),
            width: data.width,
            height: data.height,
            caption: captionWithCredit(),
        })
        open.value = false
    } catch (error: unknown) {
        errors.value = validationErrors(error)
    } finally {
        uploading.value = false
    }
}

/** Laravel's 422 response lists messages per field; show the first for each. */
function validationErrors(error: unknown): FieldErrors {
    if (isAxiosError(error) && error.response?.status === 422) {
        const fieldErrors = error.response.data.errors as Record<string, string[]>

        return Object.fromEntries(Object.entries(fieldErrors).map(([field, messages]) => [field, messages[0]]))
    }

    return { general: 'The upload failed. Please try again.' }
}

watch(open, (isOpen) => {
    if (isOpen) {
        reset()
    }
})
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ isEditing ? 'Edit image' : 'Insert image' }}</DialogTitle>
                <DialogDescription>
                    {{ isEditing
                        ? 'Change the alt text or caption. To use a different image, delete this one and insert another.'
                        : 'JPG, PNG or WebP, up to 10 MB. Location data is removed from photos on upload.' }}
                </DialogDescription>
            </DialogHeader>

            <form id="rich-text-image-form" class="space-y-4" @submit.prevent="submit">
                <img
                    v-if="shownImage"
                    :src="shownImage"
                    alt=""
                    class="max-h-48 w-full rounded-md border border-border bg-muted object-contain"
                />

                <FormField v-if="!isEditing" label="Image *" for="rich-text-image-file" :error="errors.image">
                    <!-- A plain input, like BackgroundForm: the Input component's v-model doesn't suit file inputs -->
                    <input
                        id="rich-text-image-file"
                        type="file"
                        :accept="ACCEPTED_TYPES.join(',')"
                        class="block w-full text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90"
                        @change="onFileChange"
                    />
                    <p v-if="chosenFile" class="text-xs text-muted-foreground">Selected: {{ chosenFile.name }}</p>
                </FormField>

                <FormField label="Alt text *" for="rich-text-image-alt" :error="errors.alt_text">
                    <Input
                        id="rich-text-image-alt"
                        v-model="altText"
                        placeholder="e.g. Bar chart of mail-in ballot fraud rates, 2016 to 2024"
                    />
                    <p class="text-xs text-muted-foreground">
                        Read aloud by screen readers and shown if the image can't load.
                    </p>
                </FormField>

                <FormField label="Caption" for="rich-text-image-caption" :error="errors.caption">
                    <Input
                        id="rich-text-image-caption"
                        v-model="caption"
                        :placeholder="isEditing ? 'Caption — Credit' : 'Shown under the image'"
                    />
                </FormField>

                <FormField v-if="!isEditing" label="Credit" for="rich-text-image-credit" :error="errors.credit">
                    <Input
                        id="rich-text-image-credit"
                        v-model="credit"
                        placeholder="e.g. Brennan Center for Justice"
                    />
                </FormField>

                <p v-if="errors.general" class="text-sm text-destructive">{{ errors.general }}</p>
            </form>

            <DialogFooter>
                <Button type="button" variant="outline" :disabled="uploading" @click="open = false">Cancel</Button>
                <Button type="submit" form="rich-text-image-form" :disabled="uploading">
                    {{ uploading ? 'Uploading…' : isEditing ? 'Save changes' : 'Upload and insert' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
