import { ref } from 'vue';

export function useSelectTableItems() {
    const selectedItems = ref<Array<string | number>>([]);

    function selectItem(isSelected: boolean, value: string | number) {
        const index = selectedItems.value.indexOf(value);
        if (isSelected && index < 0) {
            selectedItems.value.push(value);
        } else if (!isSelected && index > -1) {
            selectedItems.value.splice(index, 1);
        }
    }

    function toggleAll(isSelected: boolean, items: Array<Record<string, any>>, field: string) {
        if (isSelected) {
            selectedItems.value = items.map((item) => item[field]) ?? [];
            return;
        }
        selectedItems.value = [];
    }

    return {
        selectedItems,
        selectItem,
        toggleAll,
    };
}
