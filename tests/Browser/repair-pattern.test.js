import test from 'node:test';
import assert from 'node:assert/strict';
import {appendPatternDot, normalizePattern} from '../../resources/js/repair-pattern.js';
test('horizontal gaps include an untouched middle dot', () => assert.deepEqual(appendPatternDot([1], 3), [1, 2, 3]));
test('vertical and diagonal gaps include the middle', () => {
    assert.deepEqual(appendPatternDot([1], 7), [1, 4, 7]);
    assert.deepEqual(appendPatternDot([1], 9), [1, 5, 9]);
});
test('already visited dots are neither duplicated nor mutated', () => {
    const original = [2, 1]; assert.deepEqual(appendPatternDot(original, 3), [2, 1, 3]); assert.deepEqual(original, [2, 1]);
    assert.deepEqual(appendPatternDot(original, 2), original);
});
test('invalid and fractional dots are ignored', () => [0, 10, 2.5, '2'].forEach(dot => assert.deepEqual(appendPatternDot([1], dot), [1])));
test('only four to nine distinct valid dots can be saved', () => {
    assert.equal(normalizePattern([1,2,5,8,9]), '1-2-5-8-9');
    [[1,2], [1,2,3,3], [0,2,3,4], ['1',2,3,4], [1,2,3,4,5,6,7,8,9,10]].forEach(sequence => assert.equal(normalizePattern(sequence), null));
});
