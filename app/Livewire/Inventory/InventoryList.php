<?php

namespace App\Livewire\Inventory;

use App\Models\Ingredient;
use App\Models\IngredientLocalization;
use App\Models\IngredientUnitConversion;
use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class InventoryList extends Component
{
    public string $ingredientQuery = '';

    public ?string $selectedIngredientId = null;

    public ?string $selectedIngredientName = null;

    #[Validate('required|numeric|min:0.0001')]
    public ?string $quantity = null;

    #[Validate('required|integer|exists:units,id')]
    public ?int $unitId = null;

    #[Validate('required|in:fridge,freezer,pantry')]
    public string $storageLocation = 'fridge';

    public ?string $expiresAt = null;

    public string $expiresType = 'best_before';

    // 編集モード用
    public ?string $editingItemId = null;

    public ?string $editQuantity = null;

    public ?int $editUnitId = null;

    public string $editStorageLocation = 'fridge';

    public ?string $editExpiresAt = null;

    public string $editExpiresType = 'best_before';

    #[Computed]
    public function units(): \Illuminate\Database\Eloquent\Collection
    {
        return Unit::orderBy('id')->get();
    }

    #[Computed]
    public function items(): \Illuminate\Database\Eloquent\Collection
    {
        return InventoryItem::with(['ingredient.localizations', 'unit'])
            ->where('user_id', Auth::id())
            ->orderByRaw('CASE storage_location WHEN "fridge" THEN 1 WHEN "freezer" THEN 2 ELSE 3 END')
            ->orderBy('expires_at')
            ->get();
    }

    #[Computed]
    public function ingredientSuggestions(): array
    {
        $q = trim($this->ingredientQuery);
        if ($q === '' || mb_strlen($q) < 1) {
            return [];
        }

        $byLocalization = IngredientLocalization::query()
            ->where('locale', 'ja-JP')
            ->where('display_name', 'like', "%{$q}%")
            ->limit(8)
            ->get(['ingredient_id', 'display_name']);

        $byAlias = \App\Models\IngredientAlias::query()
            ->where('locale', 'ja-JP')
            ->where('alias', 'like', "%{$q}%")
            ->with(['ingredient.localizations' => fn ($q) => $q->where('locale', 'ja-JP')])
            ->limit(8)
            ->get();

        $aliasMatches = $byAlias->map(fn ($a) => [
            'id' => $a->ingredient_id,
            'name' => $a->ingredient->displayName(),
        ]);

        return $byLocalization->map(fn ($l) => [
            'id' => $l->ingredient_id,
            'name' => $l->display_name,
        ])->concat($aliasMatches)->unique('id')->values()->all();
    }

    public function selectSuggestion(string $ingredientId, string $name): void
    {
        $this->selectedIngredientId = $ingredientId;
        $this->selectedIngredientName = $name;
        $this->ingredientQuery = $name;

        // 単位を自動選択：選んだ食材の base_unit
        $ingredient = Ingredient::find($ingredientId);
        if ($ingredient) {
            $this->unitId = (int) $ingredient->base_unit_id;
        }
    }

    public function clearIngredient(): void
    {
        $this->selectedIngredientId = null;
        $this->selectedIngredientName = null;
        $this->ingredientQuery = '';
    }

    public function save(): void
    {
        $this->validate();

        if ($this->ingredientQuery === '') {
            $this->addError('ingredientQuery', '食材名を入力してください。');

            return;
        }

        DB::transaction(function () {
            $unit = Unit::findOrFail($this->unitId);

            $ingredient = $this->selectedIngredientId
                ? Ingredient::findOrFail($this->selectedIngredientId)
                : $this->createIngredientFromInput($this->ingredientQuery, $unit);

            $baseQuantity = $this->computeBaseQuantity($ingredient, $unit, (float) $this->quantity);

            InventoryItem::create([
                'user_id' => Auth::id(),
                'ingredient_id' => $ingredient->id,
                'quantity' => $this->quantity,
                'unit_id' => $unit->id,
                'base_quantity' => $baseQuantity,
                'storage_location' => $this->storageLocation,
                'expires_at' => $this->expiresAt ?: null,
                'expires_type' => $this->expiresAt ? $this->expiresType : null,
            ]);
        });

        $this->resetForm();
        unset($this->items);
    }

    public function delete(string $itemId): void
    {
        InventoryItem::where('user_id', Auth::id())
            ->where('id', $itemId)
            ->delete();
        unset($this->items);
    }

    public function startEdit(string $itemId): void
    {
        $item = InventoryItem::where('user_id', Auth::id())
            ->where('id', $itemId)
            ->first();
        if (! $item) {
            return;
        }
        $this->editingItemId = $item->id;
        $this->editQuantity = (string) (float) $item->quantity;
        $this->editUnitId = (int) $item->unit_id;
        $this->editStorageLocation = $item->storage_location;
        $this->editExpiresAt = $item->expires_at?->toDateString();
        $this->editExpiresType = $item->expires_type ?? 'best_before';
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->editingItemId = null;
        $this->editQuantity = null;
        $this->editUnitId = null;
        $this->editStorageLocation = 'fridge';
        $this->editExpiresAt = null;
        $this->editExpiresType = 'best_before';
        $this->resetErrorBag();
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editQuantity' => 'required|numeric|min:0.0001',
            'editUnitId' => 'required|integer|exists:units,id',
            'editStorageLocation' => 'required|in:fridge,freezer,pantry',
        ]);

        $item = InventoryItem::where('user_id', Auth::id())
            ->where('id', $this->editingItemId)
            ->firstOrFail();

        $unit = Unit::findOrFail($this->editUnitId);
        $baseQuantity = $this->computeBaseQuantity($item->ingredient, $unit, (float) $this->editQuantity);

        $item->update([
            'quantity' => $this->editQuantity,
            'unit_id' => $unit->id,
            'base_quantity' => $baseQuantity,
            'storage_location' => $this->editStorageLocation,
            'expires_at' => $this->editExpiresAt ?: null,
            'expires_type' => $this->editExpiresAt ? $this->editExpiresType : null,
        ]);

        $this->cancelEdit();
        unset($this->items);
    }

    private function createIngredientFromInput(string $name, Unit $unit): Ingredient
    {
        // 既に同じ display_name の食材があれば、それを使う（重複防止）
        $existing = IngredientLocalization::where('locale', 'ja-JP')
            ->where('display_name', $name)
            ->first();
        if ($existing) {
            return Ingredient::findOrFail($existing->ingredient_id);
        }

        $slug = Str::slug($name) ?: 'ing-'.Str::random(8);
        $slug = $this->ensureUniqueSlug($slug);

        $baseUnit = $this->resolveBaseUnitForKind($unit->kind);

        $ingredient = Ingredient::create([
            'slug' => $slug,
            'category' => 'other',
            'base_unit_id' => $baseUnit->id,
        ]);

        IngredientLocalization::create([
            'ingredient_id' => $ingredient->id,
            'locale' => 'ja-JP',
            'display_name' => $name,
        ]);

        if ($unit->id !== $baseUnit->id) {
            IngredientUnitConversion::create([
                'ingredient_id' => $ingredient->id,
                'unit_id' => $unit->id,
                'factor_to_base' => 1,
            ]);
        }

        return $ingredient;
    }

    private function ensureUniqueSlug(string $slug): string
    {
        $base = $slug;
        $i = 1;
        while (Ingredient::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    private function resolveBaseUnitForKind(string $kind): Unit
    {
        return match ($kind) {
            'mass' => Unit::where('code', 'g')->firstOrFail(),
            'volume' => Unit::where('code', 'ml')->firstOrFail(),
            'count' => Unit::where('code', 'piece')->firstOrFail(),
        };
    }

    private function computeBaseQuantity(Ingredient $ingredient, Unit $unit, float $quantity): float
    {
        if ($unit->id === $ingredient->base_unit_id) {
            return $quantity;
        }
        $conv = IngredientUnitConversion::where('ingredient_id', $ingredient->id)
            ->where('unit_id', $unit->id)
            ->first();

        return $conv ? $quantity * (float) $conv->factor_to_base : $quantity;
    }

    private function resetForm(): void
    {
        $this->ingredientQuery = '';
        $this->selectedIngredientId = null;
        $this->selectedIngredientName = null;
        $this->quantity = null;
        $this->unitId = null;
        $this->storageLocation = 'fridge';
        $this->expiresAt = null;
        $this->expiresType = 'best_before';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.inventory.inventory-list');
    }
}
