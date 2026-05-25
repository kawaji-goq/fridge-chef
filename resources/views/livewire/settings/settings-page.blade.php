<div class="mx-auto max-w-md p-4 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">設定</h1>
        <a href="/inventory" class="text-sm text-emerald-700 hover:text-emerald-900 underline">← 冷蔵庫へ</a>
    </div>

    @if($this->savedFlash)
        <div class="rounded-lg bg-emerald-100 text-emerald-900 px-4 py-3 text-sm ring-1 ring-emerald-200">
            ✓ 設定を保存しました
        </div>
    @endif

    {{-- 家族構成 --}}
    <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4 space-y-3">
        <h2 class="text-sm font-semibold text-gray-700">家族構成</h2>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">大人</label>
                <input type="number" min="1" max="20" wire:model="householdAdults"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-base">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">子供</label>
                <input type="number" min="0" max="20" wire:model="householdChildren"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-base">
            </div>
        </div>
        <p class="text-xs text-gray-500">提案レシピの分量参考用です。</p>
    </section>

    {{-- アレルギー --}}
    <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4 space-y-3">
        <h2 class="text-sm font-semibold text-gray-700">アレルギー（避ける食材）</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($this->allergens as $allergen)
                @php $isSelected = in_array($allergen->id, $this->allergenIds, true); @endphp
                <button type="button"
                    wire:click="toggleAllergen({{ $allergen->id }})"
                    class="rounded-full px-3 py-1.5 text-xs font-medium border transition
                        {{ $isSelected
                            ? 'bg-red-600 text-white border-red-600'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    {{ $isSelected ? '✓ ' : '' }}{{ $allergen->label_ja }}
                </button>
            @endforeach
        </div>
        <p class="text-xs text-gray-500">選んだものを含むレシピは提案から除外されます。</p>
    </section>

    {{-- 好み系統 --}}
    <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4 space-y-3">
        <h2 class="text-sm font-semibold text-gray-700">好きな料理系統</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($this->preferenceOptions as $opt)
                @php $isSelected = in_array($opt['tag'], $this->preferenceTags, true); @endphp
                <button type="button"
                    wire:click="togglePreference('{{ $opt['tag'] }}')"
                    class="rounded-full px-3 py-1.5 text-xs font-medium border transition
                        {{ $isSelected
                            ? 'bg-emerald-600 text-white border-emerald-600'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    {{ $isSelected ? '✓ ' : '' }}{{ $opt['label'] }}
                </button>
            @endforeach
        </div>
        <p class="text-xs text-gray-500">選んだ系統のレシピが提案で上位に来やすくなります。</p>
    </section>

    {{-- 嫌い食材 --}}
    <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200 p-4 space-y-3">
        <h2 class="text-sm font-semibold text-gray-700">嫌い・避けたい食材</h2>

        <div class="relative">
            <input type="text"
                wire:model.live.debounce.300ms="dislikeQuery"
                placeholder="例：ピーマン、しいたけ"
                autocomplete="off"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-base">

            @if($this->dislikeSuggestions)
                <ul class="absolute z-10 mt-1 w-full rounded-lg bg-white shadow-lg ring-1 ring-gray-200 overflow-hidden">
                    @foreach($this->dislikeSuggestions as $s)
                        <li>
                            <button type="button"
                                wire:click="addDislike('{{ $s['id'] }}')"
                                class="w-full text-left px-3 py-2 hover:bg-emerald-50 text-sm">
                                + {{ $s['name'] }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if($this->dislikedNames)
            <div class="flex flex-wrap gap-2">
                @foreach($this->dislikedNames as $d)
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs">
                        {{ $d['name'] }}
                        <button type="button"
                            wire:click="removeDislike('{{ $d['id'] }}')"
                            class="text-gray-500 hover:text-red-600">×</button>
                    </span>
                @endforeach
            </div>
        @else
            <p class="text-xs text-gray-500">登録なし</p>
        @endif
        <p class="text-xs text-gray-500">登録した食材を含むレシピは提案から除外されます。</p>
    </section>

    <button type="button"
        wire:click="save"
        class="block w-full rounded-lg bg-emerald-600 text-white font-semibold py-3 hover:bg-emerald-700">
        設定を保存
    </button>
</div>
