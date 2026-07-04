export function captureScrollPosition() {
    return {
        x: window.scrollX,
        y: window.scrollY,
    };
}

export function restoreScrollPosition(position) {
    if (!position) {
        return;
    }

    window.scrollTo(position.x, position.y);
}

export function preserveScroll(run) {
    const position = captureScrollPosition();
    run();
    restoreScrollPosition(position);
}

export async function preserveScrollAsync(run) {
    const position = captureScrollPosition();
    await run();
    requestAnimationFrame(() => {
        restoreScrollPosition(position);
        requestAnimationFrame(() => restoreScrollPosition(position));
    });
}
