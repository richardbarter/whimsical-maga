import { router } from "@inertiajs/vue3";
import { computed, ref, type Ref } from "vue";

/**
 * State for a "confirm, then delete" dialog on an index page. Pair with ConfirmDeleteDialog:
 *
 *   const deletion = useDeleteConfirmation<Quote>("admin.quotes.destroy");
 *   <ConfirmDeleteDialog v-model:open="deletion.isOpen.value" @confirm="deletion.executeDelete" … />
 */
export function useDeleteConfirmation<T extends { id: number }>(destroyRouteName: string) {
  const target = ref<T | null>(null) as Ref<T | null>;
  const isDeleting = ref(false);

  const isOpen = computed({
    get: () => target.value !== null,
    set: (open: boolean) => {
      if (!open && !isDeleting.value) {
        target.value = null;
      }
    },
  });

  function confirmDelete(item: T): void {
    target.value = item;
  }

  function executeDelete(): void {
    if (!target.value) {
      return;
    }

    isDeleting.value = true;

    router.delete(route(destroyRouteName, target.value.id), {
      preserveScroll: true,
      onFinish: () => {
        isDeleting.value = false;
        target.value = null;
      },
    });
  }

  return { target, isOpen, isDeleting, confirmDelete, executeDelete };
}
