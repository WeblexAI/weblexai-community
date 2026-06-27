import { Page } from '@inertiajs/core';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

export const toastSuccess = (message: string, description?: string, options?: typeof toast) => {
    toast.success(message, {
        description: description,
        ...options,
    });
};

export const toastError = (message: string, description?: string, options?: typeof toast) => {
    toast.error(message, {
        description: description,
        ...options,
    });
};

export const toastInfo = (message: string, description?: string, options?: typeof toast) => {
    toast.info(message, {
        description: description,
        ...options,
    });
};

export const toastResponse = (res: Page, sCb?: CallableFunction, eCb?: CallableFunction) => {
    const message = res.props.message;
    if (res.props.success) {
        if (res.props.message.length) {
            toastSuccess(message as string);
        }
        if (sCb) sCb(res);
    } else {
        toastError(message as string);
        if (eCb) eCb(res);
    }
};

export const chunkArray = <T>(array: T[], size: number): T[][] => {
    const result: T[][] = [];
    for (let i = 0; i < array.length; i += size) {
        result.push(array.slice(i, i + size));
    }
    return result;
};

export const number_format = (val: number, decimal = 2) => {
    const num = Math.ceil(val * Math.pow(10, decimal)) / Math.pow(10, decimal);
    if (Number.isNaN(num)) {
        return '0.0';
    }
    return num.toFixed(decimal);
};

export const dateAndTime = (dateStr: string) => {
    return new Date(dateStr).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
};

export function routeWithProject(name: string, params: any = {}) {
    const page = usePage();
    const project = page.props?.project;

    if (project && !params.project) {
        params.project = project.slug;
    }

    return route(name, params);
}

export function copyRefContent(elRef: HTMLElement | null, message = 'Content copied to clipboard !') {
    if (!elRef) return;
    const code = elRef.innerText;
    copyContent(code, message);
}

export function copyContent(content: string, message = 'Content copied to clipboard !') {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
            .writeText(content)
            .then(() => {
                toastSuccess(message);
            })
            .catch(() => {
                toastError('Failed to copy to clipboard');
            });
    } else {
        const textarea = document.createElement('textarea');
        textarea.value = content;
        textarea.style.position = 'fixed';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                toastSuccess(message);
            } else {
                toastError('Failed to copy to clipboard');
            }
        } catch {
            toastError('Failed to copy to clipboard');
        }
        document.body.removeChild(textarea);
    }
}

export function randomString(length: number = 8) {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

export function ucFirst(str: string): string {
    return str.charAt(0).toUpperCase() + str.slice(1);
}
