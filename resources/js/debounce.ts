export const debounce = <T extends (...args: any[]) => void>(func: T, timeout: number = 300) => {
    let timer: ReturnType<typeof setTimeout> = 0;
    return (...args: Parameters<T>) => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            func(...args);
        }, timeout);
    };
};
