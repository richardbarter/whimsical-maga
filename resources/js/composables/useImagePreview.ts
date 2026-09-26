import { onUnmounted, ref } from "vue";

/**
 * A preview URL for a file chosen in an <input type="file">. Uses an object URL
 * (no need to read the whole file into a data: URL) and releases it when replaced
 * or when the component unmounts.
 */
export function useImagePreview() {
  const previewUrl = ref<string | null>(null);

  function revoke(): void {
    if (previewUrl.value) {
      URL.revokeObjectURL(previewUrl.value);
    }
  }

  function setFile(file: File | null): void {
    revoke();
    previewUrl.value = file ? URL.createObjectURL(file) : null;
  }

  onUnmounted(revoke);

  return { previewUrl, setFile };
}
