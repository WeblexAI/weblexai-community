export class DomObserver {
    private mutationObserver: MutationObserver | null = null;

    constructor(
        private readonly onNewNodes: (nodes: Node[]) => void,
        private readonly onRemovedNodes?: (nodes: Node[]) => void,
    ) {}

    start(): void {
        if (this.mutationObserver) {
            return;
        }

        this.mutationObserver = new MutationObserver((mutations) => {
            const changedNodes = new Set<Node>();
            const removedNodes = new Set<Node>();

            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => changedNodes.add(node));
                    mutation.removedNodes.forEach((node) => removedNodes.add(node));
                    return;
                }

                if (mutation.type === 'characterData') {
                    changedNodes.add(mutation.target);
                    return;
                }

                if (mutation.type === 'attributes' && mutation.target.nodeType === Node.ELEMENT_NODE) {
                    changedNodes.add(mutation.target);
                }
            });

            if (removedNodes.size > 0 && this.onRemovedNodes) {
                this.onRemovedNodes(Array.from(removedNodes));
            }

            if (changedNodes.size > 0) {
                this.onNewNodes(Array.from(changedNodes));
            }
        });

        this.mutationObserver.observe(document.body, {
            childList: true,
            subtree: true,
            characterData: true,
            attributes: true,
            attributeFilter: ['hidden', 'aria-hidden', 'open'],
        });
    }

    stop(): void {
        this.mutationObserver?.disconnect();
        this.mutationObserver = null;
    }
}
