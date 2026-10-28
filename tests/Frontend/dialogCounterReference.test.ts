import { test } from 'node:test';
import assert from 'node:assert/strict';
import { insertDialogCounterReference } from '../../resources/js/Pages/Dialog/Composables/insertDialogCounterReference.ts';

test('appends a stable counter ID without adding another space after existing whitespace', () => {
    assert.deepEqual(insertDialogCounterReference('Wartość: ', 123), {
        content: 'Wartość: [[counter:123]]',
        cursor: 24,
    });
});

test('always appends the reference after the existing text with a separating space', () => {
    const result = insertDialogCounterReference('Masz Y punktów.', 5);

    assert.equal(result.content, 'Masz Y punktów. [[counter:5]]');
    assert.equal(result.cursor, result.content.length);
});

test('preserves all existing text and formatting', () => {
    const result = insertDialogCounterReference('[b]Wynik: Y[/b]', 7);

    assert.equal(result.content, '[b]Wynik: Y[/b] [[counter:7]]');
});

test('inserts into an empty field without leading whitespace', () => {
    assert.equal(insertDialogCounterReference('', 123).content, '[[counter:123]]');
});

test('preserves emoji and trailing line breaks', () => {
    assert.equal(insertDialogCounterReference('🏆 Wynik:\n', 123).content, '🏆 Wynik:\n[[counter:123]]');
});

test('keeps previous references when adding another value', () => {
    const first = insertDialogCounterReference('Wyniki:', 123);

    assert.equal(insertDialogCounterReference(first.content, 456).content, 'Wyniki: [[counter:123]] [[counter:456]]');
});

test('rejects invalid and imprecise IDs instead of inserting a broken reference', () => {
    for (const counterId of [0, -1, 1.5, NaN, Infinity, Number.MAX_SAFE_INTEGER + 1]) {
        assert.throws(() => insertDialogCounterReference('', counterId), RangeError);
    }
});
