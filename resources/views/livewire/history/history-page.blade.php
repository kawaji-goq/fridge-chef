<div class="mx-auto max-w-md p-4 space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">作った履歴</h1>
        <a href="/inventory" class="text-sm text-emerald-700 hover:text-emerald-900 underline">← 冷蔵庫へ</a>
    </div>

    @php
        $grouped = $this->adoptions->groupBy(fn ($a) => $a->adopted_at->copy()->startOfDay()->toDateString());
    @endphp

    @forelse($grouped as $dateStr => $items)
        @php
            $date = \Carbon\Carbon::parse($dateStr);
            $isToday = $date->isToday();
            $isYesterday = $date->isYesterday();
            $label = $isToday ? '今日' : ($isYesterday ? '昨日' : $date->format('Y年n月j日 (D)'));
        @endphp
        <section class="space-y-2">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-1">{{ $label }}</h2>

            @foreach($items as $adoption)
                <article class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold text-gray-900">{{ $adoption->recipe?->title ?? '?' }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $adoption->adopted_at->format('H:i') }}・
                                {{ rtrim(rtrim((string) $adoption->servings, '0'), '.') }} 人前
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            @php
                                $kcal = $adoption->recipe?->nutrientValues
                                    ?->firstWhere('nutrient.code', 'energy_kcal')
                                    ?->value_per_serving;
                            @endphp
                            @if($kcal !== null)
                                <div class="text-sm font-semibold text-emerald-700">{{ round((float) $kcal * (float) $adoption->servings) }} kcal</div>
                                <div class="text-[10px] text-gray-400">合計</div>
                            @endif
                        </div>
                    </div>

                    @if($adoption->inventoryUses->isNotEmpty())
                        <details class="text-xs text-gray-600">
                            <summary class="cursor-pointer text-gray-700">消費した在庫（{{ $adoption->inventoryUses->count() }}品）</summary>
                            <ul class="mt-2 space-y-0.5">
                                @foreach($adoption->inventoryUses as $use)
                                    @php $name = $use->inventoryItem?->ingredient?->displayName() ?? '?'; @endphp
                                    <li class="flex justify-between">
                                        <span>{{ $name }}</span>
                                        <span class="text-gray-500">{{ rtrim(rtrim(number_format((float) $use->used_base_quantity, 2), '0'), '.') }} {{ $use->unit?->label_ja ?? '' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif

                    <div class="flex justify-end">
                        <button type="button"
                            wire:click="delete('{{ $adoption->id }}')"
                            wire:confirm="この履歴を削除しますか？（在庫は戻りません）"
                            class="text-xs text-gray-400 hover:text-red-600">
                            削除
                        </button>
                    </div>
                </article>
            @endforeach
        </section>
    @empty
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-8 text-center text-gray-500">
            <p>まだ作った料理がありません。</p>
            <p class="text-sm mt-2">
                <a href="/propose" class="text-emerald-700 hover:text-emerald-900 underline">献立を提案してもらう</a>
            </p>
        </div>
    @endforelse

    <nav class="text-center text-sm pt-2">
        <a href="/propose" class="text-emerald-700 hover:text-emerald-900 underline">献立を提案 →</a>
    </nav>
</div>
