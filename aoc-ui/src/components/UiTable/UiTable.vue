<template>
  <div class="ui-table-wrap">

    <div class="ui-table__toolbar">
      <div v-if="showPerPage" class="ui-table__per-page">
        <span>Show</span>
        <select :value="perPage" @change="$emit('update:perPage', Number($event.target.value))">
          <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
        </select>
        <span>Entries</span>
      </div>
      <slot name="toolbar-search"/>
    </div>

    <div v-if="loading" class="ui-table__loading" aria-live="polite">
      <UiSpinner/>
      <span>Loading…</span>
    </div>

    <table v-else class="ui-table">
      <thead>
      <tr>
        <th
            v-for="col in columns"
            :key="col.key"
            :style="col.width ? { width: col.width } : {}"
            :class="{ 'ui-table__th--sortable': col.sortable }"
            scope="col"
            @click="col.sortable ? onSort(col.key) : null"
        >
          {{ col.label }}
          <span v-if="col.sortable" class="ui-table__sort-icon">
            <span :class="{ active: sortBy === col.key && sortDir === 'asc' }">▲</span>
            <span :class="{ active: sortBy === col.key && sortDir === 'desc' }">▼</span>
          </span>
        </th>
      </tr>
      </thead>
      <tbody>
      <tr v-if="rows.length === 0">
        <td :colspan="columns.length" class="ui-table__empty">
          {{ emptyText }}
        </td>
      </tr>
      <tr v-for="(row, i) in rows" :key="row.id ?? i" :class="rowClass ? rowClass(row) : null">
        <td v-for="col in columns" :key="col.key">
          <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
            {{ row[col.key] ?? '—' }}
          </slot>
        </td>
      </tr>
      </tbody>
    </table>

    <div class="ui-table__footer">

      <div class="ui-table__info">
        Showing {{ from }} to {{ to }} of {{ total.toLocaleString() }} entries
      </div>

      <div class="ui-table__pagination" v-if="totalPages > 1">
        <button
            class="ui-table__page-btn"
            :disabled="page <= 1"
            @click="$emit('update:page', page - 1)"
        >
          Previous
        </button>

          <template v-if="visiblePages[0] > 1">
            <button class="ui-table__page-btn" @click="$emit('update:page', 1)">1</button>
            <span v-if="visiblePages[0] > 2" class="ui-table__ellipsis">…</span>
          </template>

          <button
              v-for="p in visiblePages"
              :key="p"
              class="ui-table__page-btn"
              :class="{ 'ui-table__page-btn--active': p === page }"
              @click="$emit('update:page', p)"
          >
            {{ p }}
          </button>

          <template v-if="visiblePages[visiblePages.length - 1] < totalPages">
            <span v-if="visiblePages[visiblePages.length - 1] < totalPages - 1" class="ui-table__ellipsis">…</span>
            <button class="ui-table__page-btn" @click="$emit('update:page', totalPages)">{{ totalPages }}</button>
          </template>

        <button
            class="ui-table__page-btn"
            :disabled="page >= totalPages"
            @click="$emit('update:page', page + 1)"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import UiSpinner from '../UiSpinner/UiSpinner.vue'

const props = defineProps({
  columns: {type: Array, required: true},
  rows: {type: Array, default: () => []},
  loading: {type: Boolean, default: false},
  emptyText: {type: String, default: 'No data found'},
  total: {type: Number, default: 0},
  page: {type: Number, default: 1},
  perPage: {type: Number, default: 10},
  sortBy: {type: String, default: null},
  sortDir: {type: String, default: 'asc'},
  showPerPage: {type: Boolean, default: true},
  // Optional (row) => string|object|array to add per-row classes (e.g. an overdue highlight) —
  // whatever a Vue `:class` binding accepts. Receives the raw row object for that `<tr>`.
  rowClass: {type: Function, default: null},
})

const emit = defineEmits([
  'update:page',
  'update:perPage',
  'update:sortBy',
  'update:sortDir',
])

const perPageOptions = [10, 20, 50]

const totalPages = computed(() => Math.ceil(props.total / props.perPage) || 1)

const from = computed(() => props.total === 0 ? 0 : (props.page - 1) * props.perPage + 1)
const to   = computed(() => Math.min(props.page * props.perPage, props.total))

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, props.page - 2)
  const end   = Math.min(totalPages.value, props.page + 2)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

function onSort(key) {
  if (props.sortBy === key) {
    emit('update:sortDir', props.sortDir === 'asc' ? 'desc' : 'asc')
  } else {
    emit('update:sortBy', key)
    emit('update:sortDir', 'asc')
  }
}
</script>

<style lang="scss" scoped>
@use '../../styles/tokens' as *;

.ui-table-wrap {
  overflow-x: auto;
}

.ui-table {
  width: 100%;
  border-collapse: collapse;
  font-size: $text-sm;
  font-family: "Roboto", sans-serif;

  th {
    position: relative;
    padding: $space-2-5 $space-3-5;
    padding-right: 24px;
    background: #215eac;
    font-weight: 600;
    color: $color-white;
    text-align: left;
    border-bottom: 2px solid $color-gray-200;
    white-space: nowrap;
  }

  td {
    padding: $space-2 $space-3-5;
    border-bottom: 1px solid $color-gray-100;
    color: $color-gray-800;
    text-align: left;
  }

  tr:hover td {
    background: $color-gray-50;
  }

  // Opt-in via the `rowClass` prop — a light background for rows that need attention (e.g.
  // overdue). Same specificity as `tr:hover td` above, so it must stay declared after it to win.
  tr.ui-table__row--danger td,
  tr.ui-table__row--danger:hover td {
    background: $color-danger-50;
  }
}

.ui-table__empty {
  text-align: center;
  padding: $space-8;
  color: $color-gray-400;
}

.ui-table__loading {
  display: flex;
  align-items: center;
  gap: $space-2;
  padding: $space-6;
  color: $color-gray-500;
  font-size: $text-sm;
  font-family: $font-sans;
}

.ui-table__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: $space-3;
  font-size: $text-sm;
  font-family: $font-sans;
}

.ui-table__info {
  color: $color-gray-500;
}

.ui-table__pagination {
  display: flex;
  align-items: center;
  gap: $space-3;
}

.ui-table__ellipsis {
  padding: 0 4px;
  color: $color-gray-400;
}

.ui-table__page-btn {
  padding: 2px 8px;
  border: none;
  border-radius: $radius-sm;
  background: transparent;
  font-size: $text-sm;
  cursor: pointer;
  color: $color-gray-700;

  &:hover:not(:disabled):not(.ui-table__page-btn--active) { background: $color-gray-100; }
  &:disabled { opacity: 0.4; cursor: default; }

  &--active {
    background: $color-gray-100;
    color: $color-gray-800;
    font-weight: 600;
    border: 1px solid $color-gray-300;
  }
}

.ui-table__th--sortable {
  cursor: pointer;
  user-select: none;

  &:hover { background: #1c4f8f; }
}

.ui-table__sort-icon {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  display: inline-flex;
  flex-direction: column;
  margin-left: 0;
  font-size: 9px;
  line-height: 1;
  color: rgba(255, 255, 255, 0.5);
  vertical-align: middle;

  .active { color: $color-white; }
}

.ui-table__toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: $space-3;
}

.ui-table__per-page {
  display: flex;
  align-items: center;
  gap: $space-2;

  select {
    padding: 2px 6px;
    border: 1px solid $color-gray-200;
    border-radius: 4px;
    font-size: $text-sm;
  }
}
</style>