<template>
    <aside class="notes-sidebar">
        <ul v-if="notes.length" class="notes-sidebar__list">
            <li
                v-for="note in notes"
                :key="note.id"
                class="notes-sidebar__item"
            >
                <div class="notes-sidebar__item-header">
                    <span>
                        <strong>{{ authorOf(note) }}</strong> commented
                    </span>
                    <span class="notes-sidebar__date">{{ note.commented_at }}</span>
                </div>
                <p class="notes-sidebar__item-detail">
                    <span
                        v-for="(part, index) in detailParts(note)"
                        :key="index"
                        :class="{ 'notes-sidebar__name': part.isName }"
                        >{{ part.text }}</span
                    >
                </p>
            </li>
        </ul>
        <p v-else class="notes-sidebar__empty">No notes yet.</p>
    </aside>
</template>

<script setup>
// The tracker's Activity Log exactly as the server has it: rows of the shared
// `notes` table, written on every logged field change and read back through
// GET /vbc/hospitalization/trackers/{id}/notes. Already newest-first, so nothing
// is re-sorted here. Each entry is a card: "{author} commented" and its date on
// top, the entry's own text below, with the author's name in bold wherever it
// appears in that text too.
defineProps({
    notes: { type: Array, default: () => [] }, // [{ id, notes, author_name, commented_at }]
})

// Status-change entries are stored as two lines: "{Status} by {author} at {date}" and "Status changed to
// '{status}' by {author}...". The first only repeats what the card header already says (who and when), so
// it is not shown — and only when the second line follows, since the other entries (Ready for Review,
// Completed, ...) have that line as their only one.
const REPEATS_HEADER = /^.+ by .+ at \d{2}\/\d{2}\/\d{4} \d{2}:\d{2} [AP]M\n(?=Status changed to )/

// A note whose author is unknown (a deleted user) still has to say who wrote it.
function authorOf(note) {
    return note.author_name || 'Someone'
}

// The text split around the author's name, so the name alone can be bolded.
function detailParts(note) {
    const text = (note.notes ?? '').replace(REPEATS_HEADER, '')
    if (!note.author_name) return [{ text, isName: false }]

    return text
        .split(note.author_name)
        .flatMap((chunk, index) =>
            index === 0
                ? [{ text: chunk, isName: false }]
                : [
                      { text: note.author_name, isName: true },
                      { text: chunk, isName: false },
                  ],
        )
        .filter((part) => part.text !== '')
}
</script>

<style scoped lang="scss">
@use '@/modules/vbc/styles/tokens' as *;

/* A panel of its own, as in the design, with its own scroll — the activity trail
   grows without bound and must not push the record's fields around. */
.notes-sidebar {
    width: 450px;
    flex: 0 0 450px;
    min-height: 0;
    overflow-y: auto;
    background: $surface-muted;
    border: 1px solid $border;
    padding: 16px;
}
.notes-sidebar__list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.notes-sidebar__item {
    margin-bottom: 12px;
    padding: 12px 16px;
    background: $surface;
    border: 1px solid $border;
    border-radius: 8px;
    font-size: $text-sm;
    color: $text;
}
.notes-sidebar__item-header {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-style: italic;
}
.notes-sidebar__date {
    white-space: nowrap;
}
.notes-sidebar__item-detail {
    margin: 12px 0 0;
    white-space: pre-line;
}
.notes-sidebar__name {
    font-weight: 700;
}
.notes-sidebar__empty {
    color: $text-muted;
    font-size: $text-sm;
}
</style>
