export const insertDialogCounterReference = (
    content: string,
    counterId: number,
): { content: string; cursor: number } => {
    if (!Number.isSafeInteger(counterId) || counterId < 1) {
        throw new RangeError('Nieprawidłowe ID licznika');
    }

    const separator = content.length > 0 && !/\s$/.test(content) ? ' ' : '';
    const updatedContent = `${content}${separator}[[counter:${counterId}]]`;

    return {
        content: updatedContent,
        cursor: updatedContent.length,
    };
};
