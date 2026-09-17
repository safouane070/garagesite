{{-- Custom sorteer-dropdown in de huisstijl (vervangt de native <select>).
     Werkt binnen de Alpine-scope van carCatalog(): sort, sortOptions,
     sortOpen, sortLabel(), setSort(). --}}
<div class="relative" @click.outside="sortOpen=false" @keydown.escape.window="sortOpen=false">
    <button type="button" @click="sortOpen=!sortOpen" :aria-expanded="sortOpen" aria-haspopup="listbox"
            class="flex w-full items-center justify-between gap-3 rounded-[3px] border border-hairline bg-paper px-3.5 py-2.5 text-sm text-cream transition hover:border-brass-500/50 focus-visible:ring-2">
        <span class="flex min-w-0 items-center gap-2">
            <span class="hidden font-mono text-[0.7rem] uppercase tracking-wider text-cream/70 sm:inline">Sorteer</span>
            <span class="truncate font-medium" x-text="sortLabel()"></span>
        </span>
        <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-cream/50 transition-transform duration-200" ::class="sortOpen && 'rotate-180'" />
    </button>

    <ul x-show="sortOpen" x-cloak role="listbox"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="absolute inset-x-0 z-40 mt-2 overflow-hidden rounded-[5px] border border-hairline bg-paper py-1 shadow-card">
        <template x-for="opt in sortOptions" :key="opt.v">
            <li>
                <button type="button" @click="setSort(opt.v)" role="option" :aria-selected="sort===opt.v"
                        class="flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm transition"
                        ::class="sort===opt.v ? 'font-medium text-brass-300' : 'text-cream/70 hover:bg-brass-500/[0.06] hover:text-cream'">
                    <span x-text="opt.l"></span>
                    <x-icon name="check" class="h-4 w-4 shrink-0 text-brass-300" x-show="sort===opt.v" />
                </button>
            </li>
        </template>
    </ul>
</div>
