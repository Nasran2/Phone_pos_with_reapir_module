export function appendPatternDot(sequence, dot) {
    if (!Number.isInteger(dot) || dot < 1 || dot > 9 || sequence.includes(dot)) return [...sequence];
    const result = [...sequence], previous = result.at(-1);
    if (previous) {
        const ax = (previous - 1) % 3, ay = Math.floor((previous - 1) / 3);
        const bx = (dot - 1) % 3, by = Math.floor((dot - 1) / 3);
        if ((ax + bx) % 2 === 0 && (ay + by) % 2 === 0) {
            const middle = ((ay + by) / 2) * 3 + ((ax + bx) / 2) + 1;
            if (middle !== previous && middle !== dot && !result.includes(middle)) result.push(middle);
        }
    }
    result.push(dot); return result;
}
export function normalizePattern(sequence) {
    if (!sequence.every(dot => Number.isInteger(dot) && dot >= 1 && dot <= 9) || sequence.length < 4 || sequence.length > 9 || new Set(sequence).size !== sequence.length) return null;
    return sequence.join('-');
}
