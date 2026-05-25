<div class="mx-auto max-w-md p-4 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">冷蔵庫の中身</h1>
        <nav class="flex items-center gap-3 text-sm">
            <a href="/history" class="text-emerald-700 hover:text-emerald-900 underline">履歴</a>
            <a href="/settings" class="text-emerald-700 hover:text-emerald-900 underline">設定</a>
        </nav>
    </div>

    <div class="grid grid-cols-2 gap-2">
        <a href="/propose" class="block rounded-xl bg-emerald-600 text-white font-semibold py-3 text-center shadow-sm hover:bg-emerald-700">
            今日の献立を<br>提案してもらう
        </a>
        <a href="/search" class="block rounded-xl bg-white text-emerald-700 ring-1 ring-emerald-300 font-semibold py-3 text-center shadow-sm hover:bg-emerald-50">
            料理から<br>足りない物を探す
        </a>
    </div>

    {{-- 追加フォーム --}}
    <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4 space-y-4">
        <h2 class="text-sm font-semibold text-gray-600">食材を追加</h2>

        {{-- 食材名（オートコンプリート） --}}
        <div class="relative">
            <label class="block text-xs font-medium text-gray-700 mb-1">食材名</label>
            <input
                type="text"
                wire:model.live.debounce.300ms="ingredientQuery"
                placeholder="例：玉ねぎ、卵、牛乳"
                autocomplete="off"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-base"
            />

            @if($this->ingredientSuggestions && ! $selectedIngredientId)
                <ul class="absolute z-10 mt-1 w-full rounded-lg bg-white shadow-lg ring-1 ring-gray-200 overflow-hidden">
                    @foreach($this->ingredientSuggestions as $s)
                        <li>
                            <button
                                type="button"
                                wire:click="selectSuggestion('{{ $s['id'] }}', @js($s['name']))"
                                class="w-full text-left px-3 py-2 hover:bg-emerald-50 text-sm"
                            >{{ $s['name'] }}</button>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($selectedIngredientId)
                <p class="mt-1 text-xs text-emerald-700">
                    既存マスタから選択：<strong>{{ $selectedIngredientName }}</strong>
                    <button type="button" wire:click="clearIngredient" class="underline ml-2">クリア</button>
                </p>
            @elseif(trim($ingredientQuery) !== '' && empty($this->ingredientSuggestions))
                <p class="mt-1 text-xs text-amber-700">
                    マスタに見つかりません。このまま登録すると新規食材として作成されます。
                </p>
            @endif

            @error('ingredientQuery') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- 数量 + 単位 --}}
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">数量</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    wire:model="quantity"
                    placeholder="2"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-base"
                />
                @error('quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">単位</label>
                <select
                    wire:model="unitId"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-base"
                >
                    <option value="">選択</option>
                    @foreach($this->units as $u)
                        <option value="{{ $u->id }}">{{ $u->label_ja }}</option>
                    @endforeach
                </select>
                @error('unitId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- 保管場所 --}}
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">保管場所</label>
            <div class="grid grid-cols-3 gap-2">
                @foreach(['fridge' => '冷蔵', 'freezer' => '冷凍', 'pantry' => '常温'] as $val => $label)
                    <label class="flex items-center justify-center rounded-lg border px-3 py-2 cursor-pointer text-sm font-medium
                        {{ $storageLocation === $val ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        <input type="radio" wire:model.live="storageLocation" value="{{ $val }}" class="sr-only" />
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- 期限 --}}
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">期限（任意）</label>
            <div class="grid grid-cols-2 gap-3">
                <input
                    type="date"
                    wire:model="expiresAt"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-base"
                />
                <select
                    wire:model="expiresType"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-base"
                >
                    <option value="best_before">賞味期限</option>
                    <option value="use_by">消費期限</option>
                </select>
            </div>
        </div>

        <button
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            class="block w-full rounded-lg bg-emerald-600 text-white font-semibold py-3 hover:bg-emerald-700 disabled:opacity-50"
        >
            <span wire:loading.remove>追加する</span>
            <span wire:loading>保存中…</span>
        </button>
    </section>

    {{-- 在庫一覧 --}}
    <section class="space-y-3">
        <h2 class="text-sm font-semibold text-gray-600">現在の在庫（{{ $this->items->count() }}件）</h2>

        @forelse($this->items as $item)
            <article class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-3 flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium
                            {{ ['fridge'=>'bg-blue-100 text-blue-800','freezer'=>'bg-cyan-100 text-cyan-800','pantry'=>'bg-yellow-100 text-yellow-800'][$item->storage_location] }}">
                            {{ ['fridge'=>'冷蔵','freezer'=>'冷凍','pantry'=>'常温'][$item->storage_location] }}
                        </span>
                        <h3 class="font-semibold text-gray-900 truncate">{{ $item->ingredient->displayName() }}</h3>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ rtrim(rtrim((string) $item->quantity, '0'), '.') }} {{ $item->unit->label_ja }}
                        @if($item->expires_at)
                            <span class="ml-2 text-xs {{ $item->expires_at->isPast() ? 'text-red-600 font-semibold' : ($item->expires_at->diffInDays(now()) >= -3 ? 'text-amber-600' : 'text-gray-500') }}">
                                {{ $item->expires_at->format('Y/m/d') }}
                                @if($item->expires_at->isPast()) (期限切れ) @endif
                            </span>
                        @endif
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="delete('{{ $item->id }}')"
                    wire:confirm="削除しますか？"
                    class="text-red-600 hover:text-red-800 p-2"
                    aria-label="削除"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </button>
            </article>
        @empty
            <p class="text-center text-gray-500 py-8">まだ在庫が登録されていません。</p>
        @endforelse
    </section>
</div>
