<?php

namespace App\Livewire\Recipes;

use App\Models\InventoryItem;
use App\Models\Recipe;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class RecipeSearch extends Component
{
    public string $query = '';

    public ?string $selectedRecipeId = null;

    public function selectRecipe(string $id): void
    {
        $this->selectedRecipeId = $id;
        $this->query = '';
    }

    public function clearSelection(): void
    {
        $this->selectedRecipeId = null;
    }

    #[Computed]
    public function searchResults(): array
    {
        $q = trim($this->query);
        if ($q === '') {
            return [];
        }

        return Recipe::where('title', 'like', "%{$q}%")
            ->orderBy('title')
            ->limit(15)
            ->get(['id', 'title', 'total_cook_minutes', 'servings_default', 'attribution_label'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'minutes' => $r->total_cook_minutes,
                'servings' => $r->servings_default,
                'attribution_label' => $r->attribution_label,
            ])->all();
    }

    #[Computed]
    public function selectedRecipe(): ?Recipe
    {
        if (! $this->selectedRecipeId) {
            return null;
        }

        return Recipe::with([
            'ingredients.ingredient.localizations',
            'ingredients.unit',
            'tags',
            'nutrientValues.nutrient',
        ])->find($this->selectedRecipeId);
    }

    /**
     * 選択中レシピの材料を「在庫あり/不足」に分類した配列を返す。
     */
    #[Computed]
    public function ingredientBreakdown(): array
    {
        $recipe = $this->selectedRecipe;
        if (! $recipe) {
            return ['have' => [], 'missing' => []];
        }

        $inventoryIds = InventoryItem::where('user_id', Auth::id())
            ->pluck('ingredient_id')
            ->all();

        $have = [];
        $missing = [];
        foreach ($recipe->ingredients as $ri) {
            $row = [
                'name' => $ri->ingredient->displayName(),
                'quantity' => rtrim(rtrim((string) $ri->quantity, '0'), '.'),
                'unit' => $ri->unit->label_ja,
                'optional' => (bool) $ri->is_optional,
            ];
            if (in_array($ri->ingredient_id, $inventoryIds, true)) {
                $have[] = $row;
            } else {
                $missing[] = $row;
            }
        }

        return ['have' => $have, 'missing' => $missing];
    }

    #[Computed]
    public function canMake(): bool
    {
        $breakdown = $this->ingredientBreakdown;
        // 任意材料を除いた不足が無ければ作れる
        $blockingMissing = array_filter($breakdown['missing'], fn ($m) => ! $m['optional']);

        return empty($blockingMissing);
    }

    public function render()
    {
        return view('livewire.recipes.recipe-search');
    }
}
